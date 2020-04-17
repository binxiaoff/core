<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\{Clients, FileVersionSignature};

class FileVersionSignatureVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW = 'view';
    public const ATTRIBUTE_SIGN = 'sign';

    /**
     * @param FileVersionSignature $fileVersionSignature
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canSign(FileVersionSignature $fileVersionSignature, Clients $user): bool
    {
        return $fileVersionSignature->getSignatory() === $user->getCurrentStaff();
    }

    /**
     * @param FileVersionSignature $fileVersionSignature
     * @param Clients              $user
     *
     * @return bool
     */
    protected function canView(FileVersionSignature $fileVersionSignature, Clients $user): bool
    {
        return $fileVersionSignature->getSignatory() === $user->getCurrentStaff() || $fileVersionSignature->getAddedBy() === $user->getCurrentStaff();
    }

    /**
     * {@inheritdoc}
     */
    protected function fulfillPreconditions($subject, Clients $user): bool
    {
        // Disable the signature for now
        return false;
    }
}
