<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, CompanyModule, Project, ProjectStatus, Staff};
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW                 = 'view';
    public const ATTRIBUTE_VIEW_NDA             = 'view_nda';
    public const ATTRIBUTE_ADMIN_VIEW           = 'admin_view';
    public const ATTRIBUTE_EDIT                 = 'edit';
    public const ATTRIBUTE_COMMENT              = 'comment';
    public const ATTRIBUTE_CREATE               = 'create';
    public const ATTRIBUTE_DELETE               = 'delete';

    /** @var ProjectParticipationManager */
    private ProjectParticipationManager $projectParticipationManager;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ProjectParticipationManager   $projectParticipationManager
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ProjectParticipationManager $projectParticipationManager)
    {
        parent::__construct($authorizationChecker);
        $this->projectParticipationManager = $projectParticipationManager;
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function canView(Project $project, Clients $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff && ($this->hasArrangerReadAccess($project, $staff) || $this->hasParticipantReadAccess($project, $staff));
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function canAdminView(Project $project, Clients $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff && $this->hasArrangerReadAccess($project, $staff);
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @return bool
     */
    protected function canCreate(Project $project, Clients $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff && $this->hasArrangerWriteAccess($project, $staff) ;
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function canViewNda(Project $project, Clients $user): bool
    {
        if ($this->canEdit($project, $user) || $this->canView($project, $user)) {
            return true;
        }

        return $this->projectParticipationManager->isParticipant($user->getCurrentStaff(), $project);
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function canEdit(Project $project, Clients $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $this->hasArrangerWriteAccess($project, $staff)
            && ProjectStatus::STATUS_SYNDICATION_CANCELLED !== $project->getCurrentStatus()->getStatus();
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function canComment(Project $project, Clients $user): bool
    {
        return $this->canView($project, $user);
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function canDelete(Project $project, Clients $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $this->hasArrangerWriteAccess($project, $staff)
            && ProjectStatus::STATUS_DRAFT === $project->getCurrentStatus()->getStatus();
    }

    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    private function hasParticipantReadAccess(Project $project, Staff $staff): bool
    {
        // The participant doesn't need the participation module for the read access (CALS-2379)
        return $staff->isActive()
            && $this->projectParticipationManager->isParticipant($staff, $project)
            && (null === $project->getNda() || $this->projectParticipationManager->isNdaAccepted($staff, $project));
    }

    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @return bool
     */
    private function hasArrangerReadAccess(Project $project, Staff $staff): bool
    {
        return $staff->isActive()
            && $staff->getCompany() === $project->getSubmitterCompany()
            && ($staff->isAdmin() || $staff->getMarketSegments()->contains($project->getMarketSegment()) || $project->getSubmitterClient() === $staff->getClient());
    }

    /**
     * @param Project $project
     * @param Staff   $staff
     *
     * @return bool
     */
    private function hasArrangerWriteAccess(Project $project, Staff $staff): bool
    {
        return $this->hasArrangerReadAccess($project, $staff)
            && ($staff->isAdmin() || $staff->isManager() || $staff->isOperator())
            && $staff->getCompany()->hasModuleActivated(CompanyModule::MODULE_ARRANGEMENT);
    }
}
