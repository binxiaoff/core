<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\AcceptationsLegalDocs;
use Unilend\Entity\Clients;

class AcceptationLegalDocVoter extends AbstractVoter
{
    public const ATTRIBUTE_DOWNLOAD = 'download';

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return $subject instanceof AcceptationsLegalDocs && parent::supports($attribute, $subject);
    }

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
