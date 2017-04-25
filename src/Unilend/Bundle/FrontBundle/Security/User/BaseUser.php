<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

use Symfony\Component\Security\Core\Encoder\EncoderAwareInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class BaseUser implements AdvancedUserInterface, EquatableInterface, EncoderAwareInterface
{
    private $username;
    private $password;
    private $email;
    private $salt;
    private $roles;
    private $isActive;
    private $clientId;
    private $hash;
    private $lastLoginDate;
    private $encoderName;

    public function __construct($username, $password, $email, $salt, array $roles, $isActive, $clientId, $hash, $lastLoginDate = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->email    = $email;
        $this->salt     = $salt;
        $this->roles    = $roles;
        $this->isActive = $isActive;
        $this->clientId = $clientId;
        $this->hash     = $hash;
        $this->lastLoginDate = $lastLoginDate;
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

    public function getEmail()
    {
        return $this->email;
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
    public function getEncoderName()
    {
        if ('default' === $this->encoderName) {
            return null;
        }

        if (1 === preg_match('/^[0-9a-f]{32}$/', $this->password)) {
            return 'md5';
        }

        return null; // use the default encoder
    }

    public function useDefaultEncoder()
    {
        $this->encoderName = 'default';
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    /**
     * @param UserInterface $user
     *
     * @return bool
     */
    public function isEqualTo(UserInterface $user)
    {
        if (false === $user instanceof BaseUser) {
            return false;
        }

        if ($this->hash !== $user->getHash()) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            if ($this->username !== $user->getUsername()) {
                return false;
            }
        }

        if ($this->username !== $user->getUsername()) {
            if ($this->password !== $user->getPassword()) {
                return false;
            }
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

    /**
     * @return string|null
     */
    public function getLastLoginDate()
    {
        return $this->lastLoginDate;
    }

}