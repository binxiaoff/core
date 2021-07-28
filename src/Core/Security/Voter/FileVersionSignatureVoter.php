<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\FileVersionSignature;
use Unilend\Core\Entity\User;

class FileVersionSignatureVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_SIGN = 'sign';

    protected function fulfillPreconditions($subject, User $user): bool
    {
        // Disable the signature for now
        return false;
    }

    protected function canView(FileVersionSignature $fileVersionSignature, User $user): bool
    {
        return $fileVersionSignature->getSignatory() === $user->getCurrentStaff() || $fileVersionSignature->getAddedBy() === $user->getCurrentStaff();
    }

    protected function canSign(FileVersionSignature $fileVersionSignature, User $user): bool
    {
        return $fileVersionSignature->getSignatory() === $user->getCurrentStaff();
    }
}
