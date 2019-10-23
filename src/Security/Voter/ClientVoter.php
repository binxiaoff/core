<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\{AuthorizationCheckerInterface, Voter\Voter};
use Unilend\Entity\Clients;
use Unilend\Traits\ConstantsAwareTrait;

class ClientVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ATTRIBUTE_VIEW = 'view';
    public const ATTRIBUTE_EDIT = 'edit';

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
        $attributes = self::getConstants('ATTRIBUTE_');

        if (false === in_array($attribute, $attributes, true)) {
            return false;
        }

        if (false === $subject instanceof Clients) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients) {
            return false;
        }

        switch ($attribute) {
            case self::ATTRIBUTE_VIEW:
                return $this->canView();
            case self::ATTRIBUTE_EDIT:
                return $this->canEdit($subject, $user);
        }

        throw new LogicException('This code should not be reached');
    }

    /**
     * @return bool
     */
    private function canView(): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_USER');
    }

    /**
     * @param Clients $subject
     * @param Clients $user
     *
     * @return bool
     */
    private function canEdit(Clients $subject, Clients $user): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_ADMIN') || $subject->getIdClient() === $user->getIdClient();
    }
}
