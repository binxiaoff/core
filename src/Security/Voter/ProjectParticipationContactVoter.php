<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\Clients;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationContact;
use Unilend\Entity\ProjectStatus;
use Unilend\Repository\ProjectParticipationContactRepository;

class ProjectParticipationContactVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_CREATE = 'create';
    /**
     * @var ProjectParticipationContactRepository
     */
    private $projectParticipationContactRepository;

    /**
     * ProjectParticipationContactVoter constructor.
     *
     * @param AuthorizationCheckerInterface         $authorizationChecker
     * @param ProjectParticipationContactRepository $projectParticipationContactRepository
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ProjectParticipationContactRepository $projectParticipationContactRepository)
    {
        parent::__construct($authorizationChecker);
        $this->authorizationChecker                  = $authorizationChecker;
        $this->projectParticipationContactRepository = $projectParticipationContactRepository;
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
        return $subject->getClient() === $user
            || (
                $this->isValidProjectParticipationContact($subject->getProjectParticipation(), $user)
                && false === $subject->isArchived()
            );
    }

    /**
     * @param ProjectParticipationContact $subject
     * @param Clients                     $user
     *
     * @return bool
     */
    protected function canCreate(ProjectParticipationContact $subject, Clients $user)
    {
        return $this->isValidProjectParticipationContact($subject->getProjectParticipation(), $user);
    }

    /**
     * @param ProjectParticipation $projectParticipation
     * @param Clients              $user
     *
     * @return bool
     */
    private function isValidProjectParticipationContact(ProjectParticipation $projectParticipation, Clients $user)
    {
        return null !== $this->projectParticipationContactRepository->findOneBy(['projectParticipation' => $projectParticipation, 'client' => $user, 'archived' => null]);
    }
}
