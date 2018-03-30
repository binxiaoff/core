<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Companies, Partner
};

class UserPartner extends BaseUser
{
    const ROLE_DEFAULT = 'ROLE_PARTNER';
    const ROLE_ADMIN   = 'ROLE_PARTNER_ADMIN';
    const ROLE_USER    = 'ROLE_PARTNER_USER';

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
     * @param string|null    $password
     * @param string         $email
     * @param string|null    $salt
     * @param string[]       $roles
     * @param bool           $isActive
     * @param int            $clientId
     * @param string         $hash
     * @param int            $clientStatus
     * @param string|null    $firstName
     * @param string|null    $lastName
     * @param Companies      $company
     * @param Partner        $partner
     * @param null|\DateTime $lastLoginDate
     */
    public function __construct(
        string $username,
        ?string $password,
        string $email,
        ?string $salt,
        array $roles,
        bool $isActive,
        int $clientId,
        string $hash,
        int $clientStatus,
        ?string $firstName,
        ?string $lastName,
        Companies $company,
        Partner $partner,
        ?\DateTime $lastLoginDate = null)
    {
        parent::__construct($username, $password, $email, $salt, $roles, $isActive, $clientId, $hash, $clientStatus, $lastLoginDate);

        $this->firstName = $firstName;
        $this->lastName  = $lastName;
        $this->company   = $company;
        $this->partner   = $partner;
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
     * @return Companies
     */
    public function getCompany(): Companies
    {
        return $this->company;
    }

    /**
     * @return Partner
     */
    public function getPartner(): Partner
    {
        return $this->partner;
    }
}
