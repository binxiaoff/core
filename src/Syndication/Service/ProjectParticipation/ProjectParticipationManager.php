<?php

declare(strict_types=1);

namespace Unilend\Syndication\Service\ProjectParticipation;

use Unilend\Core\Entity\Staff;
use Unilend\Syndication\Entity\{ProjectParticipation, ProjectParticipationMember};
use Unilend\Syndication\Repository\NDASignatureRepository;
use Unilend\Syndication\Repository\ProjectParticipationMemberRepository;
use Unilend\Syndication\Service\Project\ProjectManager;

class ProjectParticipationManager
{
    /** @var ProjectParticipationMemberRepository */
    private ProjectParticipationMemberRepository $projectParticipationMemberRepository;

    /** @var NDASignatureRepository */
    private NDASignatureRepository $NDASignatureRepository;

    /**
     * @param ProjectParticipationMemberRepository $projectParticipationMemberRepository
     * @param NDASignatureRepository               $NDASignatureRepository
     */
    public function __construct(
        ProjectParticipationMemberRepository $projectParticipationMemberRepository,
        NDASignatureRepository $NDASignatureRepository
    ) {
        $this->projectParticipationMemberRepository = $projectParticipationMemberRepository;
        $this->NDASignatureRepository = $NDASignatureRepository;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Staff                $staff
     *
     * @return bool
     */
    public function isActiveMember(ProjectParticipation $projectParticipation, Staff $staff): bool
    {
        return null !== $this->getActiveMember($projectParticipation, $staff);
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Staff                $staff
     *
     * @return ProjectParticipationMember|null
     */
    public function getActiveMember(ProjectParticipation $projectParticipation, Staff $staff): ?ProjectParticipationMember
    {
        return $this->projectParticipationMemberRepository->findOneBy([
            'projectParticipation' => $projectParticipation,
            'staff'                => $staff,
            'archived'             => null,
        ]);
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Staff                $staff
     *
     * @return bool
     */
    public function hasSignedNDA(ProjectParticipation $projectParticipation, Staff $staff): bool
    {
        return null !== $this->NDASignatureRepository->findOneBy(['projectParticipation' => $projectParticipation, 'staff' => $staff]);
    }
}
