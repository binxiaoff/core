<?php

declare(strict_types=1);

namespace Unilend\Core\Service\Staff;

use Unilend\Core\Entity\Staff;

interface StaffLoginInterface
{
    public function isGrantedLogin(Staff $staff): bool;
}
