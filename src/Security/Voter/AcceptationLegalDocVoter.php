<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\AcceptationsLegalDocs;
use Unilend\Entity\Clients;

class AcceptationLegalDocVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_DOWNLOAD = 'download';

    /**
     * @param AcceptationsLegalDocs $attachment
     * @param Clients               $user
     *
     * @return bool
     */
    protected function canDownload(AcceptationsLegalDocs $attachment, Clients $user): bool
    {
        return $attachment->getClient() === $user;
    }
}
