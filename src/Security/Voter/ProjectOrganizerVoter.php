<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\Clients;
use Unilend\Entity\ProjectOrganizer;
use Unilend\Traits\ConstantsAwareTrait;

class ProjectOrganizerVoter extends Voter
{
    use ConstantsAwareTrait;

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
    protected function supports($attribute, $subject)
    {
        return $subject instanceof ProjectOrganizer && \in_array($attribute, static::getConstants('ATTRIBUTE_'), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var ProjectOrganizer $subject */
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients || false === $this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $subject->getProject())) {
            return false;
        }

        switch ($attribute) {
            case static::ATTRIBUTE_EDIT:
                return $this->canEdit($subject);
            case static::ATTRIBUTE_CREATE:
                return true;
            case static::ATTRIBUTE_DELETE:
                return $this->canDelete($subject);
        }

        throw new LogicException('This code should not be reached');
    }

    /**
     * @param ProjectOrganizer $subject
     *
     * @return bool
     */
    private function canDelete(ProjectOrganizer $subject): bool
    {
        return false === $subject->hasRole(ProjectOrganizer::DUTY_PROJECT_ORGANIZER_ARRANGER);
    }

    /**
     * @param ProjectOrganizer $subject
     *
     * @return bool
     */
    private function canEdit(ProjectOrganizer $subject): bool
    {
        return false === $subject->hasRole(ProjectOrganizer::DUTY_PROJECT_ORGANIZER_ARRANGER);
    }
}
