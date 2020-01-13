<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Command;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\{AuthorizationCheckerInterface, Voter\Voter};
use Unilend\Entity\Clients;
use Unilend\Traits\ConstantsAwareTrait;

class BackOfficeZoneVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ATTRIBUTE_VIEW = 'view';

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
        $attributes = self::getConstants('ATTRIBUTE_');

        if (false === in_array($attribute, $attributes, true)) {
            return false;
        }

        if (false === $subject instanceof Command) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        // As there is no specification that has been defined, we authorise the admin use with all accesses.
        return $this->authorizationChecker->isGranted(Clients::ROLE_ADMIN);
    }
}
