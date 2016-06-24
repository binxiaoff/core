<?php


namespace Unilend\Bundle\FrontBundle\Security\User;

use Unilend\Bundle\FrontBundle\Security\User\BaseUser;

class UserBorrower extends BaseUser
{

    public function __construct($username, $password, $salt, array $roles, $isActive, $clientId)
    {
        parent::__construct($username, $password, $salt, $roles, $isActive, $clientId);
    }

}