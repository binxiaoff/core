<?php

declare(strict_types=1);

namespace KLS\Core\Message\User;

use KLS\Core\Entity\User;
use KLS\Core\Message\AsyncMessageInterface;

class UserUpdated implements AsyncMessageInterface
{
    /** @var int */
    private $userId;
    /** @var array */
    private $changeSet;

    public function __construct(User $user, array $changeSet)
    {
        $this->userId    = $user->getId();
        $this->changeSet = $changeSet;
    }

    public function getChangeSet(): array
    {
        return $this->changeSet;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
