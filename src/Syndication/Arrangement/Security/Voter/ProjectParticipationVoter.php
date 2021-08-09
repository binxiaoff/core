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
    public const ATTRIBUTE_SENSITIVE_VIEW = 'sensitive_view';
    public const ATTRIBUTE_ADMIN_VIEW     = 'admin_view';

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

        return $this->hasPermissionEffective($arrangerParticipation, $staff, ProjectParticipationMember::PERMISSION_WRITE)
            && ($subject->getParticipant()->isCAGMember() || $subject->getProject()->getArranger()->hasModuleActivated(CompanyModule::MODULE_ARRANGEMENT_EXTERNAL_BANK));
    }

    protected function canView(ProjectParticipation $subject, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (false === $staff instanceof Staff) {
            return false;
        }

        return $this->hasPermissionEffective($subject, $staff, ProjectParticipationMember::PERMISSION_READ)
            || $this->hasPermissionEffective($subject->getProject()->getArrangerProjectParticipation(), $staff, ProjectParticipationMember::PERMISSION_READ);

        /*
         *
         * Visibility is not used for now
        switch ($subject->getProject()->getOfferVisibility()) {
            case Project::OFFER_VISIBILITY_PRIVATE:
                return $this->projectParticipationManager->hasPermissionEffective($subject, $staff);
            case Project::OFFER_VISIBILITY_PARTICIPANT:
            case Project::OFFER_VISIBILITY_PUBLIC:
                return $this->projectManager->isActiveParticipationMember($subject->getProject(), $staff);
        }

        throw new LogicException('This code should not be reached');
         */
    }

    protected function canAdminView(ProjectParticipation $projectParticipation, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (false === $staff instanceof Staff) {
            return false;
        }

        return $this->hasPermissionEffective($projectParticipation, $staff, ProjectParticipationMember::PERMISSION_READ)
            || $this->hasPermissionEffective($projectParticipation->getProject()->getArrangerProjectParticipation(), $staff, ProjectParticipationMember::PERMISSION_READ);
    }

    protected function canSensitiveView(ProjectParticipation $projectParticipation, User $user): bool
    {
        return $this->canAdminView($projectParticipation, $user);
        // Visibility is not used for now || Project::OFFER_VISIBILITY_PUBLIC === $projectParticipation->getProject()->getOfferVisibility();
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
        if ($this->hasPermissionEffective($arrangerParticipation, $staff, ProjectParticipationMember::PERMISSION_WRITE)) {
            return true;
        }

        return $projectParticipation->getParticipant()->hasModuleActivated(CompanyModule::MODULE_PARTICIPATION)
            && $project->isPublished()
            && $project->getCurrentStatus()->getStatus() < ProjectStatus::STATUS_ALLOCATION
            && $this->hasPermissionEffective($projectParticipation, $staff, ProjectParticipationMember::PERMISSION_WRITE);
    }

    protected function canDelete(ProjectParticipation $projectParticipation, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        if (false === $staff instanceof Staff) {
            return false;
        }

        $project = $projectParticipation->getProject();

        $arrangerParticipation = $project->getArrangerProjectParticipation();

        return $this->hasPermissionEffective($arrangerParticipation, $staff, ProjectParticipationMember::PERMISSION_WRITE)
            && ProjectStatus::STATUS_DRAFT === $project->getCurrentStatus()->getStatus()
            && $projectParticipation->getParticipant() !== $project->getArranger();
    }

    private function hasPermissionEffective(ProjectParticipation $projectParticipation, Staff $staff, int $permission = 0): bool
    {
        $testedParticipations = [$projectParticipation];
        $participant          = $projectParticipation->getParticipant();

        if (($participant->isProspect() || $participant->hasRefused()) && $participant->isSameGroup($staff->getCompany())) {
            $testedParticipation[] = $projectParticipation->getProject()->getArrangerProjectParticipation();
        }

        $testedParticipations = \array_unique($testedParticipations);

        foreach ($testedParticipations as $testedParticipation) {
            $member = $this->projectParticipationMemberRepository->findOneBy([
                'projectParticipation' => $projectParticipation,
                'staff'                => $staff,
                'archived'             => null,
            ]);

            if ($member && false === $member->isArchived() && $member->getPermissions()->has($permission)) {
                return true;
            }

            if (false === $staff->isManager()) {
                return false;
            }

            if (0 < \count($this->projectParticipationMemberRepository->findActiveByProjectParticipationAndManagerAndPermissionEnabled($testedParticipation, $staff, $permission))) {
                return true;
            }
        }

        return false;
    }
}
