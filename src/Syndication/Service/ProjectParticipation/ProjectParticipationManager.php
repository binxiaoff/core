<?php

declare(strict_types=1);

namespace Unilend\Syndication\Service\ProjectParticipation;

use Unilend\Core\Entity\Staff;
use Unilend\Syndication\Entity\ProjectParticipation;
use Unilend\Syndication\Entity\ProjectParticipationMember;
use Unilend\Syndication\Repository\NDASignatureRepository;
use Unilend\Syndication\Repository\ProjectParticipationMemberRepository;

class ProjectParticipationManager
{
    private ProjectParticipationMemberRepository $projectParticipationMemberRepository;

    private NDASignatureRepository $NDASignatureRepository;

    public function __construct(
        ProjectParticipationMemberRepository $projectParticipationMemberRepository,
        NDASignatureRepository $NDASignatureRepository
    ) {
        $this->projectParticipationMemberRepository = $projectParticipationMemberRepository;
        $this->NDASignatureRepository               = $NDASignatureRepository;
    }

    public function isActiveMember(ProjectParticipation $projectParticipation, Staff $staff): bool
    {
        return null !== $this->getActiveMember($projectParticipation, $staff);
    }

    public function getActiveMember(ProjectParticipation $projectParticipation, Staff $staff): ?ProjectParticipationMember
    {
        return $this->projectParticipationMemberRepository->findOneBy([
            'projectParticipation' => $projectParticipation,
            'staff'                => $staff,
            'archived'             => null,
        ]);
    }

    public function hasSignedNDA(ProjectParticipation $projectParticipation, Staff $staff): bool
    {
        return null !== $this->NDASignatureRepository->findOneBy(['projectParticipation' => $projectParticipation, 'addedBy' => $staff]);
    }
}
