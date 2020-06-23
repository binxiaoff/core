<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, ProjectParticipationContact, ProjectStatus};
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationContactVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE     = 'create';
    public const ATTRIBUTE_ACCEPT_NDA = 'accept_nda';
    public const ATTRIBUTE_ARCHIVE    = 'archive';
    public const ATTRIBUTE_EDIT       = 'edit';

    /** @var ProjectParticipationManager */
    private $projectParticipationManager;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ProjectParticipationManager   $projectParticipationManager
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ProjectParticipationManager $projectParticipationManager
    ) {
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
     * @param ProjectParticipationContact $subject
     * @param Clients                     $user
     *
     * @return bool
     */
    protected function canAcceptNda(ProjectParticipationContact $subject, Clients $user): bool
    {
        return $subject->getStaff() === $user->getCurrentStaff();
    }

    /**
     * @param ProjectParticipationContact $subject
     * @param Clients                     $user
     *
     * @return bool
     */
    protected function canArchive(ProjectParticipationContact $subject, Clients $user): bool
    {
        return $this->canCreate($subject, $user);
    }

    /**
     * @param ProjectParticipationContact $subject
     * @param Clients                     $user
     *
     * @return bool
     */
    protected function canEdit(ProjectParticipationContact $subject, Clients $user): bool
    {
        return $this->canCreate($subject, $user);
    }

    /**
     * @param ProjectParticipationContact $subject
     * @param Clients                     $user
     *
     * @return bool
     */
    protected function canCreate(ProjectParticipationContact $subject, Clients $user): bool
    {
        return $subject->getProjectParticipation()->getProject()->getSubmitterCompany() === $user->getCompany()
            || $this->projectParticipationManager->isParticipationOwner($user->getCurrentStaff(), $subject->getProjectParticipation());
    }
}
