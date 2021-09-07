<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Service\ProjectParticipation;

use KLS\Core\Entity\Staff;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationMember;
use KLS\Syndication\Arrangement\Repository\NDASignatureRepository;
use KLS\Syndication\Arrangement\Repository\ProjectParticipationMemberRepository;

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
