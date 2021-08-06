<?php

declare(strict_types=1);

namespace Unilend\Core\MessageHandler\Message;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use InvalidArgumentException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Core\Entity\Message;
use Unilend\Core\Entity\MessageStatus;
use Unilend\Core\Message\Message\MessageCreated;
use Unilend\Core\Repository\MessageRepository;
use Unilend\Core\Repository\MessageStatusRepository;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Service\Project\ProjectManager;

class MessageCreatedHandler implements MessageHandlerInterface
{
    private MessageRepository $messageRepository;

    private ProjectManager $projectManager;

    private MessageStatusRepository $messageStatusRepository;

    /**
     * MessageCreatedHandler constructor.
     */
    public function __construct(
        MessageRepository $messageRepository,
        ProjectManager $projectManager,
        MessageStatusRepository $messageStatusRepository
    ) {
        $this->messageRepository       = $messageRepository;
        $this->projectManager          = $projectManager;
        $this->messageStatusRepository = $messageStatusRepository;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function __invoke(MessageCreated $messageCreated)
    {
        $message = $this->messageRepository->find($messageCreated->getMessageId());
        if (false === $message instanceof Message) {
            throw new InvalidArgumentException(\sprintf('The message with id %d does not exist', $messageCreated->getMessageId()));
        }

        $projectParticipation = $message->getMessageThread()->getProjectParticipation();
        if (false === $projectParticipation instanceof ProjectParticipation) {
            throw new InvalidArgumentException(\sprintf('No participation related to the message thread with id %d.', $messageCreated->getMessageId()));
        }

        $project                      = $projectParticipation->getProject();
        $projectParticipationMembers  = $message->getMessageThread()->getProjectParticipation()->getProjectParticipationMembers();
        $participationArrangerMembers = $projectParticipation->getProject()->getArrangerProjectParticipation()->getActiveProjectParticipationMembers();

        // If the message is sent by arranger to the participant
        if ($message->getSender()->getCompany() === $project->getArranger()) {
            foreach ($projectParticipationMembers as $projectParticipationMember) {
                if (false === $projectParticipationMember->isArchived() && $projectParticipationMember->getStaff()->isActive()) {
                    $this->messageStatusRepository->persist(new MessageStatus($message, $projectParticipationMember->getStaff()));
                }
            }
        } else {
            // If the message is sent by a participant to the arranger
            foreach ($participationArrangerMembers as $participationArrangerMember) {
                if (false === $participationArrangerMember->isArchived() && $participationArrangerMember->getStaff()->isActive()) {
                    $this->messageStatusRepository->persist(new MessageStatus($message, $participationArrangerMember->getStaff()));
                }
            }
        }
        $this->messageStatusRepository->flush();
    }
}
