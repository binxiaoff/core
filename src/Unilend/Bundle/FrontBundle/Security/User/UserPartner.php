<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

class UserPartner extends BaseUser
{
    /** @var string */
    private $firstName;
    /** @var string */
    private $lastName;

    public function __construct($username, $password, $email, $salt, array $roles, $isActive, $clientId, $hash, $firstName, $lastName, $lastLoginDate = null)
    {
        parent::__construct($username, $password, $email, $salt, $roles, $isActive, $clientId, $hash, $lastLoginDate);

        $this->firstName = $firstName;
        $this->lastName  = $lastName;
    }

    public function getInitials()
    {
        return substr($this->firstName, 0, 1) . substr($this->lastName, 0, 1);
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }
}
