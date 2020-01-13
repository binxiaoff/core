<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\{AuthorizationCheckerInterface, Voter\Voter};
use Unilend\Entity\{ClientStatus, Clients};
use Unilend\Traits\ConstantsAwareTrait;

class ClientStatusVoter extends Voter
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
    protected function supports($attribute, $subject)
    {
        $attributes = self::getConstants('ATTRIBUTE_');

        if (false === in_array($attribute, $attributes, true)) {
            return false;
        }

        if (false === $subject instanceof ClientStatus) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $clientStatus, TokenInterface $token): bool
    {
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients) {
            return false;
        }

        switch ($attribute) {
            case self::ATTRIBUTE_VIEW:
                return $this->canView($clientStatus, $user);
        }

        throw new LogicException('This code should not be reached');
    }

    /**
     * @param ClientStatus $clientStatus
     * @param Clients      $user
     *
     * @return bool
     */
    private function canView(ClientStatus $clientStatus, Clients $user): bool
    {
        return $this->authorizationChecker->isGranted(Clients::ROLE_ADMIN) || $user === $clientStatus->getClient();
    }
}
