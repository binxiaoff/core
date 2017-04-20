<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

class UserBorrower extends BaseUser
{
    /** @var string */
    private $firstName;
    /** @var  string */
    private $lastName;
    /** @var  string */
    private $siren;
    /** @var  float */
    private $balance;

    /**
     * UserBorrower constructor.
     * @param string $username
     * @param string $password
     * @param string $email
     * @param string $salt
     * @param array  $roles
     * @param bool   $isActive
     * @param int    $clientId
     * @param string $hash
     * @param string $firstName
     * @param string $lastName
     * @param string $siren
     * @param float  $balance
     * @param null   $lastLoginDate
     */
    public function __construct(
        $username,
        $password,
        $email,
        $salt,
        array $roles,
        $isActive,
        $clientId,
        $hash,
        $firstName,
        $lastName,
        $siren,
        $balance,
        $lastLoginDate = null
    ) {
        parent::__construct($username, $password, $email, $salt, $roles, $isActive, $clientId, $hash, $lastLoginDate, $balance);

        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->siren     = $siren;
    }

    /**
     * @return string
     */
    public function getInitials()
    {
        return substr($this->firstName, 0, 1) . substr($this->lastName, 0, 1);
    }

    /**
     * @return null|string
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
     * @return string
     */
    public function getSiren()
    {
        return $this->siren;
    }

    /**
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
    }
}
