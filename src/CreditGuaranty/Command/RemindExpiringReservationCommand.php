<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Command;

use DateInterval;
use DateTime;
use InvalidArgumentException;
use JsonException;
use Swift_Mailer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Core\SwiftMailer\MailjetMessage;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Entity\ReservationStatus;
use Unilend\CreditGuaranty\Entity\StaffPermission;
use Unilend\CreditGuaranty\Repository\ReservationRepository;
use Unilend\CreditGuaranty\Service\StaffPermissionManager;

class RemindExpiringReservationCommand extends Command
{
    private const OPTION_CASA = 'casa';
    private const OPTION_CR   = 'cr';

    private const COMMAND_OPTIONS = [
        self::OPTION_CASA,
        self::OPTION_CR,
    ];

    private const CASA_LIMIT_48_HOURS       = 48;
    private const CR_LIMIT_15_DAYS          = 15;
    private const CR_LIMIT_15_DAYS_IN_HOURS = self::CR_LIMIT_15_DAYS * 24;
    private const CR_LIMIT_1_WEEK_IN_HOURS  = 7                      * 24;
    private const CR_LIMIT_72_HOURS         = 72;
    private const CR_LIMIT_48_HOURS         = 48;

    private const CR_LIMIT_HOURS = [
        self::CR_LIMIT_15_DAYS_IN_HOURS,
        self::CR_LIMIT_1_WEEK_IN_HOURS,
        self::CR_LIMIT_72_HOURS,
        self::CR_LIMIT_48_HOURS,
    ];

    private const CASA_TEMPLATE_ID    = MailjetMessage::TEMPLATE_CREDIT_GUARANTY_REMIND_EXPIRING_RESERVATION_CASA;
    private const CR_TEMPLATE_ID      = MailjetMessage::TEMPLATE_CREDIT_GUARANTY_REMIND_EXPIRING_RESERVATION_CR;
    private const CR_LIST_TEMPLATE_ID = MailjetMessage::TEMPLATE_CREDIT_GUARANTY_REMIND_EXPIRING_RESERVATION_LIST_CR;

    private const CR_TEMPLATE_IDS = [
        self::CR_TEMPLATE_ID,
        self::CR_LIST_TEMPLATE_ID,
    ];

    protected static $defaultName = 'kls:reservation:expiring:remind';

    private OutputInterface $output;
    private ReservationRepository $reservationRepository;
    private StaffPermissionManager $staffPermissionManager;
    private Swift_Mailer $mailer;

    public function __construct(
        ReservationRepository $reservationRepository,
        StaffPermissionManager $staffPermissionManager,
        Swift_Mailer $mailer
    ) {
        parent::__construct();

        $this->reservationRepository  = $reservationRepository;
        $this->staffPermissionManager = $staffPermissionManager;
        $this->mailer                 = $mailer;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send about to expire reservation reminder mails')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Send reminder mails to CASA or CR staff')
            ->setHelp(
                'kls:reservation:expiring:remind --to=casa' . "\n"
                . 'kls:reservation:expiring:remind --to=cr'
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $toOption = $input->getOption('to');

        if (empty($toOption)) {
            throw new InvalidArgumentException('The command option --to is missing and/or requires value among them : casa, cr');
        }

        if (false === \in_array($toOption, self::COMMAND_OPTIONS)) {
            throw new InvalidArgumentException('The command option --to value should be one among them : casa, cr');
        }

        $staffList        = [];
        $reservationsList = [];

        /** @var Reservation $reservation */
        foreach ($this->reservationRepository->findAll() as $reservation) {
            if ($reservation->isFormalized() || $reservation->isArchived() || $reservation->isRefusedByManagingCompany()) {
                continue;
            }

            $program             = $reservation->getProgram();
            $reservationDuration = $program->getReservationDuration();

            if (null === $reservationDuration) {
                $io->warning(\sprintf('Reservation #%s is ignored because reservation duration limit of program #%s is empty.', $reservation->getId(), $program->getId()));

                continue;
            }

            $creationDate = $reservation->getDateByStatus(ReservationStatus::STATUS_SENT);

            if (null === $creationDate) {
                continue;
            }

            $limitDate    = $creationDate->add(new DateInterval("P{$reservationDuration}M"));
            $nowDate      = new DateTime('now');
            $intervalDate = $nowDate->diff($limitDate);

            // cases that should not be happened
            // if intervalDate is inverted, that means the reservation exceeded reservation duration limit
            if (0 < $intervalDate->y || 1 === $intervalDate->invert) {
                // need exception here ?
                continue;
            }

            // if remaining interval is superior to 2 weeks
            // we need a reservations list for template_credit_guaranty_remind_expiring_reservation_list_cr
            if (self::OPTION_CR === $toOption && (0 < $intervalDate->m || self::CR_LIMIT_15_DAYS < $intervalDate->d)) {
                foreach ($reservation->getManagingCompany()->getStaff() as $staff) {
                    $userId = $staff->getUser()->getId();

                    // an user can have multiple staff
                    // to avoid to spam user, we store userId in key and check if it is exists before adding it
                    if (false === \in_array($userId, \array_keys($staffList), true)) {
                        $staffList[$userId] = $staff;
                    }
                }

                $reservationsList[] = [
                    'name'         => $reservation->getName(),
                    'creationDate' => $creationDate,
                    'endDate'      => $limitDate,
                ];

                continue;
            }

            if (0 < $intervalDate->m) {
                continue;
            }

            $daysInHours    = \bcmul((string) $intervalDate->d, '24', 2);
            $minutesInHours = \bcdiv((string) $intervalDate->i, '60', 2);
            $secondsInHours = \bcdiv((string) $intervalDate->s, '3600', 2);
            $remainingHours = (float) \bcadd(\bcadd(\bcadd($daysInHours, (string) $intervalDate->h, 2), $minutesInHours, 2), $secondsInHours, 2);

            if (self::OPTION_CASA === $toOption) {
                $this->handleMailingToCasa($reservation, $remainingHours);
            }

            if (self::OPTION_CR === $toOption) {
                $this->handleMailingToCr($reservation, $remainingHours);
            }
        }

        if (false === empty($staffList) && false === empty($reservationsList)) {
            $this->sendMail(
                self::CR_LIST_TEMPLATE_ID,
                $staffList,
                ['reservations' => $reservationsList]
            );
        }

        return Command::SUCCESS;
    }

