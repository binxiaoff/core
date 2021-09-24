<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Traits;

use KLS\Core\Entity\Message;
use KLS\Core\Entity\MessageThread;
use KLS\Core\Entity\Staff;

trait MessageTrait
{
    private function createMessage(Staff $staff): Message
    {
        return new Message($staff, new MessageThread(), 'message body');
    }
}
