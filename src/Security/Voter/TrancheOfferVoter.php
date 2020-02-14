<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Entity\{Clients, TrancheOffer};

class TrancheOfferVoter extends AbstractVoter
{
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
    protected function supports($attribute, $subject): bool
    {
        return $subject instanceof TrancheOffer && parent::supports($attribute, $subject);
    }

    /**
     * @param TrancheOffer $trancheOffer
     * @param Clients      $user
     *
     * @return bool
     */
    protected function canManage(TrancheOffer $trancheOffer, Clients $user): bool
    {
        if ($this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_MANAGE_TRANCHE_OFFER, $trancheOffer->getTranche()->getProject())) {
            return true;
        }

        return null !== $trancheOffer->getProjectParticipationOffer()->getProjectParticipation()->getCompany()->getStaff($user);
    }
}
