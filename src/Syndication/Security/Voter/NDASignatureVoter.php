<?php

declare(strict_types=1);

namespace Unilend\Syndication\Security\Voter;

use Unilend\Core\Entity\User;
use Unilend\Core\Security\Voter\AbstractEntityVoter;
use Unilend\Syndication\Entity\NDASignature;
use Unilend\Syndication\Security\Voter\ProjectParticipationVoter;

class NDASignatureVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_CREATE = 'create';

    /**
     * @param NDASignature $signature
     * @param User         $user
     *
     * @return bool
     */
    public function canCreate(NDASignature $signature, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff &&
               $staff->getCompany() === $signature->getProjectParticipation()->getParticipant() &&
               $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $signature->getProjectParticipation());
    }
}
