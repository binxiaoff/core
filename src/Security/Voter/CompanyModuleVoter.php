<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use Unilend\Entity\CompanyModule;
use Unilend\Entity\Staff;

class CompanyModuleVoter extends AbstractEntityVoter
{
    /** @var string */
    public const ATTRIBUTE_EDIT = 'edit';

    /**
     * @param CompanyModule $companyModule
     * @param Staff         $submitterStaff
     *
     * @return bool
     */
    public function canEdit(CompanyModule $companyModule, Staff $submitterStaff): bool
    {
        return $companyModule->getCompany() === $submitterStaff->getCompany();
    }
}
