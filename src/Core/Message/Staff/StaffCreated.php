<?php

declare(strict_types=1);

namespace Unilend\Core\Message\Staff;

use Unilend\Core\Entity\Staff;
use Unilend\Core\Message\AsyncMessageInterface;

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
