<?php

declare(strict_types=1);

namespace Unilend\Syndication\Service;

use Unilend\Core\Entity\Staff;
use Unilend\Core\Service\Staff\StaffLoginInterface;

class StaffLoginChecker implements StaffLoginInterface
{
    public function isGrantedLogin(Staff $staff): bool
    {
        $company = $staff->getCompany();

        if ($company->isCAGMember() && false === $company->hasSigned()) {
            return false;
        }

        return true;
    }
}
