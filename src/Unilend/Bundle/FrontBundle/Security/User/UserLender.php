<?php

namespace Unilend\Bundle\FrontBundle\Security\User;

class UserLender extends BaseUser
{
    /** @var float $balance */
    private $balance;
    /** @var string $initials */
    private $initials;
    /** @var string */
    private $firstName;
    /** @var string */
    private $lastName;
    /** @var int */
    private $clientStatus;
    /** @var bool $hasAcceptedCurrentTerms */
    private $hasAcceptedCurrentTerms;
    /** @var array $notifications */
    private $notifications;
    /** @var int */
    private $subscriptionStep;
    /** @var int */
    private $level;

    /**
     * @param string         $username
     * @param string         $password
     * @param string         $email
     * @param string         $salt
     * @param array          $roles
     * @param bool           $isActive
     * @param int            $clientId
     * @param string         $hash
     * @param float          $balance
     * @param string         $initials
     * @param string         $firstName
     * @param string         $lastName
     * @param int            $clientStatus
     * @param bool           $hasAcceptedCurrentTerms
     * @param array          $notifications
     * @param int            $subscriptionStep
     * @param int            $level
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
        float $balance,
        string $initials,
        string $firstName,
        string $lastName,
        int $clientStatus,
        bool $hasAcceptedCurrentTerms,
        array $notifications,
        int $subscriptionStep,
        int $level,
        ?\DateTime $lastLoginDate = null
    )
    {
        parent::__construct($username, $password, $email, $salt, $roles, $isActive, $clientId, $hash, $lastLoginDate);

        $this->balance                 = $balance;
        $this->initials                = $initials;
        $this->firstName               = $firstName;
        $this->lastName                = $lastName;
        $this->clientStatus            = $clientStatus;
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
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @return int
     */
    public function getClientStatus(): int
    {
        return $this->clientStatus;
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
    public function getUnreadNotificationsCount(): int
    {
        if (in_array('unread', array_column($this->notifications, 'status'))) {
            return array_count_values(array_column($this->notifications, 'status'))['unread'];
        }

        return 0;
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
