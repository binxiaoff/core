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

    public function __construct(
        $username,
        $password,
        $email,
        $salt,
        array $roles,
        $isActive,
        $clientId,
        $hash,
        $balance,
        $initials,
        $firstName,
        $lastName,
        $clientStatus,
        $hasAcceptedCurrentTerms,
        array $notifications,
        $subscriptionStep,
        $level,
        $lastLoginDate = null
    )
    {
        parent::__construct($username, $password, $email, $salt, $roles, $isActive, $clientId, $hash, $lastLoginDate);

        $this->balance                 = $balance;
        $this->initials                = $initials;
        $this->firstName               = $firstName;
        $this->lastName               = $lastName;
        $this->clientStatus            = $clientStatus;
        $this->hasAcceptedCurrentTerms = $hasAcceptedCurrentTerms;
        $this->notifications           = $notifications;
        $this->subscriptionStep        = $subscriptionStep;
        $this->level                   = $level;
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

    public function getLastName()
    {
        return $this->lastName;
    }

    public function getClientStatus()
    {
        return $this->clientStatus;
    }

    public function hasAcceptedCurrentTerms()
    {
        return $this->hasAcceptedCurrentTerms;
    }

    public function getNotifications()
    {
        return $this->notifications;
    }

    public function getUnreadNotificationsCount()
    {
        if (in_array('unread', array_column($this->notifications, 'status'))) {
            return array_count_values(array_column($this->notifications, 'status'))['unread'];
        }

        return 0;
    }

    public function getSubscriptionStep()
    {
        return $this->subscriptionStep;
    }

    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param double $balance
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
    }
}
