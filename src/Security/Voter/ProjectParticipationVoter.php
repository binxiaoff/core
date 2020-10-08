<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use LogicException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, CompanyModule, Project, ProjectParticipation, ProjectParticipationStatus, ProjectStatus};
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_DELETE = 'delete';

    public const ATTRIBUTE_SENSITIVE_VIEW = 'sensitive_view';
    public const ATTRIBUTE_ADMIN_VIEW     = 'admin_view';

    public const ATTRIBUTE_ARRANGER                      = 'arranger';
    public const ATTRIBUTE_OWNER                         = 'owner';

    /** @var ProjectParticipationManager */
    private ProjectParticipationManager $projectParticipationManager;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ProjectParticipationManager   $projectParticipationManager
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ProjectParticipationManager $projectParticipationManager
    ) {
        parent::__construct($authorizationChecker);
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
        return null !== $user->getCurrentStaff();
    }

    /**
     * @param ProjectParticipation $subject
     * @param Clients              $user
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    protected function canView(ProjectParticipation $subject, Clients $user): bool
    {
        if ($this->isArranger($subject, $user)) {
            return true;
        }

        $project = $subject->getProject();

        switch ($project->getOfferVisibility()) {
            case Project::OFFER_VISIBILITY_PRIVATE:
                return $this->projectParticipationManager->isParticipationOwner($user->getCurrentStaff(), $subject);
            case Project::OFFER_VISIBILITY_PARTICIPANT:
            case Project::OFFER_VISIBILITY_PUBLIC:
                return $this->projectParticipationManager->isParticipant($user->getCurrentStaff(), $project);
        }

        throw new LogicException('This code should not be reached');
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canAdminView(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        return $this->projectParticipationManager->isParticipationOwner($user->getCurrentStaff(), $projectParticipation)
            || $this->isArranger($projectParticipation, $user);
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canSensitiveView(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        return $this->canAdminView($projectParticipation, $user)
        || Project::OFFER_VISIBILITY_PUBLIC === $projectParticipation->getProject()->getOfferVisibility();
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        $project = $projectParticipation->getProject();

        return false === $projectParticipation->isArchived()
            && $project->hasEditableStatus()
            && (
                $this->isArranger($projectParticipation, $user)
                || (
                    $projectParticipation->getParticipant()->hasModuleActivated(CompanyModule::MODULE_PARTICIPATION)
                    && $this->isOwner($projectParticipation, $user)
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

    /**
     * @param ProjectParticipation $subject
     * @param Clients              $user
     *
     * @return bool
     */
    protected function isArranger(ProjectParticipation $subject, Clients $user): bool
    {
        return $subject->getProject()->getSubmitterCompany() === $user->getCompany();
    }

    /**
     * @param ProjectParticipation $subject
     * @param Clients              $user
     *
     * @return bool
     */
    protected function isOwner(ProjectParticipation $subject, Clients $user)
    {
        $participant = $subject->getParticipant();
        $staff       = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        // As an arranger, the user doesn't need the participation module to edit the following participation.
        if ($this->isArranger($subject, $user)) {
            // The one of a prospect in the same company group.
            if (($participant->isProspect() || $participant->hasRefused()) && $participant->isSameGroup($staff->getCompany())) {
                return true;
            }
            // Or the one of arranger's own (we don't check if the user is a participation member for the arranger's participation)
            if ($participant === $staff->getCompany()) {
                return true;
            }
        }

        return $this->projectParticipationManager->isParticipationOwner($user->getCurrentStaff(), $subject);
    }
}
