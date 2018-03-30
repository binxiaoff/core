<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

class UserLender extends BaseUser
{
    /** @var float */
    private $balance;
    /** @var string */
    private $initials;
    /** @var string */
    private $firstName;
    /** @var string */
    private $lastName;
    /** @var bool */
    private $hasAcceptedCurrentTerms;
    /** @var array */
    private $notifications;
    /** @var int */
    private $subscriptionStep;
    /** @var int */
    private $level;

    /**
     * @param string         $username
     * @param string|null    $password
     * @param string         $email
     * @param string|null    $salt
     * @param string[]       $roles
     * @param int            $clientId
     * @param string         $hash
     * @param int            $clientStatus
     * @param float          $balance
     * @param string         $initials
     * @param string|null    $firstName
     * @param string|null    $lastName
     * @param bool           $hasAcceptedCurrentTerms
     * @param array          $notifications
     * @param int            $subscriptionStep
     * @param int            $level
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
        float $balance,
        string $initials,
        ?string $firstName,
        ?string $lastName,
        bool $hasAcceptedCurrentTerms,
        array $notifications,
        int $subscriptionStep,
        int $level,
        ?\DateTime $lastLoginDate = null
    )
    {
        parent::__construct($username, $password, $email, $salt, $roles, $clientId, $hash, $clientStatus, $lastLoginDate);

        $this->balance                 = $balance;
        $this->initials                = $initials;
        $this->firstName               = $firstName;
        $this->lastName                = $lastName;
        $this->hasAcceptedCurrentTerms = $hasAcceptedCurrentTerms;
        $this->notifications           = $notifications;
        $this->subscriptionStep        = $subscriptionStep;
        $this->level                   = $level;
    }

    /**
     * @return float
     */
    public function getBalance(): float
    {
        return $this->balance;
    }

    /**
     * @return string
     */
    public function getInitials(): string
    {
        return $this->initials;
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
     * @return bool
     */
    public function hasAcceptedCurrentTerms(): bool
    {
        return $this->hasAcceptedCurrentTerms;
    }

    /**
     * @return array
     */
    public function getNotifications(): array
    {
        return $this->notifications;
    }

    /**
     * @return int
     */
    public function getSubscriptionStep(): int
    {
        return $this->subscriptionStep;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param float $balance
     */
    public function setBalance(float $balance): void
    {
        $this->balance = $balance;
    }
}
