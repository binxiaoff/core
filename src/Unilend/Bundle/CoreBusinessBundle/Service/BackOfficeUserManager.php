<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\UsersTypes;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;

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
        if (UsersTypes::TYPE_RISK == $user->getIdUserType()->getIdUserType() || $user->getIdUser() == Users::USER_ID_ALAIN_ELKAIM) {
            return true;
        }

        return false;
    }

    /**
     * @param Users $user
     *
     * @return bool
     */
    public function isUserGroupCompliance(Users $user)
    {
        if (UsersTypes::TYPE_COMPLIANCE == $user->getIdUserType()->getIdUserType() || $user->getIdUser() == Users::USER_ID_NICOLAS_LESUR) {
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
        if (UsersTypes::TYPE_IT == $user->getIdUserType()->getIdUserType()) {
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
        if (UsersTypes::TYPE_COMMERCIAL == $user->getIdUserType()->getIdUserType() || $user->getIdUser() == Users::USER_ID_ARNAUD_SCHWARTZ) {
            return true;
        }

        return false;
    }


    /**
     * @param Users $user
     *
     * @return bool
     */
    public function isGrantedRisk(Users $user)
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
    public function isGrantedIT(Users $user)
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
    public function isGrantedManagement(Users $user)
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
    public function isGrantedSales(Users $user)
    {
        if (in_array($user->getIdUserType()->getIdUserType(), [UsersTypes::TYPE_COMMERCIAL, UsersTypes::TYPE_ADMIN]) || $user->getIdUser() == Users::USER_ID_ARNAUD_SCHWARTZ) {
            return true;
        }

        return false;
    }

    /**
     * @param Users $user
     * @param Zones|string $zone
     *
     * @return bool
     */
    public function hasAccessToZone(Users $user, $zone): bool
    {
        if (is_string($zone)) {
            $zone = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Zones')->findOneBy(['slug' => $zone]);
        }
        if ($zone) {
            if ($this->entityManager->getRepository('UnilendCoreBusinessBundle:UsersZones')->findOneBy(['idUser' => $user, 'idZone' => $zone])) {
                return true;
            }
        }

        return false;
    }
}
