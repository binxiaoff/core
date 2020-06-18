<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\Clients;
use Unilend\Entity\ProjectParticipationContact;
use Unilend\Entity\ProjectStatus;
use Unilend\Repository\ProjectParticipationContactRepository;
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationContactVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';

    /** @var ProjectParticipationContactRepository */
    private $projectParticipationContactRepository;
    /** @var ProjectParticipationManager */
    private $projectParticipationManager;

    /**
     * @param AuthorizationCheckerInterface         $authorizationChecker
     * @param ProjectParticipationContactRepository $projectParticipationContactRepository
     * @param ProjectParticipationManager           $projectParticipationManager
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ProjectParticipationContactRepository $projectParticipationContactRepository,
        ProjectParticipationManager $projectParticipationManager
    ) {
        parent::__construct($authorizationChecker);
        $this->authorizationChecker                  = $authorizationChecker;
        $this->projectParticipationContactRepository = $projectParticipationContactRepository;
        $this->projectParticipationManager           = $projectParticipationManager;
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
    protected function canView(ProjectParticipationContact $subject, Clients $user)
    {
        return $subject->getClient() === $user;
    }

    /**
     * @param ProjectParticipationContact $subject
     * @param Clients                     $user
     *
     * @return bool
     */
    protected function canEdit(ProjectParticipationContact $subject, Clients $user)
    {
        return $this->isParticipant($subject, $user);
    }

    /**
     * @param ProjectParticipationContact $subject
     * @param Clients                     $user
     *
     * @return bool
     */
    protected function canCreate(ProjectParticipationContact $subject, Clients $user)
    {
        return $this->isParticipant($subject, $user);
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
