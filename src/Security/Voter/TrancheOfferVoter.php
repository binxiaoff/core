<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\{Clients, TrancheOffer};
use Unilend\Traits\ConstantsAwareTrait;

class TrancheOfferVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ATTRIBUTE_MANAGE = 'manage';

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

        if (false === $subject instanceof TrancheOffer) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $trancheOffer, TokenInterface $token): bool
    {
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients) {
            return false;
        }

        switch ($attribute) {
            case self::ATTRIBUTE_MANAGE:
                return $this->canManage($trancheOffer, $user);
        }

        throw new LogicException('This code should not be reached');
    }

    /**
     * @param TrancheOffer $trancheOffer
     * @param Clients      $user
     *
     * @return bool
     */
    private function canManage(TrancheOffer $trancheOffer, Clients $user): bool
    {
        if ($this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_MANAGE_TRANCHE_OFFER, $trancheOffer->getTranche()->getProject())) {
            return true;
        }

        return null !== $trancheOffer->getProjectParticipationOffer()->getProjectParticipation()->getCompany()->getStaff($user);
    }
}
