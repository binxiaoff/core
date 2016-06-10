<?php

namespace Unilend\Bundle\FrontBundle\Security\User;


use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, EquatableInterface
{

    private $username;
    private $password;
    private $salt;
    private $roles;
    private $balance;
    private $initials;
    private $firstName;

    public function __construct($username, $password, $salt, array $roles, $balance, $initials, $firstName)
    {
        $this->username  = $username;
        $this->password  = $password;
        $this->salt      = $salt;
        $this->roles     = $roles;
        $this->balance   = $balance;
        $this->initials  = $initials;
        $this->firstName = $firstName;
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
        if (false === $user instanceof User) {
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

}