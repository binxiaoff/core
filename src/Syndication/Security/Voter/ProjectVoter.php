<?php

declare(strict_types=1);

namespace Unilend\Syndication\Security\Voter;

use Exception;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\CompanyModule;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\Syndication\Entity\Project;
use Unilend\Syndication\Entity\ProjectStatus;
use Unilend\Syndication\Repository\ProjectParticipationRepository;
use Unilend\Syndication\Service\Project\ProjectManager;

class ProjectVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW       = 'view';
    public const ATTRIBUTE_VIEW_NDA   = 'view_nda';
    public const ATTRIBUTE_ADMIN_VIEW = 'admin_view';
    public const ATTRIBUTE_EDIT       = 'edit';
    public const ATTRIBUTE_COMMENT    = 'comment';
    public const ATTRIBUTE_CREATE     = 'create';
    public const ATTRIBUTE_DELETE     = 'delete';

    private ProjectManager $projectManager;

    private ProjectParticipationRepository $projectParticipationRepository;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ProjectParticipationRepository $projectParticipationRepository,
        ProjectManager $projectManager
    ) {
        parent::__construct($authorizationChecker);
        $this->projectParticipationRepository = $projectParticipationRepository;
        $this->projectManager                 = $projectManager;
    }

    /**
     * @throws Exception
     */
    protected function canView(Project $project, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        $projectParticipation = $this->projectParticipationRepository->findOneBy([
            'participant' => $staff->getCompany(),
            'project'     => $project,
        ]);

        return $projectParticipation
            && $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $projectParticipation)
            && (
                $this->projectManager->hasSignedNDA($project, $staff)
                || null === $projectParticipation->getAcceptableNdaVersion()
                || $project->getArranger() === $staff->getCompany()
            );
    }

    /**
     * @throws Exception
     */
    protected function canAdminView(Project $project, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $project->getArrangerProjectParticipation())
            && $project->getArranger() === $staff->getCompany();
    }

    protected function canCreate(Project $project, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $staff->hasArrangementProjectCreationPermission()
            && $staff->getCompany()->hasModuleActivated(CompanyModule::MODULE_ARRANGEMENT);
    }

    /**
     * @throws Exception
     */
    protected function canViewNda(Project $project, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        $projectParticipation = $this->projectParticipationRepository->findOneBy([
            'participant' => $staff->getCompany(),
            'project'     => $project,
        ]);

        return $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $projectParticipation);
    }

    /**
     * @throws Exception
     */
    protected function canEdit(Project $project, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_EDIT, $project->getArrangerProjectParticipation())
            && ProjectStatus::STATUS_SYNDICATION_CANCELLED !== $project->getCurrentStatus()->getStatus();
    }

    /**
     * @throws Exception
     */
    protected function canComment(Project $project, User $user): bool
    {
        return $this->canView($project, $user);
    }

    /**
     * @throws Exception
     */
    protected function canDelete(Project $project, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        // We check against the EDIT attribute of ProjectParticipationVoter instead of DELETE Attribute
        // because DELETE return false when tested object is arrangerParticipation
        // (That is the normal case : we cannot in any case delete arranger participation)
        // We need a little be more flexibility on this case
        return $staff
            && $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_EDIT, $project->getArrangerProjectParticipation())
            && ProjectStatus::STATUS_DRAFT === $project->getCurrentStatus()->getStatus();
    }
}
