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
        return $subject->getProject()->getCurrentStatus()->getStatus() <= ProjectStatus::STATUS_ALLOCATION;
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
     * @param ProjectParticipation $subject
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canEdit(ProjectParticipation $subject, Clients $user): bool
    {
        return $subject->getProject()->getSubmitterCompany() === $user->getCompany()
            || (
                $this->projectParticipationManager->isParticipationOwner($user->getCurrentStaff(), $subject)
                && ProjectParticipationStatus::STATUS_ACTIVE === $subject->getCurrentStatus()->getStatus()
                && !in_array($subject->getCommitteeStatus(), [ProjectParticipation::COMMITTEE_STATUS_ACCEPTED, ProjectParticipation::COMMITTEE_STATUS_REJECTED], true)
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
        $company   = $subject->getCompany();
        $blacklist = array_map('strtolower', ProjectParticipation::BLACKLISTED_COMPANIES);

        return false === \in_array(mb_strtolower($company->getName()), $blacklist, true) && $this->authorizationChecker->isGranted('edit', $subject->getProject());
    }
}
