<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\Clients;
use Unilend\Traits\ConstantsAwareTrait;

abstract class AbstractEntityVoter extends Voter
{
    use ConstantsAwareTrait;

    protected const UNILEND_ENTITY_NAMESPACE = 'Unilend\\Entity\\';

    /** @var AuthorizationCheckerInterface */
    protected AuthorizationCheckerInterface $authorizationChecker;

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
    final protected function supports($attribute, $subject): bool
    {
        $entityClass = static::UNILEND_ENTITY_NAMESPACE . str_replace(['Unilend\\Security\\Voter\\', 'Voter'], '', static::class);

        return $subject instanceof $entityClass && \in_array($attribute, static::getConstants('ATTRIBUTE_'), true);
    }

    /**
     * {@inheritdoc}
     */
    final protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $this->getUser($token);

        if (null === $user) {
            return false;
        }

        if ($this->isGrantedAll($subject, $user) || $this->authorizationChecker->isGranted(Clients::ROLE_ADMIN)) {
            return true;
        }

        $prefixes = ['can', 'is'];
        $suffix = implode('', array_map('ucfirst', explode('_', $attribute)));

        foreach ($prefixes as $prefix) {
            $methodName = $prefix . $suffix;

            if (method_exists($this, $methodName)) {
                return $user && $this->fulfillPreconditions($subject, $user) && $this->{$methodName}($subject, $user);
            }
        }

        return false;
    }

    /**
     * @param TokenInterface $token
     *
     * @return Clients|null
     */
    protected function getUser(TokenInterface $token): ?Clients
    {
        /** @var Clients $user */
        $user = $token->getUser();

        return $user instanceof Clients ? $user : null;
    }

    /**
     * @param mixed   $subject
     * @param Clients $user
     *
     * @return bool
     */
    protected function isGrantedAll($subject, Clients $user): bool
    {
        return false;
    }

    /**
     * @param mixed   $subject
     * @param Clients $user
     *
     * @return bool
     */
    protected function fulfillPreconditions($subject, Clients $user): bool
    {
        return true;
    }
}
