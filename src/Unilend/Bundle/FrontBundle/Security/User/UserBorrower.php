<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

class UserBorrower extends BaseUser
{
    private $firstName;
    private $lastName;
    private $siren;

    public function __construct($username, $password, $salt, array $roles, $isActive, $clientId, $hash, $firstName, $lastName, $siren, $lastLoginDate = null)
    {
        parent::__construct($username, $password, $salt, $roles, $isActive, $clientId, $hash, $lastLoginDate);

        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->siren     = $siren;
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

    public function getSiren()
    {
        return $this->siren;
    }
}
