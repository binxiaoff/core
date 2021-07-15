<?php

declare(strict_types=1);

namespace Unilend\Core\Command;

use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Swift_Mailer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Core\Repository\MessageStatusRepository;
use Unilend\Core\SwiftMailer\MailjetMessage;

class UnreadMessageEmailNotificationCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'kls:message:unread_email_notification';

    private LoggerInterface $logger;
    private Swift_Mailer $mailer;
    private MessageStatusRepository $messageStatusRepository;

    public function __construct(LoggerInterface $logger, Swift_Mailer $mailer, MessageStatusRepository $messageStatusRepository)
    {
        parent::__construct();
        $this->logger                  = $logger;
        $this->mailer                  = $mailer;
        $this->messageStatusRepository = $messageStatusRepository;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send email to notify user for unread message(s)')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('--dry-run', null, InputOption::VALUE_NONE, 'Launch command on dry run mode with console display only.'),
                    new InputOption('--limit', null, InputOption::VALUE_OPTIONAL, 'Set a limit of user to send messages.'),
                ])
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        /** @var ConsoleSectionOutput $section */
        $section                   = $output->section();
        $table                     = $this->createTable($section);
        $dryRun                    = $input->hasParameterOption('--dry-run');
        $limit                     = $input->getParameterOption('--limit', null);
        $totalUnreadMessageByUsers = $this->messageStatusRepository->countUnreadMessageByRecipentForPeriod($limit ? (int) $limit : $limit);

        if ($dryRun && $totalUnreadMessageByUsers) {
            $table->render();
        }

        foreach ($totalUnreadMessageByUsers as $i => $item) {
            if ($dryRun) {
                $table->appendRow([
                    'userId'           => $item['id'],
                    'email'            => $item['email'],
                    'nbUnreadMessages' => $item['nb_messages_unread'],
                ]);

                continue;
            }

            if (0 < $item['nb_messages_unread']) {
                $this->sendUnreadMessagesNotification($item['email'], $item['first_name'], $item['last_name'], (int) $item['nb_messages_unread']);
                $this->messageStatusRepository->setMessageStatusesToUnreadNotified((int) $item['id']);
            }
        }

        return Command::SUCCESS;
    }

    private function createTable(OutputInterface $output): Table
    {
        $table = new Table($output);
        $table->setHeaders(['User id', 'User email', 'nb unread messages']);

        return $table;
    }

    private function sendUnreadMessagesNotification(string $email, string $firstName, string $lastName, int $nbUnreadMessage): void
    {
        $failedRecipient = [];

        try {
            $message = (new MailjetMessage())
                ->setTemplateId(MailjetMessage::TEMPLATE_MESSAGE_UNREAD_USER_NOTIFICATION)
                ->setVars(\compact('firstName', 'lastName', 'nbUnreadMessage'))
                ->setTo($email)
            ;
            if (0 === $this->mailer->send($message, $failedRecipient)) {
                throw new RuntimeException(\sprintf('Error on sending email to : "%s"', \implode(', ', $failedRecipient)));
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
}
