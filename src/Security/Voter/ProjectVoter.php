<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Exception;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, Project, ProjectOrganizer, ProjectStatus};
use Unilend\Repository\ProjectOrganizerRepository;
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW                 = 'view';
    public const ATTRIBUTE_VIEW_NDA             = 'view_nda';
    public const ATTRIBUTE_ADMIN_VIEW           = 'admin_view';
    public const ATTRIBUTE_EDIT                 = 'edit';
    public const ATTRIBUTE_MANAGE_TRANCHE_OFFER = 'manage_tranche_offer';
    public const ATTRIBUTE_CREATE_TRANCHE_OFFER = 'create_tranche_offer';
    public const ATTRIBUTE_COMMENT              = 'comment';
    public const ATTRIBUTE_CREATE               = 'create';
    public const ATTRIBUTE_DELETE               = 'delete';

    /** @var ProjectOrganizerRepository */
    private ProjectOrganizerRepository $projectOrganizerRepository;
    /** @var ProjectParticipationManager */
    private ProjectParticipationManager $projectParticipationManager;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ProjectParticipationManager   $projectParticipationManager
     * @param ProjectOrganizerRepository    $projectOrganizerRepository
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ProjectParticipationManager $projectParticipationManager,
        ProjectOrganizerRepository $projectOrganizerRepository
    ) {
        parent::__construct($authorizationChecker);
        $this->projectParticipationManager = $projectParticipationManager;
        $this->projectOrganizerRepository  = $projectOrganizerRepository;
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
        if ($project->getSubmitterClient() === $user) {
            return true;
        }

        $staff = $user->getCurrentStaff();

        return  $staff
            && $staff->isActive()
            && $this->projectParticipationManager->isParticipant($staff, $project)
            && (null === $project->getNda() || $this->projectParticipationManager->isNdaAccepted($staff, $project));
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @return bool
     */
    protected function canAdminView(Project $project, Clients $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $this->canView($project, $user)
            && $staff->getCompany() === $project->getSubmitterCompany()
            && ($staff->isAdmin() || $staff->getMarketSegments()->contains($project->getMarketSegment()));
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

        return  $staff && $staff->isActive() && ($staff->isAdmin() || $staff->getMarketSegments()->contains($project->getMarketSegment()));
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
            && $this->canView($project, $user) && $this->canAdminView($project, $user)
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
    protected function canManageTrancheOffer(Project $project, Clients $user): bool
    {
        return $project->getSubmitterCompany() === $user->getCompany();
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @throws Exception
     *
     * @return bool
     */
    protected function canCreateTrancheOffer(Project $project, Clients $user): bool
    {
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
        return $this->canEdit($project, $user) && ProjectStatus::STATUS_DRAFT === $project->getCurrentStatus()->getStatus();
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @return ProjectOrganizer|null
     */
    private function getProjectOrganizer(Project $project, Clients $user): ?ProjectOrganizer
    {
        return $this->projectOrganizerRepository->findOneBy(['project' => $project, 'company' => $user->getCompany()]);
    }
}
