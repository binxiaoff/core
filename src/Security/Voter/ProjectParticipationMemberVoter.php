<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, CompanyModule, ProjectParticipationMember, ProjectStatus};
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationMemberVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE     = 'create';
    public const ATTRIBUTE_ACCEPT_NDA = 'accept_nda';
    public const ATTRIBUTE_EDIT       = 'edit';

    /** @var ProjectParticipationManager */
    private ProjectParticipationManager $projectParticipationManager;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ProjectParticipationManager   $projectParticipationManager
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ProjectParticipationManager $projectParticipationManager)
    {
        parent::__construct($authorizationChecker);
        $this->authorizationChecker        = $authorizationChecker;
        $this->projectParticipationManager = $projectParticipationManager;
    }

    /**
     * @param mixed   $subject
     * @param Clients $user
     *
     * @return bool
     */
    protected function fulfillPreconditions($subject, Clients $user): bool
    {
        return $subject->getProjectParticipation()->getProject()->getCurrentStatus()->getStatus() <= ProjectStatus::STATUS_PARTICIPANT_REPLY;
    }

    /**
     * @param ProjectParticipationMember $subject
     * @param Clients                    $user
     *
     * @return bool
     */
    protected function canAcceptNda(ProjectParticipationMember $subject, Clients $user): bool
    {
        return $subject->getProjectParticipation()->getParticipant()->hasModuleActivated(CompanyModule::MODULE_PARTICIPATION) && $subject->getStaff() === $user->getCurrentStaff();
    }

    /**
     * @param ProjectParticipationMember $subject
     * @param Clients                    $user
     *
     * @return bool
     */
    protected function canEdit(ProjectParticipationMember $subject, Clients $user): bool
    {
        return $this->canCreate($subject, $user) || $this->canAcceptNda($subject, $user);
    }

    /**
     * @param ProjectParticipationMember $subject
     * @param Clients                    $user
     *
     * @return bool
     */
    protected function canCreate(ProjectParticipationMember $subject, Clients $user): bool
    {
        $currentCompany = $user->getCompany();

        return $currentCompany && (
            $subject->getProjectParticipation()->getProject()->getSubmitterCompany() === $currentCompany // You are connected as a staff of the arranger
            || ($subject->getProjectParticipation()->getParticipant()->hasModuleActivated(CompanyModule::MODULE_PARTICIPATION) &&
                $this->projectParticipationManager->isParticipationOwner($user->getCurrentStaff(), $subject->getProjectParticipation()) &&
                $currentCompany->isCAGMember())); // You are connected as a staff of the participation
    }
}
