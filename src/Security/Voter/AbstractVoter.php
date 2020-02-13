<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\Clients;
use Unilend\Traits\ConstantsAwareTrait;

abstract class AbstractVoter extends Voter
{
    use ConstantsAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return \in_array($attribute, static::getConstants('ATTRIBUTE_'), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $methodName = 'can' . implode('', array_map('ucfirst', explode('_', $attribute)));

        if (false === method_exists($this, $methodName)) {
            throw new \LogicException(sprintf('You have to implement %s in %s', $methodName, __CLASS__));
        }

        $user = $this->getUser($token);

        return $user && $this->{$methodName}($subject, $user);
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
}
