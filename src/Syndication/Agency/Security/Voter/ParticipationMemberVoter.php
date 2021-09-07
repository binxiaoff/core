<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Agency\Entity\ParticipationMember;
use KLS\Syndication\Agency\Repository\ParticipationMemberRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ParticipationMemberVoter extends AbstractEntityVoter
{
    private ParticipationMemberRepository $participationMemberRepository;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ParticipationMemberRepository $participationMemberRepository)
    {
        parent::__construct($authorizationChecker);
        $this->participationMemberRepository = $participationMemberRepository;
    }

    /**
     * @throws NonUniqueResultException
     */
    protected function canCreate(ParticipationMember $participationMember, User $user): bool
    {
        $project = $participationMember->getProject();

        if (false === $project->isEditable()) {
            return false;
        }

        // Agent can create member for participation
        if ($this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $project)) {
            return true;
        }

        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        $company = $staff->getCompany();

        if ($company !== $participationMember->getParticipation()->getParticipant()) {
            return false;
        }

        // Seek a corresponding member for current connected staff (user and company) and project
        $participationMember = $this->participationMemberRepository->findByProjectAndCompanyAndUserAndActive(
            $project,
            $company,
            $user
        );

        return null !== $participationMember;
    }

    protected function canView(ParticipationMember $participationMember, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW, $participationMember->getProject());
    }

    protected function canEdit(ParticipationMember $participationMember, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        $project = $participationMember->getProject();

        return false === $participationMember->isArchived()
            && false === $participationMember->getParticipation()->isArchived()
            && $project->isEditable()
            && (
                (
                    $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_PARTICIPANT, $project)
                    && $staff->getCompany() === $participationMember->getParticipation()->getParticipant()
                )
                || $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $project)
            )
        ;
    }
}