    /**
     * @throws JsonException
     */
    private function handleMailingToCasa(Reservation $reservation, float $remainingHours): void
    {
        if ($remainingHours > self::CASA_LIMIT_48_HOURS) {
            return;
        }

        $this->sendMail(
            self::CASA_TEMPLATE_ID,
            $reservation->getProgram()->getManagingCompany()->getStaff(),
            ['reservationName' => $reservation->getName()]
        );
    }

    /**
     * @throws JsonException
     */
    private function handleMailingToCr(Reservation $reservation, float $remainingHours): void
    {
        // need to convert float to int to avoid sending mail every day
        $remainingHours = (int) $remainingHours;

        if (false === \in_array($remainingHours, self::CR_LIMIT_HOURS, true)) {
            return;
        }

        $this->sendMail(
            self::CR_TEMPLATE_ID,
            $reservation->getManagingCompany()->getStaff(),
            ['reservationName' => $reservation->getName()]
        );
    }

    /**
     * @param array|Staff[] $staffList
     *
     * @throws JsonException
     */
    private function sendMail(int $templateId, array $staffList, array $vars): void
    {
        foreach ($staffList as $staff) {
            if (false === $staff->isActive()) {
                continue;
            }

            if (\in_array($templateId, self::CR_TEMPLATE_IDS, true) && false === $this->hasReservationPermission($staff)) {
                continue;
            }

            $user              = $staff->getUser();
            $vars['firstName'] = $user->getFirstName();
            $vars['lastName']  = $user->getLastName();
            $mailjetMessage    = $this->createMessage($user, $templateId, $vars);

            $this->send($mailjetMessage);
        }
    }

    /**
     * @throws JsonException
     */
    private function createMessage(User $user, int $templateId, array $vars = []): MailjetMessage
    {
        $message = new MailjetMessage();
        $message->setTemplateId($templateId);
        $message->setTo($user->getEmail());
        $message->setVars($vars);

        return $message;
    }

    /**
     * @throws JsonException
     */
    private function send(MailjetMessage $mailjetMessage): void
    {
        $vars       = $mailjetMessage->getVars();
        $recipients = \array_keys($mailjetMessage->getTo());

        if ($this->output->isVerbose()) {
            $reservationName = $vars['reservationName'] ?? 'list';
            $this->output->writeln(\sprintf('Sending an email to %s for reservation %s', \implode(' and ', $recipients), $reservationName));
        }

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln('Variables for message :');
            foreach ($vars as $key => $value) {
                if (\is_array($value)) {
                    $value = \json_encode($value);
                }
                $this->output->writeln("\t{$key} : {$value}");
            }
        }

        $this->mailer->send($mailjetMessage);

        if ($this->output->isVerbose()) {
            $this->output->writeln('Email sent');
        }
    }

    private function hasReservationPermission(Staff $staff): bool
    {
        return $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_CREATE_RESERVATION)
            || $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_EDIT_RESERVATION)
        ;
    }
}
