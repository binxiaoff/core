<?php

declare(strict_types=1);

namespace Unilend\Core\Command;

use DateTimeImmutable;
use Doctrine\ORM\{NoResultException, NonUniqueResultException};
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Swift_Mailer;
use Symfony\Component\Console\{Command\Command, Input\InputArgument, Input\InputInterface, Output\OutputInterface, Question\ConfirmationQuestion, Style\SymfonyStyle};
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Core\Repository\MessageStatusRepository;
use Unilend\Core\Repository\StaffRepository;
use Unilend\Core\SwiftMailer\MailjetMessage;

class UnreadMessageEmailNotificationCommand extends Command
{
    private const BATCH_SIZE = 20;

    /** @var string */
    protected static $defaultName = 'kls:message:unread_email_notification';

    /** @var StaffRepository */
    private StaffRepository $staffRepository;

    /** @var MessageStatusRepository */
    private MessageStatusRepository $messageStatusRepository;

    /** @var Swift_Mailer */
    private Swift_Mailer $mailer;

    /** @var LoggerInterface */
    private $logger;

    /** @var bool */
    private $dryRun;

    /** @var SymfonyStyle */
    private $io;

    /**
     * UnreadMessageEmailNotificationCommand constructor.
     *
     * @param StaffRepository         $staffRepository
     * @param MessageStatusRepository $messageStatusRepository
     * @param Swift_Mailer            $mailer
     * @param LoggerInterface         $logger
     */
    public function __construct(StaffRepository $staffRepository, MessageStatusRepository $messageStatusRepository, Swift_Mailer $mailer, LoggerInterface $logger)
    {
        $this->staffRepository         = $staffRepository;
        $this->messageStatusRepository = $messageStatusRepository;
        $this->mailer                  = $mailer;
        $this->logger                  = $logger;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Send email to notify user for unread message(s)')
            ->addArgument('dryRun', InputArgument::OPTIONAL, 'Launch command on dry run mode with console display', true);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dryRun = (bool) $input->getArgument('dryRun');
        $this->io     = new SymfonyStyle($input, $output);

        parent::initialize($input, $output);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     *
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $to                        = new DateTimeImmutable();
        $from                      = $to->modify('-24 hours');
        $nbStaffWithUnreadMessages = $this->messageStatusRepository->countTotalRecipientUnreadMessageForDateBetween($from, $to);
        $nbLoop                    = intval(ceil($nbStaffWithUnreadMessages / self::BATCH_SIZE));

        for ($i = 0; $i <= $nbLoop; $i++) {
            $offset = $i * self::BATCH_SIZE;
            $dryRunDataToDisplay = [];
            $totalUnreadMessageByRecipients = $this->messageStatusRepository->getTotalUnreadMessageByRecipientForDateBetween($from, $to, self::BATCH_SIZE, $offset);

            foreach ($totalUnreadMessageByRecipients as $totalUnreadMessageByRecipient) {
                $failedRecipient      = [];
                $staff                = $this->staffRepository->findOneBy(['id' => $totalUnreadMessageByRecipient['recipient']]);
                $user                 = ($staff instanceof Staff) ? $staff->getUser() : null;
                $nbUserUnreadMessages = (int) $totalUnreadMessageByRecipient['unread'];

                if ($this->dryRun && $user instanceof User) {
                    $dryRunDataToDisplay[] = [
                        'staffId'          => $staff->getId(),
                        'email'            => $user->getEmail(),
                        'nbUnreadMessages' => $nbUserUnreadMessages,
                    ];
                }

                try {
                    $message = (new MailjetMessage())
                        ->setTemplateId(MailjetMessage::TEMPLATE_MESSAGE_UNREAD_USER_NOTIFICATION)
                        ->setVars([
                            'firstName'       => $user->getFirstName(),
                            'lastName'        => $user->getLastName(),
                            'nbUnreadMessage' => $nbUserUnreadMessages,
                        ])
                        ->setTo($user->getEmail())
                    ;
                    if (0 === $this->mailer->send($message, $failedRecipient)) {
                        throw new RuntimeException(sprintf('Error on sending email to : "%s"', implode(',', $failedRecipient)));
                    }
                } catch (Exception $exception) {
                    $this->logger->error('Unable to send unread message(s) email notification with error : ' . $exception->getMessage(), [
                        'class'    => __CLASS__,
                        'function' => __FUNCTION__,
                        'file'     => $exception->getFile(),
                        'line'     => $exception->getLine(),
                    ]);
                }
            }

            if ($this->dryRun) {
                if (empty($dryRunDataToDisplay) || Command::SUCCESS === $this->displayOutput($dryRunDataToDisplay, $nbStaffWithUnreadMessages, $input, $output)) {
                    return Command::SUCCESS;
                }
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param array           $dryRunDataToDisplay
     * @param int             $nbTotalTodisplay
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    private function displayOutput(array $dryRunDataToDisplay, int $nbTotalTodisplay, InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'Display next page ? (y|n) ',
            false,
            '/^(y)/i'
        );

        $this->io->table(
            ['staff id', 'user email', 'nb unread messages'],
            $dryRunDataToDisplay
        );

        if (count($dryRunDataToDisplay) === $nbTotalTodisplay || !$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }
    }
}
