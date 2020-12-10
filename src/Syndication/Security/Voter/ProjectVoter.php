<?php

declare(strict_types=1);

namespace Unilend\Syndication\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\Clients;
use Unilend\Core\Entity\CompanyModule;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\Syndication\Entity\{Project, ProjectStatus};
use Unilend\Syndication\Service\Project\ProjectManager;

class ProjectVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW                 = 'view';
    public const ATTRIBUTE_VIEW_NDA             = 'view_nda';
    public const ATTRIBUTE_ADMIN_VIEW           = 'admin_view';
    public const ATTRIBUTE_EDIT                 = 'edit';
    public const ATTRIBUTE_COMMENT              = 'comment';
    public const ATTRIBUTE_CREATE               = 'create';
    public const ATTRIBUTE_DELETE               = 'delete';

    /** @var ProjectManager */
    private ProjectManager $projectManager;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ProjectManager                $projectManager
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ProjectManager $projectManager)
    {
        parent::__construct($authorizationChecker);
        $this->projectManager = $projectManager;
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

        return $staff
            && $this->hasArrangerWriteAccess($project, $staff)
            && $staff->getCompany()->hasModuleActivated(CompanyModule::MODULE_ARRANGEMENT);
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

        return $this->projectManager->isParticipationMember($project, $user->getCurrentStaff());
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
        if (false === $staff->isActive()) {
            return false;
        }

        $projectParticipationMember = $this->projectManager->getParticipationMember($project, $staff);

        // The participant doesn't need the participation module for the read access (CALS-2379)
        return $projectParticipationMember && (
            null === $projectParticipationMember->getAcceptableNdaVersion() || $projectParticipationMember->getNdaAccepted()
        );
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
        return $this->hasArrangerReadAccess($project, $staff) && ($staff->isAdmin() || $staff->isManager() || $staff->isOperator());
    }
}
