<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

class UserBorrower extends BaseUser
{
    /** @var string */
    private $firstName;
    /** @var string */
    private $lastName;
    /** @var string */
    private $siren;
    /** @var float */
    private $balance;

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
     * @param string         $siren
     * @param float          $balance
     * @param \DateTime|null $lastLoginDate
     */
    public function __construct(
        string $username,
        string $password,
        string $email,
        string $salt,
        array $roles,
        bool $isActive,
        int $clientId,
        string $hash,
        string $firstName,
        string $lastName,
        string $siren,
        float $balance,
        ?\DateTime $lastLoginDate = null
    )
    {
        parent::__construct($username, $password, $email, $salt, $roles, $isActive, $clientId, $hash, $lastLoginDate);

        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->siren     = $siren;
        $this->balance   = $balance;
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
