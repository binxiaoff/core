<?php

declare(strict_types=1);

namespace Unilend\Syndication\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Unilend\Core\Entity\Message;
use Unilend\Core\Repository\MessageRepository;
use Unilend\Core\Repository\MessageThreadRepository;

final class MessageDataPersister implements DataPersisterInterface
{
    /** @var MessageRepository */
    private MessageRepository $messageRepository;

    /** @var MessageThreadRepository */
    private MessageThreadRepository $messageThreadRepository;

    /**
     * MessageDataPersister constructor.
     *
     * @param MessageRepository       $messageRepository
     * @param MessageThreadRepository $messageThreadRepository
     */
    public function __construct(MessageRepository $messageRepository, MessageThreadRepository $messageThreadRepository)
    {
        $this->messageRepository        = $messageRepository;
        $this->messageThreadRepository  = $messageThreadRepository;

    }

    /**
     * @param $data
     *
     * @return bool
     */
    public function supports($data): bool
    {
        return ($data instanceof Message);
    }

    /**
     * @param $data
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return object|void
     */
    public function persist($data)
    {
        // If message is a broadcasted one, get each project projectParticipations and link a copy of this message to projectParticipation.thread
        if ($data->isBroadcast()) {
            $project = $data->getMessageThread()->getProjectParticipation()->getProject();
            foreach ($project->getProjectParticipations() as $projectParticipation) {
                $messageThread = $this->messageThreadRepository->findOneBy(['projectParticipation' => $projectParticipation]);
                if ($projectParticipation->isActive() && $data->getMessageThread() !== $messageThread) {
                    $message = (new Message($data->getSender(), $projectParticipation->getMessageThread(), $data->getBody()))->setBroadcast($data->getBroadcast());
                    $this->messageRepository->save($message);
                }
            }
        }
        $this->messageRepository->save($data);

        return $data;
    }

    /**
     * @param $data
     */
    public function remove($data)
    {
        // TODO: Implement remove() method.
    }
}
