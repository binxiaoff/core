<?php

declare(strict_types=1);

namespace Unilend\MessageHandler\Message;

use InvalidArgumentException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Unilend\Entity\Message;
use Unilend\Entity\MessageStatus;
use Unilend\Entity\ProjectParticipation;
use Unilend\Message\Message\MessageCreated;
use Unilend\Repository\MessageRepository;
use Unilend\Repository\MessageStatusRepository;
use Unilend\Repository\ProjectParticipationRepository;

class MessageCreatedHandler implements MessageHandlerInterface
{
    /**
     * @var MessageRepository
     */
    private MessageRepository $messageRepository;

    /**
     * @var MessageStatusRepository
     */
    private MessageStatusRepository $messageStatusRepository;

    /**
     * @var ProjectParticipationRepository
     */
    private ProjectParticipationRepository $projectParticipationRepository;

    /**
     * MessageCreatedHandler constructor.
     *
     * @param MessageRepository              $messageRepository
     * @param MessageStatusRepository        $messageStatusRepository
     * @param ProjectParticipationRepository $projectParticipationRepository
     */
    public function __construct(
        MessageRepository $messageRepository,
        MessageStatusRepository $messageStatusRepository,
        ProjectParticipationRepository $projectParticipationRepository
    ) {
        $this->messageRepository = $messageRepository;
        $this->messageStatusRepository = $messageStatusRepository;
        $this->projectParticipationRepository = $projectParticipationRepository;
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

        $projectParticipation = $this->projectParticipationRepository->findOneBy(['messageThread' => $message->getMessageThread()]);
        if (false === $projectParticipation instanceof ProjectParticipation) {
            throw new InvalidArgumentException(sprintf('There is no projectParticipation linked to messageThread with id %d', $message->getMessageThread()));
        }

        foreach ($projectParticipation->getProjectParticipationMembers() as $projectParticipationMember) {
            if ($message->getSender() !== $projectParticipationMember->getStaff()) {
                $messageStatus = new MessageStatus(MessageStatus::STATUS_UNREAD, $message, $projectParticipationMember->getStaff());
                $this->messageStatusRepository->persist($messageStatus);
            }
        }
        $this->messageStatusRepository->flush();
    }
}
