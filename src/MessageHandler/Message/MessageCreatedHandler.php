<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Message;

use InvalidArgumentException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Entity\Message;
use Unilend\Entity\MessageStatus;
use Unilend\Message\Message\MessageCreated;
use Unilend\Repository\MessageRepository;
use Unilend\Repository\{MessageStatusRepository, ProjectParticipationRepository};
use Unilend\Service\Project\ProjectManager;

class MessageCreatedHandler implements MessageHandlerInterface
{
    /** @var MessageRepository */
    private MessageRepository $messageRepository;

    /** @var ProjectParticipationRepository */
    private ProjectParticipationRepository $projectParticipationRepository;

    /** @var ProjectManager */
    private ProjectManager $projectManager;

    /** @var MessageStatusRepository */
    private MessageStatusRepository $messageStatusRepository;

    /**
     * MessageCreatedHandler constructor.
     *
     * @param MessageRepository              $messageRepository
     * @param ProjectParticipationRepository $projectParticipationRepository
     * @param ProjectManager                 $projectManager
     * @param MessageStatusRepository        $messageStatusRepository
     */
    public function __construct(
        MessageRepository $messageRepository,
        ProjectParticipationRepository $projectParticipationRepository,
        ProjectManager $projectManager,
        MessageStatusRepository $messageStatusRepository
    ) {
        $this->messageRepository              = $messageRepository;
        $this->projectParticipationRepository = $projectParticipationRepository;
        $this->projectManager                 = $projectManager;
        $this->messageStatusRepository        = $messageStatusRepository;
    }

    /**
     * @param MessageCreated $messageCreated
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
            if ($message->getSender() !== $projectParticipationMember->getStaff()) {
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
