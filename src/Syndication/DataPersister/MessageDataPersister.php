<?php

declare(strict_types=1);

namespace Unilend\Syndication\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Core\Entity\Message;
use Unilend\Core\Repository\{MessageRepository, MessageThreadRepository};

final class MessageDataPersister implements DataPersisterInterface
{
    /** @var MessageRepository */
    private MessageRepository $messageRepository;

    /** @var MessageThreadRepository */
    private MessageThreadRepository $messageThreadRepository;

    /**
     * @param MessageRepository       $messageRepository
     * @param MessageThreadRepository $messageThreadRepository
     */
    public function __construct(MessageRepository $messageRepository, MessageThreadRepository $messageThreadRepository)
    {
        $this->messageRepository        = $messageRepository;
        $this->messageThreadRepository  = $messageThreadRepository;
    }

    /**
     * @param Message $data
     *
     * @return bool
     */
    public function supports($data): bool
    {
        return ($data instanceof Message);
    }

    /**
     * @param Message $data
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Message
     */
    public function persist($data): Message
    {
        // If message is a broadcast one, get each project projectParticipations and link a copy of this message to projectParticipation.thread
        if ($data->isBroadcast()) {
            $project = $data->getMessageThread()->getProjectParticipation()->getProject();
            foreach ($project->getProjectParticipations() as $projectParticipation) {
                $messageThread = $this->messageThreadRepository->findOneBy(['projectParticipation' => $projectParticipation]);
                if ($projectParticipation->isActive() && $data->getMessageThread() !== $messageThread) {
                    $message = (new Message($data->getSender(), $messageThread, $data->getBody()))->setBroadcast($data->getBroadcast());
                    $this->messageRepository->save($message);
                }
            }
        }
        $this->messageRepository->save($data);

        return $data;
    }

    /**
     * @param Message $data
     */
    public function remove($data): void
    {
        // TODO: Implement remove() method.
    }
}
