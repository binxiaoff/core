<?php

declare(strict_types=1);

namespace Unilend\Service\Project;

use Doctrine\ORM\NonUniqueResultException;
use Unilend\Core\Entity\Staff;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectParticipationMember;
use Unilend\Syndication\Repository\ProjectParticipationMemberRepository;

class ProjectManager
{
    /**
     * @var ProjectParticipationMemberRepository
     */
    private ProjectParticipationMemberRepository $projectParticipationMemberRepository;

    /**
     * @param ProjectParticipationMemberRepository $projectParticipationMemberRepository
     */
    public function __construct(ProjectParticipationMemberRepository $projectParticipationMemberRepository)
    {
        $this->projectParticipationMemberRepository = $projectParticipationMemberRepository;
    }


    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    public function isParticipationMember(Project $project, Staff $staff): bool
    {
        $projectParticipationMember = $this->getParticipationMember($project, $staff);

        return $projectParticipationMember && false === $projectParticipationMember->isArchived();
    }

    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @return ProjectParticipationMember|null
     *
     * @throws NonUniqueResultException
     */
    public function getParticipationMember(Project $project, Staff $staff): ?ProjectParticipationMember
    {
        return $this->projectParticipationMemberRepository->findByProjectAndStaff($project, $staff);
    }

    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @return bool
     */
    public function isArranger(Project $project, Staff $staff): bool
    {
        return $project->getArranger() === $staff->getCompany();
    }
}
