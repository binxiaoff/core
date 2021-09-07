<?php

declare(strict_types=1);

namespace KLS\Core\Service\Staff;

use KLS\Core\Entity\Staff;

class StaffLoginChecker
{
    /** @var StaffLoginInterface[]|iterable */
    private iterable $checkers;

    public function __construct(iterable $checkers)
    {
        $this->checkers = $checkers;
    }

    public function isGrantedLogin(Staff $staff): bool
    {
        if (false === $staff->isActive()) {
            return false;
        }

        foreach ($this->checkers as $checker) {
            if ($checker->isGrantedLogin($staff)) {
                return true;
            }
        }

        return false;
    }
}
