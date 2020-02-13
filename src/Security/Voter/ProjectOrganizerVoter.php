<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\ProjectOrganizer;

class ProjectOrganizerVoter extends AbstractVoter
{
    public const ATTRIBUTE_DELETE = 'delete';
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_EDIT   = 'edit';

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return $subject instanceof ProjectOrganizer && parent::supports($attribute, $subject);
    }

    /**
     * @param ProjectOrganizer $subject
     *
     * @return bool
     */
    private function canCreate(ProjectOrganizer $subject)
    {
        return $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $subject->getProject());
    }

    /**
     * @param ProjectOrganizer $subject
     *
     * @return bool
     */
    private function canDelete(ProjectOrganizer $subject): bool
    {
        return $subject->hasRole(ProjectOrganizer::DUTY_PROJECT_ORGANIZER_ARRANGER) && $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $subject->getProject());
    }

    /**
     * @param ProjectOrganizer $subject
     *
     * @return bool
     */
    private function canEdit(ProjectOrganizer $subject): bool
    {
        return $subject->hasRole(ProjectOrganizer::DUTY_PROJECT_ORGANIZER_ARRANGER) && $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $subject->getProject());
    }
}
