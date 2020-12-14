<?php

declare(strict_types=1);

namespace Unilend\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Unilend\Entity\Message;
use Unilend\Repository\ProjectParticipationRepository;

final class MessageDataPersister implements ContextAwareDataPersisterInterface
{
    /** @var ContextAwareDataPersisterInterface */
    private ContextAwareDataPersisterInterface $decoratedDataPersister;

    /** @var ProjectParticipationRepository */
    private ProjectParticipationRepository $projectParticipationRepository;

    /**
     * MessageDataPersister constructor.
     *
     * @param ContextAwareDataPersisterInterface $decoratedDataPersister
     * @param ProjectParticipationRepository     $projectParticipationRepository
     */
    public function __construct(
        ContextAwareDataPersisterInterface $decoratedDataPersister,
        ProjectParticipationRepository $projectParticipationRepository
    ) {
        $this->decoratedDataPersister         = $decoratedDataPersister;
        $this->projectParticipationRepository = $projectParticipationRepository;
    }

    /**
     * @param       $data
     * @param array $context
     *
     * @return bool
     */
    public function supports($data, array $context = []): bool
    {
        return (($context['collection_operation_name'] ?? null) === 'post' && $data instanceof Message);
    }

    /**
     * @param       $data
     * @param array $context
     *
     * @return object|void
     */
    public function persist($data, array $context = [])
    {
        // If message is a broadcasted one, get each project projectParticipations and link a copy of this message to projectParticipation.thread
        if ($data->isBroadcasted()) {
            $project = $data->getMessageThread()->getProjectParticipation()->getProject();
            foreach ($project->getProjectParticipations() as $projectParticipation) {
                if ($projectParticipation->isActive() && $data->getMessageThread() !== $projectParticipation->getMessageThread()) {
                    $this->decoratedDataPersister->persist(new Message($data->getSender(), $projectParticipation->getMessageThread(), $data->getBody(), true));
                }
            }
        }
        $this->decoratedDataPersister->persist($data);

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
}
