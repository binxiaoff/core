<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\{FileVersionSignature, User};

class FileVersionSignatureVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW = 'view';
    public const ATTRIBUTE_SIGN = 'sign';

    /**
     * @param FileVersionSignature $fileVersionSignature
     * @param User                 $user
     *
     * @return bool
     */
    protected function canSign(FileVersionSignature $fileVersionSignature, User $user): bool
    {
        return $fileVersionSignature->getSignatory() === $user->getCurrentStaff();
    }

    /**
     * @param FileVersionSignature $fileVersionSignature
     * @param User                 $user
     *
     * @return bool
     */
    protected function canView(FileVersionSignature $fileVersionSignature, User $user): bool
    {
        return $fileVersionSignature->getSignatory() === $user->getCurrentStaff() || $fileVersionSignature->getAddedBy() === $user->getCurrentStaff();
    }

    /**
     * {@inheritdoc}
     */
    protected function fulfillPreconditions($subject, User $user): bool
    {
        // Disable the signature for now
        return false;
    }
}
