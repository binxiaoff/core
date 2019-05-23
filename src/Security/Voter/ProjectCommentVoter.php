<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\Clients;
use Unilend\Entity\ProjectComment;
use Unilend\Traits\ConstantsAwareTrait;

class ProjectCommentVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ATTRIBUTE_EDIT = 'edit';

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        $attributes = self::getConstants('ATTRIBUTE_');

        if (false === in_array($attribute, $attributes)) {
            return false;
        }

        if (false === $subject instanceof ProjectComment) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $projectComment, TokenInterface $token): bool
    {
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients) {
            return false;
        }

        switch ($attribute) {
            case self::ATTRIBUTE_EDIT:
                return $this->canEdit($projectComment, $user);
        }

        throw new LogicException('This code should not be reached');
    }

    /**
     * @param ProjectComment $projectComment
     * @param Clients        $user
     *
     * @return bool
     */
    private function canEdit(ProjectComment $projectComment, Clients $user): bool
    {
        return $projectComment->getClient() === $user;
    }
}
