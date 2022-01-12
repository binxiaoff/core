<?php

declare(strict_types=1);

namespace KLS\Core\Command;

use KLS\Core\Mailer\MailjetMessage;
use KLS\Core\Repository\MessageStatusRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;

class RemindUnreadMessageCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'kls:core:message:unread:remind';

    private LoggerInterface $logger;
    private MailerInterface $mailer;
    private MessageStatusRepository $messageStatusRepository;

    public function __construct(
        LoggerInterface $logger,
        MailerInterface $mailer,
        MessageStatusRepository $messageStatusRepository
    ) {
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
                    new InputOption(
                        '--dry-run',
                        null,
                        InputOption::VALUE_NONE,
                        'Launch command on dry run mode with console display only.'
                    ),
                    new InputOption(
                        '--limit',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Set a limit of user to send messages.'
                    ),
                ])
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var ConsoleSectionOutput $section */
        $section = $output->section();
        $table   = $this->createTable($section);
        $dryRun  = $input->hasParameterOption('--dry-run');
        $limit   = $input->getParameterOption('--limit', null);

        $totalUnreadMessageByUsers = $this->messageStatusRepository->countUnreadMessageByRecipentForPeriod(
            $limit ? (int) $limit : $limit
        );

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
                $this->sendUnreadMessagesNotification(
                    $item['email'],
                    $item['first_name'],
                    $item['last_name'],
                    (int) $item['nb_messages_unread']
                );
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

    private function sendUnreadMessagesNotification(
        string $email,
        string $firstName,
        string $lastName,
        int $nbUnreadMessage
    ): void {
        $templateId = MailjetMessage::TEMPLATE_MESSAGE_UNREAD_USER_NOTIFICATION;

        try {
            $message = (new MailjetMessage())
                ->setTemplateId($templateId)
                ->setVars(\compact('firstName', 'lastName', 'nbUnreadMessage'))
                ->to($email)
            ;
            $this->mailer->send($message);
        } catch (\Throwable $throwable) {
            $this->logger->error(
                \sprintf(
                    'Unable to send unread message(s) email notification to %s with template id %d. Error: %s',
                    $email,
                    $templateId,
                    $throwable->getMessage()
                ),
                ['throwable' => $throwable]
            );
        }
    }
}
