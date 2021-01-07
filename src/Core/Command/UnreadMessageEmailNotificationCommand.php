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
use Unilend\Core\Repository\{MessageStatusRepository, StaffRepository};
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
    private LoggerInterface $logger;

    /** @var bool */
    private bool $dryRun;

    /** @var SymfonyStyle */
    private SymfonyStyle $io;

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
            $dryRunOutputRows               = [];
            $offset                         = $i * self::BATCH_SIZE;
            $totalUnreadMessageByRecipients = $this->messageStatusRepository->getTotalUnreadMessageByRecipientForDateBetween($from, $to, self::BATCH_SIZE, $offset);

            foreach ($totalUnreadMessageByRecipients as $totalUnreadMessageByRecipient) {
                $failedRecipient      = [];
                $staff                = $this->staffRepository->findOneBy(['id' => $totalUnreadMessageByRecipient['recipient']]);
                $user                 = $staff->getUser();
                $nbUserUnreadMessages = (int) $totalUnreadMessageByRecipient['unread'];

                if ($this->dryRun) {
                    $dryRunOutputRows[] = [
                        'userId'           => $staff->getUser()->getId(),
                        'email'            => $user->getEmail(),
                        'nbUnreadMessages' => $nbUserUnreadMessages,
                    ];
                    continue;
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
                        throw new RuntimeException(sprintf('Error on sending email to : "%s"', implode(', ', $failedRecipient)));
                    }
                    $this->messageStatusRepository->setMessageStatusesToNotified($staff, $from, $to);
                } catch (Exception $exception) {
                    $this->logger->error('Unable to send unread message(s) email notification with error : ' . $exception->getMessage(), [
                        'class'    => __CLASS__,
                        'function' => __FUNCTION__,
                        'file'     => $exception->getFile(),
                        'line'     => $exception->getLine(),
                    ]);
                }
            }

            if ($this->dryRun && Command::SUCCESS === $this->displayDryRunOutput($dryRunOutputRows, $i + 1, $nbLoop, $input, $output)) {
                return Command::SUCCESS;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param array           $dryRunOutputRows
     * @param int             $currentPageNum
     * @param int             $totalPages
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     */
    private function displayDryRunOutput(array $dryRunOutputRows, int $currentPageNum, int $totalPages, InputInterface $input, OutputInterface $output): ?int
    {
        if (empty($dryRunOutputRows)) {
            return Command::SUCCESS;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            sprintf('%s/%s - Display next page ? (y|n) ', $currentPageNum, $totalPages),
            false,
            '/^(y)/i'
        );

        $this->io->table(
            ['User id', 'User email', 'nb unread messages'],
            $dryRunOutputRows
        );

        if ($currentPageNum === $totalPages || !$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        return null;
    }
}
