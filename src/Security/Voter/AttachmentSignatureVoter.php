<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\AttachmentSignature;
use Unilend\Entity\Clients;

class AttachmentSignatureVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_DOWNLOAD = 'signe';

    /**
     * @param AttachmentSignature $attachmentSignature
     * @param Clients             $user
     *
     * @return bool
     */
    protected function canSigne(AttachmentSignature $attachmentSignature, Clients $user): bool
    {
        return $attachmentSignature->getSignatory() === $user->getCurrentStaff();
    }
}
