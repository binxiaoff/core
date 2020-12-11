<?php

declare(strict_types=1);

namespace Unilend\Syndication\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use LogicException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Entity\CompanyModule;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\Syndication\Entity\{Project,
    ProjectParticipation,
    ProjectStatus
};
use Unilend\Syndication\Service\Project\ProjectManager;
use Unilend\Syndication\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_DELETE = 'delete';

    public const ATTRIBUTE_SENSITIVE_VIEW = 'sensitive_view';
    public const ATTRIBUTE_ADMIN_VIEW     = 'admin_view';

    /** @var ProjectParticipationManager */
    private ProjectParticipationManager $projectParticipationManager;

    /** @var ProjectManager  */
    private ProjectManager $projectManager;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ProjectManager                $projectManager
     * @param ProjectParticipationManager   $projectParticipationManager
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ProjectManager $projectManager,
        ProjectParticipationManager $projectParticipationManager
    ) {
        parent::__construct($authorizationChecker);
        $this->projectParticipationManager = $projectParticipationManager;
        $this->projectManager = $projectManager;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param mixed $subject
     * @param User  $user
     *
     * @return bool
     */
    protected function fulfillPreconditions($subject, User $user): bool
    {
        return null !== $user->getCurrentStaff();
    }

    /**
     * @param ProjectParticipation $subject
     * @param User                 $user
     *
     * @return bool
     *
     * @throws NonUniqueResultException
     */
    protected function canView(ProjectParticipation $subject, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (false === $staff instanceof Staff) {
            return false;
        }

        if ($this->projectManager->isArranger($subject->getProject(), $staff)) {
            return true;
        }

        switch ($subject->getProject()->getOfferVisibility()) {
            case Project::OFFER_VISIBILITY_PRIVATE:
                return $this->projectParticipationManager->isMember($subject, $staff);
            case Project::OFFER_VISIBILITY_PARTICIPANT:
            case Project::OFFER_VISIBILITY_PUBLIC:
                return $this->projectManager->isParticipationMember($subject->getProject(), $staff);
        }

        throw new LogicException('This code should not be reached');
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param User                 $user
     *
     * @return bool
     */
    protected function canAdminView(ProjectParticipation $projectParticipation, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (false === $staff instanceof Staff) {
            return false;
        }

        return $this->projectParticipationManager->isMember($projectParticipation, $staff)
            || $this->projectManager->isArranger($projectParticipation->getProject(), $staff);
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param User                 $user
     *
     * @return bool
     */
    protected function canSensitiveView(ProjectParticipation $projectParticipation, User $user): bool
    {
        return $this->canAdminView($projectParticipation, $user)
        || Project::OFFER_VISIBILITY_PUBLIC === $projectParticipation->getProject()->getOfferVisibility();
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param User                 $user
     *
     * @return bool
     */
    protected function canEdit(ProjectParticipation $projectParticipation, User $user): bool
    {
        $project = $projectParticipation->getProject();

        $staff = $user->getCurrentStaff();

        if (false === $staff instanceof Staff) {
            return false;
        }

        return $projectParticipation->isActive()
            && $project->hasEditableStatus()
            && (
                $this->projectManager->isArranger($projectParticipation->getProject(), $staff)
                || (
                    $projectParticipation->getParticipant()->hasModuleActivated(CompanyModule::MODULE_PARTICIPATION)
                    && $this->projectParticipationManager->isMember($projectParticipation, $staff)
                    && $project->isPublished()
                    && $project->getCurrentStatus()->getStatus() < ProjectStatus::STATUS_ALLOCATION
                )
            );
    }

    /**
     * @param ProjectParticipation $subject
     *
     * @return bool
     */
    protected function canCreate(ProjectParticipation $subject): bool
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $subject->getProject())
            && ($subject->getParticipant()->isCAGMember() || $subject->getProject()->getArranger()->hasModuleActivated(CompanyModule::MODULE_ARRANGEMENT_EXTERNAL_BANK));
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @return bool
     */
    protected function canDelete(ProjectParticipation $projectParticipation): bool
    {
        $project = $projectParticipation->getProject();

        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)
            && ProjectStatus::STATUS_DRAFT === $project->getCurrentStatus()->getStatus()
            && $projectParticipation->getParticipant() !== $project->getSubmitterCompany();
    }
}
