<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\{Clients, TrancheOffer};

class TrancheOfferVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_MANAGE = 'manage';

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
