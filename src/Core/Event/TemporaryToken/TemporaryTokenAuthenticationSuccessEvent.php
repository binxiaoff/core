<?php

declare(strict_types=1);

namespace KLS\Core\Event\TemporaryToken;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

class TemporaryTokenAuthenticationSuccessEvent extends Event
{
    /**
     * @var UserInterface
     */
    protected $user;

    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }

    /**
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }
}
