<?php

declare(strict_types=1);

namespace KLS\Core\Service\Staff;

use KLS\Core\Entity\Staff;

interface StaffLoginInterface
{
    public function isGrantedLogin(Staff $staff): bool;
}
