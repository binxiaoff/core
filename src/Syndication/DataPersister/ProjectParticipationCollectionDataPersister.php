<?php

declare(strict_types=1);

namespace Unilend\Syndication\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Unilend\Syndication\Entity\Request\ProjectParticipationCollection;
use Unilend\Syndication\Repository\ProjectParticipationRepository;

class ProjectParticipationCollectionDataPersister implements DataPersisterInterface
{
    private ProjectParticipationRepository $projectParticipationRepository;

    public function __construct(ProjectParticipationRepository $projectParticipationRepository)
    {
        $this->projectParticipationRepository = $projectParticipationRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data): bool
    {
        return $data instanceof ProjectParticipationCollection;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function persist($data): ProjectParticipationCollection
    {
        foreach ($data->getProjectParticipations() as $projectParticipation) {
            $this->projectParticipationRepository->persist($projectParticipation);
        }

        $this->projectParticipationRepository->flush();

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data): void
    {
        // remove is not supported
    }
}
