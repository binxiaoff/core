<?php

declare(strict_types=1);

namespace KLS\Core\MessageHandler\Message;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use InvalidArgumentException;
use KLS\Core\Entity\Message;
use KLS\Core\Entity\MessageStatus;
use KLS\Core\Message\Message\MessageCreated;
use KLS\Core\Repository\MessageRepository;
use KLS\Core\Repository\MessageStatusRepository;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MessageCreatedHandler implements MessageHandlerInterface
{
    private MessageRepository $messageRepository;
    private MessageStatusRepository $messageStatusRepository;

    public function __construct(
        MessageRepository $messageRepository,
        MessageStatusRepository $messageStatusRepository
    ) {
        $this->messageRepository       = $messageRepository;
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
            throw new InvalidArgumentException(\sprintf(
                'The message with id %d does not exist',
                $messageCreated->getMessageId()
            ));
        }

        $projectParticipation = $message->getMessageThread()->getProjectParticipation();
        if (false === $projectParticipation instanceof ProjectParticipation) {
            throw new InvalidArgumentException(\sprintf(
                'No participation related to the message thread with id %d.',
                $messageCreated->getMessageId()
            ));
        }

        $project                     = $projectParticipation->getProject();
        $projectParticipationMembers = $message->getMessageThread()->getProjectParticipation()
            ->getProjectParticipationMembers()
        ;

        $participationArrangerMembers = $projectParticipation->getProject()->getArrangerProjectParticipation()
            ->getActiveProjectParticipationMembers()
        ;

        // If the message is sent by arranger to the participant
        if ($message->getSender()->getCompany() === $project->getArranger()) {
            foreach ($projectParticipationMembers as $projectParticipationMember) {
                if (
                    false === $projectParticipationMember->isArchived()
                    && $projectParticipationMember->getStaff()->isActive()
                ) {
                    $this->messageStatusRepository->persist(new MessageStatus(
                        $message,
                        $projectParticipationMember->getStaff()
                    ));
                }
            }
        } else {
            // If the message is sent by a participant to the arranger
            foreach ($participationArrangerMembers as $participationArrangerMember) {
                if (
                    false === $participationArrangerMember->isArchived()
                    && $participationArrangerMember->getStaff()->isActive()
                ) {
                    $this->messageStatusRepository->persist(new MessageStatus(
                        $message,
                        $participationArrangerMember->getStaff()
                    ));
                }
            }
        }
        $this->messageStatusRepository->flush();
    }
}
