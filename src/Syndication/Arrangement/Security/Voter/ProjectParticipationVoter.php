<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Security\Voter;

use KLS\Core\Entity\CompanyModule;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectParticipationMember;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use KLS\Syndication\Arrangement\Repository\ProjectParticipationMemberRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ProjectParticipationVoter extends AbstractEntityVoter
{
    private ProjectParticipationMemberRepository $projectParticipationMemberRepository;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ProjectParticipationMemberRepository $projectParticipationMemberRepository
    ) {
        parent::__construct($authorizationChecker);
        $this->projectParticipationMemberRepository = $projectParticipationMemberRepository;
    }

    /**
     * @param mixed $subject
     */
    protected function fulfillPreconditions($subject, User $user): bool
    {
        return null !== $user->getCurrentStaff();
    }

    protected function canCreate(ProjectParticipation $subject, User $user): bool
    {
        // $subject->getParticipant()->isCAGMember() should be changed when other group banks use platform
        $staff = $user->getCurrentStaff();

        if (false === $staff instanceof Staff) {
            return false;
        }

        $project = $subject->getProject();

        $arrangerParticipation = $project->getArrangerProjectParticipation();

        return $this->hasPermissionEffective(
            $arrangerParticipation,
            $staff,
            ProjectParticipationMember::PERMISSION_WRITE
        ) && (
                $subject->getParticipant()->isCAGMember()
                || $subject
                    ->getProject()
                    ->getArranger()
                    ->hasModuleActivated(CompanyModule::MODULE_ARRANGEMENT_EXTERNAL_BANK)
        );
    }

    protected function canView(ProjectParticipation $subject, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (false === $staff instanceof Staff) {
            return false;
        }

        return $this->hasPermissionEffective($subject, $staff, ProjectParticipationMember::PERMISSION_READ)
            || $this->hasPermissionEffective(
                $subject->getProject()->getArrangerProjectParticipation(),
                $staff,
                ProjectParticipationMember::PERMISSION_READ
            );
    }

    protected function canEdit(ProjectParticipation $projectParticipation, User $user): bool
    {
        $project = $projectParticipation->getProject();

        $staff = $user->getCurrentStaff();

        if (false === $staff instanceof Staff) {
            return false;
        }

        if (false === $projectParticipation->isActive() || false === $project->hasEditableStatus()) {
            return false;
        }

        $arrangerParticipation = $project->getArrangerProjectParticipation();
        // Arranger condition
        if (
            $this->hasPermissionEffective(
                $arrangerParticipation,
                $staff,
                ProjectParticipationMember::PERMISSION_WRITE
            )
        ) {
            return true;
        }

        return $projectParticipation->getParticipant()->hasModuleActivated(CompanyModule::MODULE_PARTICIPATION)
            && $project->isPublished()
            && $project->getCurrentStatus()->getStatus() < ProjectStatus::STATUS_ALLOCATION
            && $this->hasPermissionEffective(
                $projectParticipation,
                $staff,
                ProjectParticipationMember::PERMISSION_WRITE
            );
    }

    protected function canDelete(ProjectParticipation $projectParticipation, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (false === $staff instanceof Staff) {
            return false;
        }

        $project = $projectParticipation->getProject();

        $arrangerParticipation = $project->getArrangerProjectParticipation();

        return $this->hasPermissionEffective(
            $arrangerParticipation,
            $staff,
            ProjectParticipationMember::PERMISSION_WRITE
        )
            && ProjectStatus::STATUS_DRAFT === $project->getCurrentStatus()->getStatus()
            && $projectParticipation->getParticipant() !== $project->getArranger();
    }

    private function hasPermissionEffective(
        ProjectParticipation $projectParticipation,
        Staff $staff,
        int $permission
    ): bool {
        $participant = $projectParticipation->getParticipant();

        // In case that the current user wants to have access to a participation
        // of which the participant hasn't signed the contract with us,
        // we verify if the current user is the arranger of the project by verifying if the user has the access to
        // the arranger's participation.
        if (
            $staff->getCompany() !== $participant
            && ($participant->isProspect() || $participant->hasRefused())
            && $participant->isSameGroup($staff->getCompany())
        ) {
            return $this->checkPermission(
                $staff,
                $projectParticipation->getProject()->getArrangerProjectParticipation(),
                $permission
            );
        }

        return $this->checkPermission($staff, $projectParticipation, $permission);
    }

    private function checkPermission(Staff $staff, ProjectParticipation $projectParticipation, int $permission): bool
    {
        $member = $this->projectParticipationMemberRepository->findOneBy([
            'projectParticipation' => $projectParticipation,
            'staff'                => $staff,
            'archived'             => null,
        ]);

        return (
            $member
            && $member->getStaff()->isActive()
            && false === $member->isArchived()
            && $member->getPermissions()->has($permission)
        ) || 0 < \count($projectParticipation->getManagedMembersOfPermission($staff, $permission));
    }
}
