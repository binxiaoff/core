<?php

declare(strict_types=1);

namespace Unilend\Message\Staff;

use Unilend\Entity\Staff;

class StaffCreated
{
    /** @var Staff */
    private $staffId;

    /**
     * @param Staff $staff
     */
    public function __construct(Staff $staff)
    {
        $this->staffId = $staff->getId();
    }

    /**
     * @return int
     */
    public function getStaffId(): int
    {
        return $this->staffId;
    }
}
