<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Agency\Entity\Participation;
use Unilend\Agency\Repository\ParticipationRepository;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class ParticipationVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_DELETE = 'delete';

    private ParticipationRepository $participationRepository;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ParticipationRepository $participationRepository)
    {
        parent::__construct($authorizationChecker);
        $this->participationRepository = $participationRepository;
    }

    public function canView(Participation $participation, User $user)
    {
        if (false === $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_VIEW, $participation->getProject())) {
            return false;
        }

        if (
            $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_AGENT, $participation->getProject())
            || $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_BORROWER, $participation->getProject())
        ) {
            return true;
        }

        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        $company = $staff->getCompany();

        /** @var Participation $connectedUserParticipation */
        $connectedUserParticipation = $this->participationRepository->findOneBy(['participant' => $company, 'project' => $participation->getProject()]);

        return $connectedUserParticipation && $connectedUserParticipation->isSecondary() === $participation->isSecondary();
    }

    public function canEdit(Participation $participation, User $user): bool
    {
        if ($participation->isArchived()) {
            return false;
        }

        if ($this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $participation->getProject())) {
            return true;
        }

        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        return $this->authorizationChecker->isGranted(ProjectRoleVoter::ROLE_PARTICIPANT, $participation->getProject())
            && $staff->getCompany() === $participation->getParticipant();
    }

    public function canCreate(Participation $participation, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $participation->getProject());
    }

    protected function canDelete(Participation $participation, User $user): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $participation->getProject()) && false === $participation->isAgent();
    }
}
