<?php


namespace Unilend\Bundle\FrontBundle\Security\User;


class UserLender extends BaseUser
{
    private $balance;
    private $initials;
    private $firstName;
    private $clientStatus;
    private $hasAcceptedCurrentTerms;

    public function __construct($username, $password, $salt, array $roles, $balance, $initials, $firstName, $isActive, $clientStatus, $hasAcceptedCurrentTerms)
    {
        parent::__construct($username, $password, $salt, $roles, $isActive);
        $this->balance                 = $balance;
        $this->initials                = $initials;
        $this->firstName               = $firstName;
        $this->clientStatus            = $clientStatus;
        $this->hasAcceptedCurrentTerms = $hasAcceptedCurrentTerms;
    }

    public function getBalance()
    {
        return $this->balance;
    }

    public function getInitials()
    {
        return $this->initials;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getClientStatus()
    {
        return $this->clientStatus;
    }

    public function getHasAcceptedCurrentTerms()
    {
        return $this->hasAcceptedCurrentTerms;
    }

}
