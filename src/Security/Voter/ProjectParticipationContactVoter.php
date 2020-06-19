<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\Clients;
use Unilend\Entity\ProjectParticipationContact;
use Unilend\Entity\ProjectStatus;
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationContactVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE     = 'create';
    public const ATTRIBUTE_ACCEPT_NDA = 'accept_nda';
    public const ATTRIBUTE_ARCHIVE    = 'archive';

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
        $this->authorizationChecker        = $authorizationChecker;
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
        return $subject->getProjectParticipation()->getProject()->getCurrentStatus()->getStatus() <= ProjectStatus::STATUS_INTERESTS_COLLECTED;
    }

    /**
     * @param ProjectParticipationContact $subject
     * @param Clients                     $user
     *
     * @return bool
     */
    protected function canAcceptNda(ProjectParticipationContact $subject, Clients $user)
    {
        return $subject->getClient() === $user;
    }

    /**
     * @param ProjectParticipationContact $subject
     * @param Clients                     $user
     *
     * @return bool
     */
    protected function canArchive(ProjectParticipationContact $subject, Clients $user)
    {
        return $this->canCreate($subject, $user);
    }

    /**
     * @param ProjectParticipationContact $subject
     * @param Clients                     $user
     *
     * @return bool
     */
    protected function canCreate(ProjectParticipationContact $subject, Clients $user)
    {
        return $subject->getProjectParticipation()->getProject()->getArranger() === $user || $this->isParticipant($subject, $user);
    }

    /**
     * @param ProjectParticipationContact $subject
     * @param Clients                     $user
     *
     * @return bool
     */
    private function isParticipant(ProjectParticipationContact $subject, Clients $user)
    {
        return $this->projectParticipationManager->isParticipationOwner($user->getCurrentStaff(), $subject->getProjectParticipation());
    }
}
