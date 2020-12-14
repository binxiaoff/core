<?php

declare(strict_types=1);

namespace Unilend\Syndication\Security\Voter;

use Exception;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\Syndication\Entity\ProjectMessage;
use Unilend\Syndication\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectMessageVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';
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
     * @param ProjectMessage $subject
     * @param User           $user
     *
     * @return bool

     **@throws Exception
     *
     */
    protected function canCreate(ProjectMessage $subject, User $user): bool
    {
        return $subject->getParticipation()->getProject()->getSubmitterCompany() === $user->getCompany()
            || (
                $this->projectParticipationManager->isActiveMember($subject->getParticipation(), $user->getCurrentStaff())
            );
    }

    /**
     * @param ProjectMessage $subject
     * @param User           $user
     *
     * @return bool
     */
    protected function canEdit(ProjectMessage $subject, User $user): bool
    {
        return $user->getCurrentStaff() === $subject->getAddedBy();
    }

    /**
     * @param ProjectMessage $subject
     * @param User           $user
     *
     * @return bool
     */
    protected function canDelete(ProjectMessage $subject, User $user): bool
    {
        return $user->getCurrentStaff() === $subject->getAddedBy();
    }
}
