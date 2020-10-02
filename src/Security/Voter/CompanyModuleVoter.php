<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\{Clients, CompanyModule};

class CompanyModuleVoter extends AbstractEntityVoter
{
    /** @var string */
    public const ATTRIBUTE_EDIT = 'edit';

    /**
     * @param CompanyModule $companyModule
     * @param Clients       $submitter
     *
     * @return bool
     */
    public function canEdit(CompanyModule $companyModule, Clients $submitter): bool
    {
        $staff = $submitter->getCurrentStaff();

        return $staff && ($staff->isAdmin() || $staff->isAccountant()) && $companyModule->getCompany() === $staff->getCompany();
    }
}
