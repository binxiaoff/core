<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Partner;

class UserPartner extends BaseUser
{
    /** @var string */
    private $firstName;
    /** @var string */
    private $lastName;
    /** @var Companies */
    private $company;
    /** @var Partner */
    private $partner;

    /**
     * @param string         $username
     * @param string         $password
     * @param string         $email
     * @param string         $salt
     * @param array          $roles
     * @param bool           $isActive
     * @param int            $clientId
     * @param string         $hash
     * @param string         $firstName
     * @param string         $lastName
     * @param Companies      $company
     * @param Partner        $partner
     * @param null|\DateTime $lastLoginDate
     */
    public function __construct($username, $password, $email, $salt, array $roles, $isActive, $clientId, $hash, $firstName, $lastName, Companies $company, Partner $partner, $lastLoginDate = null)
    {
        parent::__construct($username, $password, $email, $salt, $roles, $isActive, $clientId, $hash, $lastLoginDate);

        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->company   = $company;
        $this->partner   = $partner;
    }

    /**
     * @return string
     */
    public function getInitials()
    {
        return substr($this->firstName, 0, 1) . substr($this->lastName, 0, 1);
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @return Companies
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @return Partner
     */
    public function getPartner()
    {
        return $this->partner;
    }
}
