<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Message;

use InvalidArgumentException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Entity\Message;
use Unilend\Entity\MessageStatus;
use Unilend\Entity\ProjectParticipation;
use Unilend\Message\Message\MessageCreated;
use Unilend\Repository\MessageRepository;
use Unilend\Repository\ProjectParticipationRepository;

class MessageCreatedHandler implements MessageHandlerInterface
{
    /** @var MessageRepository */
    private MessageRepository $messageRepository;

    /** @var ProjectParticipationRepository */
    private ProjectParticipationRepository $projectParticipationRepository;

    /** @var EntityManagerInterface */
    private EntityManagerInterface $entityManager;

    /**
     * MessageCreatedHandler constructor.
     *
     * @param MessageRepository              $messageRepository
     * @param ProjectParticipationRepository $projectParticipationRepository
     * @param EntityManagerInterface         $entityManager
     */
    public function __construct(
        MessageRepository $messageRepository,
        ProjectParticipationRepository $projectParticipationRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->messageRepository              = $messageRepository;
        $this->projectParticipationRepository = $projectParticipationRepository;
        $this->entityManager                  = $entityManager;
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

        $projectParticipation = $message->getMessageThread()->getProjectParticipation();

        // Add messageStatus unread for other arranger member not current arranger if the sender is an arranger member
        $participationArranger = $projectParticipation->getProject()->getArrangerProjectParticipation();
        $arrangerParticipationMembers = $participationArranger->getActiveProjectParticipationMembers();
        foreach($participationArranger->getActiveProjectParticipationMembers() as $arranger){
            if ($message->getSender() !== $arranger->getStaff()) {
                $this->entityManager->persist(new MessageStatus($message, $arranger->getStaff()));
            }
        }

        // Add messageStatus unread for every projectParticipationMembers that received the message
        foreach ($projectParticipation->getProjectParticipationMembers() as $projectParticipationMember) {
            if ($message->getSender() !== $projectParticipationMember->getStaff()) {
                $this->entityManager->persist(new MessageStatus($message, $projectParticipationMember->getStaff()));
            }
        }
        $this->entityManager->persist(new MessageStatus($message, $message->getSender(), MessageStatus::STATUS_READ));
        $this->entityManager->flush();
    }

}

