<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\Clients;
use Unilend\Traits\ConstantsAwareTrait;

abstract class AbstractEntityVoter extends Voter
{
    use ConstantsAwareTrait;

    private const UNILEND_ENTITY_NAMESPACE = 'Unilend\\Entity\\';

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

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
        $entityClass = self::UNILEND_ENTITY_NAMESPACE . str_replace(['Unilend\\Security\\Voter\\', 'Voter'], '', static::class);

        return $subject instanceof $entityClass && \in_array($attribute, static::getConstants('ATTRIBUTE_'), true);
    }

    /**
     * {@inheritdoc}
     */
    final protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $this->getUser($token);

        if (($user && $this->isGrantedAll($subject, $user)) || $this->authorizationChecker->isGranted(Clients::ROLE_ADMIN)) {
            return true;
        }

        $methodName = 'can' . implode('', array_map('ucfirst', explode('_', $attribute)));

        if (false === method_exists($this, $methodName)) {
            throw new LogicException(sprintf('You have to implement %s in %s', $methodName, __CLASS__));
        }

        return $user && $this->fulfillPreconditions($subject, $user) && $this->{$methodName}($subject, $user);
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
