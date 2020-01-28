<?php

declare(strict_types=1);

namespace Unilend\Message\Staff;

use Unilend\Entity\Staff;

class StaffCreated
{
    /** @var Staff */
    private $staff;

    /**
     * @param Staff $staff
     */
    public function __construct(Staff $staff)
    {
        $this->staff = $staff;
    }

    /**
     * @return Staff
     */
    public function getStaff(): Staff
    {
        return $this->staff;
    }
}
