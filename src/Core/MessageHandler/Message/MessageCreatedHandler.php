<?php

declare(strict_types=1);

namespace Unilend\Core\MessageHandler\Message;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use InvalidArgumentException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Core\Entity\{Message, MessageStatus};
use Unilend\Core\Message\Message\MessageCreated;
use Unilend\Core\Repository\{MessageRepository, MessageStatusRepository};
use Unilend\Syndication\Service\Project\ProjectManager;

class MessageCreatedHandler implements MessageHandlerInterface
{
    /** @var MessageRepository */
    private MessageRepository $messageRepository;

    /** @var ProjectManager */
    private ProjectManager $projectManager;

    /** @var MessageStatusRepository */
    private MessageStatusRepository $messageStatusRepository;

    /**
     * MessageCreatedHandler constructor.
     *
     * @param MessageRepository       $messageRepository
     * @param ProjectManager          $projectManager
     * @param MessageStatusRepository $messageStatusRepository
     */
    public function __construct(
        MessageRepository $messageRepository,
        ProjectManager $projectManager,
        MessageStatusRepository $messageStatusRepository
    ) {
        $this->messageRepository              = $messageRepository;
        $this->projectManager                 = $projectManager;
        $this->messageStatusRepository        = $messageStatusRepository;
    }

    /**
     * @param MessageCreated $messageCreated
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function __invoke(MessageCreated $messageCreated)
    {
        $message = $this->messageRepository->find($messageCreated->getMessageId());
        if (false === $message instanceof Message) {
            throw new InvalidArgumentException(sprintf('The message with id %d does not exist', $messageCreated->getMessageId()));
        }

        $projectParticipation         = $message->getMessageThread()->getProjectParticipation();
        $projectParticipationMembers  = $message->getMessageThread()->getProjectParticipation()->getProjectParticipationMembers();
        $participationArrangerMembers = $projectParticipation->getProject()->getArrangerProjectParticipation()->getActiveProjectParticipationMembers();

        // Add messageStatus unread for every projectParticipationMembers that received the message
        foreach ($projectParticipationMembers as $projectParticipationMember) {
            if (
                $projectParticipationMember->getStaff()->getCompany() !== $projectParticipationMember->getProjectParticipation()->getProject()->getArranger()
                && $message->getSender() !== $projectParticipationMember->getStaff()
            ) {
                $this->messageStatusRepository->save(new MessageStatus($message, $projectParticipationMember->getStaff()));
            }
        }

        // Message sender is not an arranger, we have to set the message as unread for each arranger member
        if (false === $this->projectManager->isArranger($projectParticipation->getProject(), $message->getSender())) {
            foreach ($participationArrangerMembers as $participationArrangerMember) {
                $this->messageStatusRepository->save(new MessageStatus($message, $participationArrangerMember->getStaff()));
            }
        }
        $this->messageStatusRepository->save(new MessageStatus($message, $message->getSender(), MessageStatus::STATUS_READ));
    }
}
