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

    public const ATTRIBUTE_ARRANGER_EDIT                      = 'arranger_edit';
    public const ATTRIBUTE_ARRANGER_INTEREST_COLLECTION_EDIT  = 'arranger_interest_collection_edit';
    public const ATTRIBUTE_ARRANGER_OFFER_NEGOTIATION_EDIT    = 'arranger_offer_negotiation_edit';
    public const ATTRIBUTE_ARRANGER_ALLOCATION_EDIT           = 'arranger_allocation_edit';

    public const ATTRIBUTE_PARTICIPATION_OWNER_EDIT                      = 'participation_owner_edit';
    public const ATTRIBUTE_PARTICIPATION_OWNER_INTEREST_COLLECTION_EDIT  = 'participation_owner_interest_collection_edit';
    public const ATTRIBUTE_PARTICIPATION_OWNER_OFFER_NEGOTIATION_EDIT    = 'participation_owner_offer_negotiation_edit';

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
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canArrangerEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        return $this->canEdit($projectParticipation, $user) && $this->isProjectArranger($projectParticipation, $user);
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canParticipationOwnerEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        $project     = $projectParticipation->getProject();
        $participant = $projectParticipation->getParticipant();
        $staff       = $user->getCurrentStaff();

        if (null === $staff) {
            return false;
        }

        if (false ===  $project->isPublished() || false === $project->hasEditableStatus()) {
            return false;
        }
        // As an arranger, the user doesn't need the participation module to edit the following participation.
        if ($this->isProjectArranger($projectParticipation, $user)) {
            // The one of a prospect in the same company group.
            if ($participant->isProspect() && $participant->isSameGroup($staff->getCompany())) {
                return true;
            }
            // Or the one of arranger's own.
            if ($this->projectParticipationManager->isParticipationOwner($user->getCurrentStaff(), $projectParticipation)) {
                return true;
            }
        }

        return $this->projectParticipationManager->isParticipationOwner($user->getCurrentStaff(), $projectParticipation)
            && $projectParticipation->getParticipant()->hasModuleActivated(CompanyModule::MODULE_PARTICIPATION);
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
        if ($this->isProjectArranger($subject, $user)) {
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
            || $this->isProjectArranger($projectParticipation, $user);
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
        return $projectParticipation->isActive()
            && $projectParticipation->getProject()->hasEditableStatus()
            && (
                $this->isProjectArranger($projectParticipation, $user)
                || (
                    $projectParticipation->getParticipant()->hasModuleActivated(CompanyModule::MODULE_PARTICIPATION)
                    && $this->projectParticipationManager->isParticipationOwner($user->getCurrentStaff(), $projectParticipation)
                    && ProjectParticipationStatus::STATUS_COMMITTEE_ACCEPTED !== $projectParticipation->getCurrentStatus()->getStatus()
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
        return $this->authorizationChecker->isGranted('edit', $subject->getProject())
            && ($subject->getParticipant()->isCAGMember() || $subject->getProject()->getArranger()->hasModuleActivated(CompanyModule::MODULE_ARRANGEMENT_EXTERNAL_BANK));
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canArrangerInterestCollectionEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        return $this->isProjectArranger($projectParticipation, $user) && false === $projectParticipation->getProject()->isInterestCollected();
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canArrangerOfferNegotiationEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        $project = $projectParticipation->getProject();

        return $this->isProjectArranger($projectParticipation, $user)
            && ($project->isInOfferNegotiationStep() || (false === $project->isInterestExpressionEnabled() && false === $project->isPublished()));
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canArrangerAllocationEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        return $this->isProjectArranger($projectParticipation, $user)
            && $projectParticipation->getProject()->isInAllocationStep()
            && $this->projectParticipationManager->isParticipationOwner($user->getCurrentStaff(), $projectParticipation);
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canParticipationOwnerInterestCollectionEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        return $this->canParticipationOwnerEdit($projectParticipation, $user)
            && $projectParticipation->getProject()->isInInterestCollectionStep();
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canParticipationOwnerOfferNegotiationEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        return $this->canParticipationOwnerEdit($projectParticipation, $user)
            && $projectParticipation->getProject()->isInOfferNegotiationStep();
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
    private function isProjectArranger(ProjectParticipation $subject, Clients $user): bool
    {
        return $subject->getProject()->getSubmitterCompany() === $user->getCompany();
    }
}
