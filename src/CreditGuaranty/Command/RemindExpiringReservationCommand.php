<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Command;

use DateTime;
use InvalidArgumentException;
use JsonException;
use Swift_Mailer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\CompanyRepository;
use Unilend\Core\SwiftMailer\MailjetMessage;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Repository\ReservationRepository;

class RemindExpiringReservationCommand extends Command
{
    private const CASA_LIMIT_48_HOURS = 48;

    protected static $defaultName = 'kls:reservation:expiring:remind';

    private OutputInterface $output;
    private CompanyRepository $companyRepository;
    private ReservationRepository $reservationRepository;
    private Swift_Mailer $mailer;

    public function __construct(
        CompanyRepository $companyRepository,
        ReservationRepository $reservationRepository,
        Swift_Mailer $mailer
    ) {
        parent::__construct();

        $this->companyRepository     = $companyRepository;
        $this->reservationRepository = $reservationRepository;
        $this->mailer                = $mailer;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send about to expire reservation reminder mails')
            ->addOption('casa', null, InputOption::VALUE_NONE, 'Send reminder mails to CASA users')
            ->setHelp('kls:reservation:expiring:remind --casa')
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
        $casaOption = $input->getOption('casa');

        if (false === $casaOption) {
            throw new InvalidArgumentException('This command requires one option among them : --casa');
        }

        /** @var Reservation $reservation */
        foreach ($this->reservationRepository->findAll() as $reservation) {
            if ($reservation->isFormalized() || $reservation->isArchived() || $reservation->isRefusedByManagingCompany()) {
                continue;
            }

            $reservationDate = $reservation->getCurrentStatus()->getAdded();
            $nowDate         = new DateTime('now');
            $intervalDate    = $nowDate->diff($reservationDate);

            if (0 < $intervalDate->y && 0 < $intervalDate->m) {
                continue;
            }

            $daysInHours    = \bcmul((string) $intervalDate->d, '24', 2);
            $minutesInHours = \bcdiv((string) $intervalDate->i, '60', 2);
            $secondsInHours = \bcdiv((string) $intervalDate->s, '3600', 2);
            $remainingHours = \bcadd(\bcadd(\bcadd($daysInHours, (string) $intervalDate->h, 2), $minutesInHours, 2), $secondsInHours, 2);

            if ($casaOption) {
                $this->handleCasaMailing($reservation, (float) $remainingHours);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @throws JsonException
     */
    private function handleCasaMailing(Reservation $reservation, float $remainingHours): void
    {
        if ($remainingHours > self::CASA_LIMIT_48_HOURS) {
            return;
        }

        /** @var Company $company */
        $company    = $this->companyRepository->findOneBy(['shortCode' => Company::SHORT_CODE_CASA]);
        $templateId = MailjetMessage::TEMPLATE_CREDIT_GUARANTY_REMIND_EXPIRING_RESERVATION_CASA;

        foreach ($company->getStaff() as $staff) {
            if (false === $staff->isActive()) {
                continue;
            }

            $user = $staff->getUser();
            $vars = [
                'reservationName' => $reservation->getName(),
                'firstName'       => $user->getFirstName(),
                'lastName'        => $user->getLastName(),
            ];
            $mailjetMessage = $this->createMessage($user, $templateId, $vars);

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
            $this->output->writeln(\sprintf('Sending an email to %s for reservation %s', \implode(' and ', $recipients), $vars['reservationName']));
        }

        if ($this->output->isVeryVerbose()) {
            $this->output->writeln('Variables for message :');
            foreach ($vars as $key => $value) {
                $this->output->writeln("\t{$key} : {$value}");
            }
        }

        $this->mailer->send($mailjetMessage);

        if ($this->output->isVerbose()) {
            $this->output->writeln('Email sent');
        }
    }
}
