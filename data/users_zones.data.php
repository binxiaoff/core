<?php

use Unilend\Bridge\Doctrine\DBAL\Connection;

class users_zones extends users_zones_crud
{
    /**
     * users_zones constructor.
     *
     * @param Connection $bdd
     * @param string     $params
     */
    public function __construct(Connection $bdd, $params = '')
    {
        parent::users_zones($bdd, $params);
    }

    /**
     * @param int $userId
     * @return array
     */
    public function selectZonesUser($userId)
    {
        $statement = $this->bdd->createQueryBuilder()
            ->select('z.slug AS slug')
            ->from('users_zones', 'uz')
            ->innerJoin('uz', 'zones', 'z', 'z.id_zone = uz.id_zone')
            ->where('uz.id_user = :userId')
            ->orderBy('name', 'ASC')
            ->setParameter('userId', $userId)
            ->execute();

        $userZones = $statement->fetchAll();
        $statement->closeCursor();

        return array_column($userZones, 'slug');
    }
}
