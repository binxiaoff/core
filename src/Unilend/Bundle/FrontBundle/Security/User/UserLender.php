<?php


namespace Unilend\Bundle\FrontBundle\Security\User;


class UserLender extends BaseUser
{
    /** @var float $balance */
    private $balance;
    /** @var  string $initials */
    private $initials;
    /** @var  string */
    private $firstName;
    /** @var  int */
    private $clientStatus;
    /** @var bool $hasAcceptedCurrentTerms */
    private $hasAcceptedCurrentTerms;
    /** @var  int $notificationsUnread */
    private $notificationsUnread;
    /** @var  int */
    private $subscriptionStep;
    /** @var  int  */
    private $level;

    public function __construct($username, $password, $salt, array $roles, $isActive, $clientId, $balance, $initials, $firstName, $clientStatus, $hasAcceptedCurrentTerms, $notificationsUnread, $subscriptionStep, $level)
    {
        parent::__construct($username, $password, $salt, $roles, $isActive, $clientId);
        $this->balance                 = $balance;
        $this->initials                = $initials;
        $this->firstName               = $firstName;
        $this->clientStatus            = $clientStatus;
        $this->hasAcceptedCurrentTerms = $hasAcceptedCurrentTerms;
        $this->notificationsUnread     = $notificationsUnread;
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

    public function getClientStatus()
    {
        return $this->clientStatus;
    }

    public function getHasAcceptedCurrentTerms()
    {
        return $this->hasAcceptedCurrentTerms;
    }

    public function getNotificationsUnread()
    {
        return $this->notificationsUnread;
    }

    public function getSubscriptionStep()
    {
        return $this->subscriptionStep;
    }

    public function getLevel()
    {
        return $this->level;
    }

}
