<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Users, UsersTypes, Zones
};

class BackOfficeUserManager
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
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
        if (UsersTypes::TYPE_RISK === $user->getIdUserType()->getIdUserType()) {
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
        if (UsersTypes::TYPE_COMPLIANCE === $user->getIdUserType()->getIdUserType()) {
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
        if (UsersTypes::TYPE_COMMERCIAL === $user->getIdUserType()->getIdUserType()) {
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
        if (in_array($user->getIdUserType()->getIdUserType(), [UsersTypes::TYPE_RISK, UsersTypes::TYPE_ADMIN])) {
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
        if (in_array($user->getIdUserType()->getIdUserType(), [UsersTypes::TYPE_COMMERCIAL, UsersTypes::TYPE_ADMIN])) {
            return true;
        }

        return false;
    }

    /**
     * @param Users        $user
     * @param Zones|string $zone
     *
     * @return bool
     */
    public function isGrantedZone(Users $user, $zone): bool
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

    /**
     * @return array|Users[]
     */
    public function getSalesPersons(): array
    {
        $userRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Users');
        $salesPersons   = $userRepository->findBy(['status' => Users::STATUS_ONLINE, 'idUserType' => UsersTypes::TYPE_COMMERCIAL]);

        return $salesPersons;
    }

    /**
     * @return array
     */
    public function getAnalysts(): array
    {
        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:Users')->findBy(['status' => Users::STATUS_ONLINE, 'idUserType' => UsersTypes::TYPE_RISK]);
    }
}
