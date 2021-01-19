<?php

declare(strict_types=1);

namespace Unilend\Core\Security\Voter;

use Unilend\Core\Entity\User;
use Unilend\Core\Entity\CompanyModule;

class CompanyModuleVoter extends AbstractEntityVoter
{
    /** @var string */
    public const ATTRIBUTE_EDIT = 'edit';

    /**
     * @param CompanyModule $companyModule
     * @param User          $submitter
     *
     * @return bool
     */
    public function canEdit(CompanyModule $companyModule, User $submitter): bool
    {
        $staff = $submitter->getCurrentStaff();

        return $staff && ($staff->isAdmin() || $staff->isAccountant()) && $companyModule->getCompany() === $staff->getCompany();
    }
}
