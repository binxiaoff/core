<?php

declare(strict_types=1);

namespace KLS\Core\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Traits\ConstantsAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

abstract class AbstractEntityVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ATTRIBUTE_CREATE = 'create';
    public const ATTRIBUTE_VIEW   = 'view';
    public const ATTRIBUTE_EDIT   = 'edit';
    public const ATTRIBUTE_DELETE = 'delete';

    protected AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    final protected function supports($attribute, $subject): bool
    {
        $entityClass = \str_replace(['Security\\Voter\\', 'Voter'], ['Entity\\', ''], static::class);

        return $subject instanceof $entityClass && \in_array($attribute, static::getConstants('ATTRIBUTE_'), true);
    }

    final protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $this->getUser($token);

        if (null === $user) {
            return false;
        }

        if ($this->isGrantedAll($subject, $user) || $this->authorizationChecker->isGranted(User::ROLE_ADMIN)) {
            return true;
        }

        $methodName = 'can' . \implode('', \array_map('ucfirst', \explode('_', $attribute)));

        if (false === \method_exists($this, $methodName)) {
            return false;
        }

        return $user && $this->fulfillPreconditions($subject, $user) && $this->{$methodName}($subject, $user);
    }

    protected function getUser(TokenInterface $token): ?User
    {
        /** @var User $user */
        $user = $token->getUser();

        return $user instanceof User ? $user : null;
    }

    /**
     * @param mixed $subject
     */
    protected function isGrantedAll($subject, User $user): bool
    {
        return false;
    }

    /**
     * @param mixed $subject
     */
    protected function fulfillPreconditions($subject, User $user): bool
    {
        return true;
    }
}
