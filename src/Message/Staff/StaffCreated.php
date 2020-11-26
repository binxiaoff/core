<?php

declare(strict_types=1);

namespace Unilend\Message\Staff;

use Unilend\Core\Entity\Staff;
use Unilend\Message\AsyncMessageInterface;

class StaffCreated implements AsyncMessageInterface
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
