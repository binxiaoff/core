<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Security\Voter;

use Exception;
use KLS\Core\Entity\CompanyModule;
use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use KLS\Syndication\Arrangement\Repository\ProjectParticipationRepository;
use KLS\Syndication\Arrangement\Service\Project\ProjectManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ProjectVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW_NDA            = 'view_nda';
    public const ATTRIBUTE_VIEW_GROUP_INTERNAL = 'view_group_internal';
    public const ATTRIBUTE_ADMIN_VIEW          = 'admin_view';
    public const ATTRIBUTE_COMMENT             = 'comment';
    public const ATTRIBUTE_EXPORT              = 'export';

    private ProjectParticipationRepository $projectParticipationRepository;
    private ProjectManager $projectManager;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ProjectParticipationRepository $projectParticipationRepository,
        ProjectManager $projectManager
    ) {
        parent::__construct($authorizationChecker);
        $this->projectParticipationRepository = $projectParticipationRepository;
        $this->projectManager                 = $projectManager;
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
    protected function canViewGroupInternal(Project $project, User $user): bool
    {
        if (false === $this->canView($project, $user)) {
            return false;
        }

        $staff = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        $arrangerCompany      = $project->getArranger();
        $arrangerCompanyGroup = $arrangerCompany->getCompanyGroup();

        return $arrangerCompanyGroup
            ? $arrangerCompanyGroup === $staff->getCompany()->getCompanyGroup()
            : $staff->getCompany()  === $arrangerCompany
        ;
    }

    /**
     * @throws Exception
     */
    protected function canAdminView(Project $project, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
            && $this->authorizationChecker->isGranted(
                ProjectParticipationVoter::ATTRIBUTE_VIEW,
                $project->getArrangerProjectParticipation()
            )
            && $project->getArranger() === $staff->getCompany();
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
            && $this->authorizationChecker->isGranted(
                ProjectParticipationVoter::ATTRIBUTE_EDIT,
                $project->getArrangerProjectParticipation()
            )
            && ProjectStatus::STATUS_SYNDICATION_CANCELLED !== $project->getCurrentStatus()->getStatus();
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
            && $this->authorizationChecker->isGranted(
                ProjectParticipationVoter::ATTRIBUTE_EDIT,
                $project->getArrangerProjectParticipation()
            )
            && ProjectStatus::STATUS_DRAFT === $project->getCurrentStatus()->getStatus();
    }

    /**
     * @throws Exception
     *
     * @return bool
     */
    protected function canExport(Project $project, User $user)
    {
        $agent = $project->getAgent();

        $staff = $user->getCurrentStaff();

        $company = $staff ? $staff->getCompany() : null;

        return $this->authorizationChecker->isGranted(static::ATTRIBUTE_VIEW, $project)
            && $project->isFinished()
            && $company
            && ($project->getArranger() === $company || ($agent && $agent->getCompany() === $company));
    }

    /**
     * @throws Exception
     */
    protected function canComment(Project $project, User $user): bool
    {
        return $this->canView($project, $user);
    }
}
