<?php

declare(strict_types=1);

namespace KLS\Syndication\Security\Voter;

use KLS\Core\Entity\User;
use KLS\Core\Security\Voter\AbstractEntityVoter;
use KLS\Syndication\Entity\NDASignature;

class NDASignatureVoter extends AbstractEntityVoter
{
    public function canCreate(NDASignature $signature, User $user): bool
    {
        $staff = $user->getCurrentStaff();

        return $staff
           && $staff->getCompany() === $signature->getProjectParticipation()->getParticipant()
           && $this->authorizationChecker->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $signature->getProjectParticipation());
    }
}
