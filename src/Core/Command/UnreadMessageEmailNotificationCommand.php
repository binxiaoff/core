<?php

declare(strict_types=1);

namespace Unilend\Core\Command;

use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Core\Repository\MessageStatusRepository;
use Unilend\Core\Repository\StaffRepository;

class UnreadMessageEmailNotificationCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'kls:message:unread_email_notification';

    /** @var StaffRepository */
    private StaffRepository $staffRepository;

    /** @var MessageStatusRepository */
    private MessageStatusRepository $messageStatusRepository;

    /**
     * UnreadMessageEmailNotificationCommand constructor.
     *
     * @param StaffRepository         $staffRepository
     * @param MessageStatusRepository $messageStatusRepository
     */
    public function __construct(StaffRepository $staffRepository, MessageStatusRepository $messageStatusRepository)
    {
        $this->staffRepository = $staffRepository;
        $this->messageStatusRepository = $messageStatusRepository;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Send email to notify user for unread messages');
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        // Get all unread message for staff
        $unreadMessages = $this->messageStatusRepository->getTotalUnreadMessageForDateBetween(new DateTimeImmutable('today'), new DateTimeImmutable('tomorrow -1 second'));

        return 0;
    }
}
