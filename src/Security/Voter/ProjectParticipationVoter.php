<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Doctrine\ORM\NonUniqueResultException;
use LogicException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, Project, ProjectParticipation, ProjectParticipationStatus, ProjectStatus};
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';

    public const ATTRIBUTE_SENSITIVE_VIEW = 'sensitive_view';
    public const ATTRIBUTE_ADMIN_VIEW     = 'admin_view';

    public const ATTRIBUTE_ARRANGER_INTEREST_COLLECTION_EDIT  = 'arranger_interest_collection_edit';
    public const ATTRIBUTE_ARRANGER_OFFER_NEGOTIATION_EDIT    = 'arranger_offer_negotiation_edit';
    public const ATTRIBUTE_ARRANGER_CONTRACT_NEGOTIATION_EDIT = 'arranger_contract_negotiation_edit';

    public const ATTRIBUTE_PARTICIPATION_OWNER_EDIT                      = 'participation_owner_edit';
    public const ATTRIBUTE_PARTICIPATION_OWNER_INTEREST_COLLECTION_EDIT  = 'participation_owner_interest_collection_edit';
    public const ATTRIBUTE_PARTICIPATION_OWNER_OFFER_NEGOTIATION_EDIT    = 'participation_owner_offer_negotiation_edit';
    public const ATTRIBUTE_PARTICIPATION_OWNER_CONTRACT_NEGOTIATION_EDIT = 'participation_owner_contract_negotiation_edit';

    /** @var ProjectParticipationManager */
    private $projectParticipationManager;

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
        return $user->getCurrentStaff() && $subject->getProject()->getCurrentStatus()->getStatus() <= ProjectStatus::STATUS_ALLOCATION;
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
        $project = $subject->getProject();

        $projectOrganizer = $subject->getProject()->getArranger();
        if ($projectOrganizer && $projectOrganizer->isArranger()) {
            return true;
        }

        switch ($project->getOfferVisibility()) {
            case Project::OFFER_VISIBILITY_PRIVATE:
                return null !== $this->projectParticipationManager->isParticipationOwner($user->getCurrentStaff(), $subject);
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
            || $projectParticipation->getProject()->getSubmitterCompany() === $user->getCompany();
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
            && ProjectParticipation::COMMITTEE_STATUS_REJECTED !== $projectParticipation->getCommitteeStatus()
            && (
                $projectParticipation->getProject()->getSubmitterCompany() === $user->getCompany()
                || (
                    $this->projectParticipationManager->isParticipationOwner($user->getCurrentStaff(), $projectParticipation)
                    && ProjectParticipation::COMMITTEE_STATUS_ACCEPTED !== $projectParticipation->getCommitteeStatus()
                )
            );
    }

    /**
     * @see https://lafabriquebyca.atlassian.net/browse/CALS-759
     *
     * @param ProjectParticipation $subject
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canCreate(ProjectParticipation $subject, Clients $user): bool
    {
        return $this->authorizationChecker->isGranted('edit', $subject->getProject());
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canArrangerInterestCollectionEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        $project = $projectParticipation->getProject();

        return $this->isArranger($project, $user) && false === $project->isInterestCollected();
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

        return $this->isArranger($project, $user) && $project->isInOfferNegotiationStep();
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canArrangerContractNegotiationEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        $project = $projectParticipation->getProject();

        return $this->isArranger($project, $user) && $project->isInContractNegotiationStep();
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canParticipationOwnerEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        return $this->projectParticipationManager->isParticipationOwner($user->getCurrentStaff(), $projectParticipation);
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canParticipationOwnerInterestCollectionEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        return $this->canParticipationOwnerEdit($projectParticipation, $user) && false === $projectParticipation->getProject()->isInterestCollected();
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canParticipationOwnerOfferNegotiationEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        return $this->canParticipationOwnerEdit($projectParticipation, $user) && $projectParticipation->getProject()->isInOfferNegotiationStep();
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canParticipationOwnerContractNegotiationEdit(ProjectParticipation $projectParticipation, Clients $user): bool
    {
        return $this->canParticipationOwnerEdit($projectParticipation, $user) && $projectParticipation->getProject()->isInContractNegotiationStep();
    }

    /**
     * @param Project $project
     * @param Clients $user
     *
     * @return bool
     */
    private function isArranger(Project $project, Clients $user): bool
    {
        return $project->getSubmitterCompany() === $user->getCompany();
    }
}
