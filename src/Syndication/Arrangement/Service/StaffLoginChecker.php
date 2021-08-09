<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Service;

use KLS\Core\Entity\Staff;
use KLS\Core\Service\Staff\StaffLoginInterface;

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
