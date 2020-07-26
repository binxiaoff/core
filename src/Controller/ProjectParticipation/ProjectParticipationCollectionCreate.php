<?php

declare(strict_types=1);

namespace Unilend\Controller\ProjectParticipation;

use Doctrine\ORM\ORMException;
use Unilend\Entity\ProjectParticipationCollection;
use Unilend\Repository\ProjectParticipationRepository;

class ProjectParticipationCollectionCreate
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
     * @param ProjectParticipationCollection $data
     *
     * @return ProjectParticipationCollection
     *
     * @throws ORMException
     */
    public function __invoke(ProjectParticipationCollection $data)
    {
        foreach ($data->getProjectParticipations() as $projectParticipation) {
            $this->projectParticipationRepository->persist($projectParticipation);
        }

        $this->projectParticipationRepository->flush();

        return $data;
    }
}
