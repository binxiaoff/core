<?php

declare(strict_types=1);

namespace Unilend\Agency\Security\Voter;

use Exception;
use Unilend\Agency\Entity\TermAnswer;
use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;

class TermAnswerVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_VIEW   = 'view';

    /**
     * @param TermAnswer $termAnswer
     * @param User       $user
     *
     * @return bool
     */
    protected function canView(TermAnswer $termAnswer, User $user): bool
    {
        return $this->authorizationChecker->isGranted(TermVoter::ATTRIBUTE_VIEW, $termAnswer->getTerm());
    }

    /**
     * @param TermAnswer $termAnswer
     * @param User       $user
     *
     * @return bool
     */
    protected function canCreate(TermAnswer $termAnswer, User $user): bool
    {
        // There must be no validated answer to create a new one
        return false === $termAnswer->getTerm()->isValid();
    }
}
