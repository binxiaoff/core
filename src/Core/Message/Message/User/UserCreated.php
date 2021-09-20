<?php

declare(strict_types=1);

namespace KLS\Core\Message\Message\User;

use KLS\Core\Entity\User;
use KLS\Core\Message\AsyncMessageInterface;

class UserCreated implements AsyncMessageInterface
{
    private int $userId;
    private int $previousStatus;
    private int $newStatus;

    public function __construct(User $user, int $previousStatus, int $newStatus)
    {
        $this->userId         = $user->getId();
        $this->previousStatus = $previousStatus;
        $this->newStatus      = $newStatus;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getPreviousStatus(): int
    {
        return $this->previousStatus;
    }

    public function getNewStatus(): int
    {
        return $this->newStatus;
    }
}
