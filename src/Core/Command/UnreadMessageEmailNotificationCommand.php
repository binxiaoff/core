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
use Unilend\Core\Repository\{MessageStatusRepository, UserRepository};
use Unilend\Core\SwiftMailer\MailjetMessage;

class UnreadMessageEmailNotificationCommand extends Command
{
    private const BATCH_SIZE = 2;

    /** @var string */
    protected static $defaultName = 'kls:message:unread_email_notification';

    /** @var UserRepository */
    private UserRepository $userRepository;

    /** @var MessageStatusRepository */
    private MessageStatusRepository $messageStatusRepository;

    /** @var Swift_Mailer */
    private Swift_Mailer $mailer;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /**
     * UnreadMessageEmailNotificationCommand constructor.
     *
     * @param UserRepository          $userRepository
     * @param MessageStatusRepository $messageStatusRepository
     * @param Swift_Mailer            $mailer
     * @param LoggerInterface         $logger
     */
    public function __construct(UserRepository $userRepository, MessageStatusRepository $messageStatusRepository, Swift_Mailer $mailer, LoggerInterface $logger)
    {
        $this->userRepository          = $userRepository;
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
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     *
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $dryRun = (bool) $input->getArgument('dryRun');
        $to     = new DateTimeImmutable();
        $from   = $to->modify('-24 hours');

        if ($dryRun) {
            return $this->displayDryRunOutput($input, $output, $from, $to);
        }

        $totalUnreadMessageByUsers = $this->messageStatusRepository->countUnreadMessageByRecipentForPeriod($from, $to);
        foreach ($totalUnreadMessageByUsers as $totalUnreadMessageByUser) {
            $failedRecipient = [];
            try {
                if (0 < $totalUnreadMessageByUser['nb_messages_unread']) {
                    $message = (new MailjetMessage())
                        ->setTemplateId(MailjetMessage::TEMPLATE_MESSAGE_UNREAD_USER_NOTIFICATION)
                        ->setVars([
                            'firstName'       => $totalUnreadMessageByUser['first_name'],
                            'lastName'        => $totalUnreadMessageByUser['last_name'],
                            'nbUnreadMessage' => $totalUnreadMessageByUser['nb_messages_unread'],
                        ])
                        ->setTo($totalUnreadMessageByUser['email'])
                    ;
                    if (0 === $this->mailer->send($message, $failedRecipient)) {
                        throw new RuntimeException(sprintf('Error on sending email to : "%s"', implode(', ', $failedRecipient)));
                    }
                    $this->messageStatusRepository->setMessageStatusesToUnreadNotified((int) $totalUnreadMessageByUser['id'], $from, $to);
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

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface    $input
     * @param OutputInterface   $output
     * @param DateTimeImmutable $from
     * @param DateTimeImmutable $to
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     *
     * @return int|null
     */
    private function displayDryRunOutput(InputInterface $input, OutputInterface $output, DateTimeImmutable $from, DateTimeImmutable $to): ?int
    {
        /** @var SymfonyStyle */
        $io                        = new SymfonyStyle($input, $output);
        $nbUserWithUnreadMessages  = $this->messageStatusRepository->countRecipientsWithUnreadMessageForPeriod($from, $to);
        $nbLoop                    = intdiv($nbUserWithUnreadMessages, self::BATCH_SIZE) + (($nbUserWithUnreadMessages % self::BATCH_SIZE) !== 0 ? 1 : 0);
        $helper                    = $this->getHelper('question');

        for ($i = 0; $i <= $nbLoop; $i++) {
            $currentPageNum            = $i + 1;
            $dryRunOutputRows          = [];
            $offset                    = $i * self::BATCH_SIZE;
            $totalUnreadMessageByUsers = $this->messageStatusRepository->countUnreadMessageByRecipentForPeriod($from, $to, self::BATCH_SIZE, $offset);
            foreach ($totalUnreadMessageByUsers as $totalUnreadMessageByUser) {
                $nbUserUnreadMessages = (int) $totalUnreadMessageByUser['nb_messages_unread'];
                $dryRunOutputRows[] = [
                    'userId'           => $totalUnreadMessageByUser['id'],
                    'email'            => $totalUnreadMessageByUser['email'],
                    'nbUnreadMessages' => $nbUserUnreadMessages,
                ];
            }

            $question = new ConfirmationQuestion(
                sprintf('%s/%s - Display next page ? (y|n) ', $currentPageNum, $nbLoop),
                false,
                '/^(y)/i'
            );

            $io->table(
                ['User id', 'User email', 'nb unread messages'],
                $dryRunOutputRows
            );

            if ($currentPageNum === $nbLoop || !$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }

            // Clear screen between each page
            $output->write(sprintf("\033\143"));
        }

        return null;
    }
}
