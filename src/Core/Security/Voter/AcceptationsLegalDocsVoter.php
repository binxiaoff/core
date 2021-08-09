<?php

declare(strict_types=1);

namespace KLS\Core\Security\Voter;

use KLS\Core\Entity\AcceptationsLegalDocs;
use KLS\Core\Entity\User;

class AcceptationsLegalDocsVoter extends AbstractEntityVoter
{
    public function canCreate(AcceptationsLegalDocs $acceptationsLegalDocs, User $submitter): bool
    {
        return $acceptationsLegalDocs->getAcceptedBy() === $submitter;
    }
}
