<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\AcceptationsLegalDocs;
use Unilend\Entity\Clients;

class AcceptationsLegalDocsVoter extends AbstractEntityVoter
{
    public const ATTRIBUTE_VIEW = 'create';

    /**
     * @param AcceptationsLegalDocs $acceptationsLegalDocs
     * @param Clients               $submitter
     *
     * @return bool
     */
    public function canCreate(AcceptationsLegalDocs $acceptationsLegalDocs, Clients $submitter): bool
    {
        $staff = $submitter->getCurrentStaff();

        return $staff && $acceptationsLegalDocs->getAddedBy() === $staff;
    }
}
