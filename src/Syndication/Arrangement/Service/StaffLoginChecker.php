<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Service;

use KLS\Core\Entity\Staff;
use KLS\Core\Service\Staff\StaffLoginInterface;

/**
 * @internal should only be used in KLS\Core\Service\Staff\StaffLoginChecker
 */
class StaffLoginChecker implements StaffLoginInterface
{
    public function isGrantedLogin(Staff $staff): bool
    {
        return true;
    }
}
