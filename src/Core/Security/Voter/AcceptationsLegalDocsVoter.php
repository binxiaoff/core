<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\AcceptationsLegalDocs;
use Unilend\Core\Entity\User;

class AcceptationsLegalDocsVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW = 'create';

    /**
     * @param AcceptationsLegalDocs $acceptationsLegalDocs
     * @param User                  $submitter
     *
     * @return bool
     */
    public function canCreate(AcceptationsLegalDocs $acceptationsLegalDocs, User $submitter): bool
    {
        return $acceptationsLegalDocs->getAcceptedBy() === $submitter;
    }
}
