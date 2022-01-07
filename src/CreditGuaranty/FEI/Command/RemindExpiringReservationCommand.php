<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Command;

use DateInterval;
use DateTime;
use InvalidArgumentException;
use KLS\Core\Entity\Staff;
use KLS\Core\Mailer\MailjetMessage;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\CreditGuaranty\FEI\Repository\ReservationRepository;
use KLS\CreditGuaranty\FEI\Service\StaffPermissionManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;

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

    protected static $defaultName = 'kls:fei:reservation:expiring:remind';

    private MailerInterface        $mailer;
    private ReservationRepository  $reservationRepository;
    private StaffPermissionManager $staffPermissionManager;
    private LoggerInterface        $logger;

    public function __construct(
        MailerInterface $mailer,
        ReservationRepository $reservationRepository,
        StaffPermissionManager $staffPermissionManager,
        LoggerInterface $logger
    ) {
        parent::__construct();

        $this->mailer                 = $mailer;
        $this->reservationRepository  = $reservationRepository;
        $this->staffPermissionManager = $staffPermissionManager;
        $this->logger                 = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send about to expire reservation reminder mails')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Send reminder mails to CASA or CR staff')
            ->setHelp(
                self::$defaultName . ' --to=casa' . "\n" .
                self::$defaultName . ' --to=cr'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $toOption = $input->getOption('to');

        if (empty($toOption)) {
            throw new InvalidArgumentException(
                'The command option --to is missing and/or requires value among them : casa, cr'
            );
        }

        if (false === \in_array($toOption, self::COMMAND_OPTIONS, true)) {
            throw new InvalidArgumentException('The command option --to value should be one among them : casa, cr');
        }

        $staffList        = [];
        $reservationsList = [];

        /** @var Reservation $reservation */
        foreach ($this->reservationRepository->findAll() as $reservation) {
            if (
                $reservation->isFormalized()
                || $reservation->isArchived()
                || $reservation->isRefusedByManagingCompany()
            ) {
                continue;
            }

            $program             = $reservation->getProgram();
            $reservationDuration = $program->getReservationDuration();

            if (null === $reservationDuration) {
                $io->warning(
                    \sprintf(
                        'Reservation #%s is ignored because reservation duration limit of program #%s is empty.',
                        $reservation->getId(),
                        $program->getId()
                    )
                );

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
                    if (false === \array_key_exists($userId, $staffList)) {
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
            $remainingHours = (float) \bcadd(
                \bcadd(\bcadd($daysInHours, (string) $intervalDate->h, 2), $minutesInHours, 2),
                $secondsInHours,
                2
            );

            if (self::OPTION_CASA === $toOption) {
                $this->handleMailingToCasa($reservation, $remainingHours, $output);
            }

            if (self::OPTION_CR === $toOption) {
                $this->handleMailingToCr($reservation, $remainingHours, $output);
            }
        }

        if (false === empty($staffList) && false === empty($reservationsList)) {
            $this->sendMail(
                self::CR_LIST_TEMPLATE_ID,
                $staffList,
                ['reservations' => $reservationsList],
                $output
            );
        }

        return Command::SUCCESS;
    }

    private function handleMailingToCasa(Reservation $reservation, float $remainingHours, OutputInterface $output): void
    {
        if ($remainingHours > self::CASA_LIMIT_48_HOURS) {
            return;
        }

        $this->sendMail(
            self::CASA_TEMPLATE_ID,
            $reservation->getProgram()->getManagingCompany()->getStaff(),
            ['reservationName' => $reservation->getName()],
            $output
        );
    }

    private function handleMailingToCr(Reservation $reservation, float $remainingHours, OutputInterface $output): void
    {
        // need to convert float to int to avoid sending mail every day
        $remainingHours = (int) $remainingHours;

        if (false === \in_array($remainingHours, self::CR_LIMIT_HOURS, true)) {
            return;
        }

        $this->sendMail(
            self::CR_TEMPLATE_ID,
            $reservation->getManagingCompany()->getStaff(),
            ['reservationName' => $reservation->getName()],
            $output
        );
    }

    /**
     * @param array|Staff[] $staffList
     */
    private function sendMail(int $templateId, array $staffList, array $vars, OutputInterface $output): void
    {
        foreach ($staffList as $staff) {
            if (false === $staff->isActive()) {
                continue;
            }

            if (
                \in_array($templateId, self::CR_TEMPLATE_IDS, true)
                && false === $this->hasReservationPermission($staff)
            ) {
                continue;
            }

            $user              = $staff->getUser();
            $vars['firstName'] = $user->getFirstName();
            $vars['lastName']  = $user->getLastName();

            try {
                $mailjetMessage = (new MailjetMessage())
                    ->to($user->getEmail())
                    ->setTemplateId($templateId)
                    ->setVars($vars)
                ;

                if ($output->isVerbose()) {
                    $recipients      = \array_keys($mailjetMessage->getTo());
                    $reservationName = $vars['reservationName'] ?? 'list';

                    $output->writeln(
                        \sprintf(
                            'Sending an email to %s for reservation %s',
                            \implode(' and ', $recipients),
                            $reservationName
                        )
                    );
                }

                if ($output->isVeryVerbose()) {
                    $output->writeln('Variables for message :');
                    foreach ($vars as $key => $value) {
                        if (\is_array($value)) {
                            $value = \json_encode($value, JSON_THROW_ON_ERROR);
                        }
                        $output->writeln("\t{$key} : {$value}");
                    }
                }

                $this->mailer->send($mailjetMessage);
            } catch (\Throwable $throwable) {
                $this->logger->error(
                    \sprintf(
                        'Remind expiring Reservation mail sending failed for %s with template id %d. Error: %s',
                        $user->getEmail(),
                        $templateId,
                        $throwable->getMessage()
                    ),
                    ['throwable' => $throwable]
                );
            }
            if ($output->isVerbose()) {
                $output->writeln('Email sent for ' . $user->getEmail());
            }
        }
    }

    private function hasReservationPermission(Staff $staff): bool
    {
        return $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_CREATE_RESERVATION)
            || $this->staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_EDIT_RESERVATION)
        ;
    }
}
