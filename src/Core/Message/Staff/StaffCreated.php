<?php

declare(strict_types=1);

namespace KLS\Core\Message\Staff;

use KLS\Core\Entity\Staff;
use KLS\Core\Message\AsyncMessageInterface;

class StaffCreated implements AsyncMessageInterface
{
    /** @var Staff */
    private $staffId;

    public function __construct(Staff $staff)
    {
        $this->staffId = $staff->getId();
    }

    public function getStaffId(): int
    {
        return $this->staffId;
    }
}
