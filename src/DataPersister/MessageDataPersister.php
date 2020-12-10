<?php

declare(strict_types=1);

namespace Unilend\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Unilend\Entity\Message;
use Unilend\Entity\MessageThread;
use Unilend\Entity\MessageStatus;
use Unilend\Repository\ProjectParticipationRepository;
use ApiPlatform\Core\Bridge\Doctrine\Common\DataPersister;

final class MessageDataPersister implements ContextAwareDataPersisterInterface
{
    /** @var ContextAwareDataPersisterInterface */
    private ContextAwareDataPersisterInterface $decoratedDataPersister;

    /** @var ProjectParticipationRepository */
    private ProjectParticipationRepository $projectParticipationRepository;

    /** @var ObjectManager */
    private EntityManagerInterface $entityManager;

    /**
     * MessageDataPersister constructor.
     *
     * @param ContextAwareDataPersisterInterface $decoratedDataPersister
     * @param ProjectParticipationRepository     $projectParticipationRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        ContextAwareDataPersisterInterface $decoratedDataPersister,
        ProjectParticipationRepository $projectParticipationRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->decoratedDataPersister         = $decoratedDataPersister;
        $this->projectParticipationRepository = $projectParticipationRepository;
        $this->entityManager                  = $entityManager;
    }

    /**
     * @param       $data
     * @param array $context
     *
     * @return bool
     */
    public function supports($data, array $context = []): bool
    {
        return $data instanceof Message;
    }

    /**
     * @param       $data
     * @param array $context
     *
     * @return mixed|object|Message|void
     *
     * @throws ORMException
     */
    public function persist($data, array $context = [])
    {
        if (($context['collection_operation_name'] ?? null) === 'post' && $data instanceof Message) {
            return $this->persistMessage($data);
        }
        return $data;
    }

    /**
     * @param       $data
     * @param array $context
     */
    public function remove($data, array $context = [])
    {
        // TODO: Implement remove() method.
    }

    /**
     * @param Message $message
     *
     * @return Message
     *
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function persistMessage(Message $message)
    {
        // If message is a broadcasted one, get each project projectParticipations and link a copy of this message to projectParticipation.thread
        if ($message->isBroadcasted()) {
            $project = $message->getMessageThread()->getProjectParticipation()->getProject();
            foreach ($project->getProjectParticipations() as $projectParticipation) {
                if ($projectParticipation->isActive() && $message->getMessageThread() !== $projectParticipation->getMessageThread()) {
                    $this->decoratedDataPersister->persist(new Message($message->getSender(), $projectParticipation->getMessageThread(), $message->getBody(), true));
                }
            }
        }
        $this->decoratedDataPersister->persist($message);

        return $message;
    }
}
