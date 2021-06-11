<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Agency\Entity\ParticipationMember;
use Unilend\Agency\Repository\ParticipationMemberRepository;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class ParticipationMemberVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_EDIT   = 'edit';

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

    protected function canEdit(ParticipationMember $participationMember, User $user)
    {
        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        $project = $participationMember->getProject();

        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_PARTICIPANT, $project)
            && false === $participationMember->isArchived()
            && false === $participationMember->getParticipation()->isArchived()
            && $project->isEditable()
            && $staff->getCompany() === $participationMember->getParticipation()->getParticipant()
        ;
    }
}
