<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class BaseUser implements AdvancedUserInterface, EquatableInterface
{

    private $username;
    private $password;
    private $salt;
    private $roles;
    private $isActive;
    private $clientId;

    public function __construct($username, $password, $salt, array $roles, $isActive, $clientId)
    {
        $this->username = $username;
        $this->password = $password;
        $this->salt     = $salt;
        $this->roles    = $roles;
        $this->isActive = $isActive;
        $this->clientId = $clientId;
    }

    /**
     * @inheritDoc
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @inheritDoc
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @inheritDoc
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @inheritDoc
     */
    public function getUsername()
    {
        return $this->username;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    /**
     * @inheritDoc
     */
    public function isEqualTo(UserInterface $user)
    {
        if (false === $user instanceof BaseUser) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }
    /**
     * @inheritDoc
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled()
    {
        return true; // TODO AB is validated? to check if client has status validated to have full feature access
    }

    /**
     * @inheritDoc
     */
    public function isAccountNonLocked()
    {
        return $this->isActive;
    }

}