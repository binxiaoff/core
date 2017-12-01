<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\UsersTypes;

class BackOfficeUserManager
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Users $user
     *
     * @return bool
     */
    public function isUserGroupRisk(Users $user)
    {
        if (in_array($user->getIdUserType()->getIdUserType(), [UsersTypes::TYPE_RISK, UsersTypes::TYPE_ADMIN]) || $user->getIdUser() == Users::USER_ID_ALAIN_ELKAIM) {
            return true;
        }

        return false;
    }

    /**
     * @param Users $user
     *
     * @return bool
     */
    public function isUserGroupIT(Users $user)
    {
        if (in_array($user->getIdUserType()->getIdUserType(), [UsersTypes::TYPE_ADMIN, UsersTypes::TYPE_IT])) {
            return true;
        }

        return false;
    }

    /**
     * @param Users $user
     *
     * @return bool
     */
    public function isUserGroupManagement(Users $user)
    {
        if (in_array($user->getIdUserType()->getIdUserType(), [UsersTypes::TYPE_DIRECTION, UsersTypes::TYPE_ADMIN])) {
           return true;
        }

        return false;
    }

    /**
     * @param Users $user
     *
     * @return bool
     */
    public function isUserGroupSales(Users $user)
    {
        if (in_array($user->getIdUserType()->getIdUserType(), [UsersTypes::TYPE_COMMERCIAL, UsersTypes::TYPE_ADMIN]) || $user->getIdUser() == Users::USER_ID_ARNAUD_SCHWARTZ) {
            return true;
        }

        return false;
    }
}
