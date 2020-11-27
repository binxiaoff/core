<?php

declare(strict_types=1);

namespace Unilend\Syndication\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\Request\ProjectParticipationCollection;
use Unilend\Repository\ProjectParticipationRepository;

class ProjectParticipationCollectionDataPersister implements DataPersisterInterface
{
    /** @var ProjectParticipationRepository */
    private ProjectParticipationRepository $projectParticipationRepository;

    /**
     * @param ProjectParticipationRepository $projectParticipationRepository
     */
    public function __construct(ProjectParticipationRepository $projectParticipationRepository)
    {
        $this->projectParticipationRepository = $projectParticipationRepository;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
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
