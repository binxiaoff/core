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
     * @param string|null    $password
     * @param string         $email
     * @param string|null    $salt
     * @param string[]       $roles
     * @param int            $clientId
     * @param string         $hash
     * @param int            $clientStatus
     * @param string|null    $firstName
     * @param string|null    $lastName
     * @param string         $siren
     * @param float          $balance
     * @param \DateTime|null $lastLoginDate
     */
    public function __construct(
        string $username,
        ?string $password,
        string $email,
        ?string $salt,
        array $roles,
        int $clientId,
        string $hash,
        int $clientStatus,
        ?string $firstName,
        ?string $lastName,
        string $siren,
        float $balance,
        ?\DateTime $lastLoginDate = null
    )
    {
        parent::__construct($username, $password, $email, $salt, $roles, $clientId, $hash, $clientStatus, $lastLoginDate);

        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->siren     = $siren;
        $this->balance   = $balance;
    }

    /**
     * @return string
     */
    public function getInitials(): string
    {
        return substr($this->firstName, 0, 1) . substr($this->lastName, 0, 1);
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getSiren(): string
    {
        return $this->siren;
    }

    /**
     * @return float
     */
    public function getBalance(): float
    {
        return $this->balance;
    }
}
