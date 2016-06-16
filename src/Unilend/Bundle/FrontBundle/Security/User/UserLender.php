<?php


namespace Unilend\Bundle\FrontBundle\Security\User;


class UserLender extends BaseUser
{
    /** @var float $balance */
    private $balance;
    /** @var  string $initials */
    private $initials;
    private $firstName;
    private $clientStatus;
    /** @var bool $hasAcceptedCurrentTerms */
    private $hasAcceptedCurrentTerms;
    /** @var  int $notificationsUnread */
    private $notificationsUnread;

    public function __construct($username, $password, $salt, array $roles, $balance, $initials, $firstName, $isActive, $clientStatus, $hasAcceptedCurrentTerms, $notificationsUnread)
    {
        parent::__construct($username, $password, $salt, $roles, $isActive);
        $this->balance                 = $balance;
        $this->initials                = $initials;
        $this->firstName               = $firstName;
        $this->clientStatus            = $clientStatus;
        $this->hasAcceptedCurrentTerms = $hasAcceptedCurrentTerms;
        $this->notificationsUnread     = $notificationsUnread;
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

}
