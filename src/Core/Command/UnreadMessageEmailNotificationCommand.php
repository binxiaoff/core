<?php

declare(strict_types=1);

namespace Unilend\Core\Command;

use DateTimeImmutable;
use Doctrine\ORM\{NoResultException, NonUniqueResultException};
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Swift_Mailer;
use Symfony\Component\Console\{
    Command\Command,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface,
    Question\ConfirmationQuestion,
    Style\SymfonyStyle
};
use Unilend\Core\Repository\{MessageStatusRepository, UserRepository};
use Unilend\Core\SwiftMailer\MailjetMessage;

class UnreadMessageEmailNotificationCommand extends Command
{
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

    /** @var array */
    private array $dryRunOutputRows = [];

    /** @var int */
    private int $dryRunCurrentPageNum = 0;

    /** @var int */
    private int $dryRunOutputNbRowsByPage = 2;

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
            ->addOption('--dry-run', null, InputOption::VALUE_NONE, 'Launch command on dry run mode with console display only.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        /** @var SymfonyStyle */
        $io   = new SymfonyStyle($input, $output);
        $to   = (new DateTimeImmutable())->setTime(6, 0);
        $from = $to->modify('-24 hours');

        $totalUnreadMessageByUsers = $this->messageStatusRepository->countUnreadMessageByRecipentForPeriod($from, $to);

        if ($input->hasParameterOption('--dry-run')) {
            $nbUserWithUnreadMessages = count($totalUnreadMessageByUsers);
            $dryRunNbPage             = intdiv($nbUserWithUnreadMessages, $this->dryRunOutputNbRowsByPage)
                + (($nbUserWithUnreadMessages % $this->dryRunOutputNbRowsByPage) !== 0 ? 1 : 0);
        }

        foreach ($totalUnreadMessageByUsers as $i => $totalUnreadMessageByUser) {
            $failedRecipient = [];
            try {
                if (0 < $totalUnreadMessageByUser['nb_messages_unread']) {
                    if ($input->hasParameterOption('--dry-run')) {
                        $this->displayDryRunOutput($totalUnreadMessageByUser, $dryRunNbPage, $io, $input, $output);
                        continue;
                    }
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
     * @param array           $totalUnreadMessageByUser
     * @param int             $dryRunNbPage
     * @param SymfonyStyle    $io
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     */
    private function displayDryRunOutput(array $totalUnreadMessageByUser, int $dryRunNbPage, SymfonyStyle $io, InputInterface $input, OutputInterface $output): ?int
    {
        $nbUserUnreadMessages = (int) $totalUnreadMessageByUser['nb_messages_unread'];
        $this->dryRunOutputRows[] = [
            'userId'           => $totalUnreadMessageByUser['id'],
            'email'            => $totalUnreadMessageByUser['email'],
            'nbUnreadMessages' => $nbUserUnreadMessages,
        ];

        if ($this->dryRunOutputNbRowsByPage === count($this->dryRunOutputRows) || $this->dryRunCurrentPageNum === ($dryRunNbPage - 1)) {
            // Keep current page number
            $this->dryRunCurrentPageNum++;
            $question = new ConfirmationQuestion(
                sprintf('%s/%s - Display next page ? (y|n) ', $this->dryRunCurrentPageNum, $dryRunNbPage),
                false,
                '/^(y)/i'
            );

            $io->table(
                ['User id', 'User email', 'nb unread messages'],
                $this->dryRunOutputRows
            );

            // Reset after each page displayed
            $this->dryRunOutputRows = [];

            if ($this->dryRunCurrentPageNum === $dryRunNbPage || false === $this->getHelper('question')->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }
        }
        // Clear screen between each page
        $output->write(sprintf("\033\143"));

        return Command::SUCCESS;
    }
}
