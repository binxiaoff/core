<?php
/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 27/06/2017
 * Time: 14:29
 */

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\InfolegaleExecutivePersonalChange;

class InfolegaleExecutivePersonalChangeRepository extends EntityRepository
{
    /**
     * @param string $siren
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getActiveExecutives(string $siren) : array
    {
        $query = '
            SELECT DISTINCT id_executive AS idExecutive, title, first_name AS firstName, last_name AS lastName, position, code_position AS codePosition, nominated
                FROM infolegale_executive_personal_change
                WHERE siren = :siren
                AND ended IS NULL
                AND title NOT LIKE :company
            UNION
                SELECT DISTINCT id_executive AS idExecutive, title, first_name AS firstName, last_name AS lastName, position, code_position AS codePosition, nominated
                FROM infolegale_executive_personal_change
                WHERE siren IN (
                  SELECT DISTINCT id_executive
                  FROM infolegale_executive_personal_change
                  WHERE siren = :siren
                  AND ended IS NULL
                  AND siren_if_company IS NOT NULL
                )
                AND ended IS NULL';

        return $this->getEntityManager()->getConnection()->executeQuery($query, ['siren' => $siren, 'company' => 'Ste'], [])->fetchAll();
    }

    /**
     * @param string    $siren
     * @param \DateTime $date
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getPreviousExecutivesLeftAfter(string $siren, \DateTime $date) : array
    {
        $currentExecutives = array_column($this->getActiveExecutives($siren), 'idExecutive');

        $qb = $this->createQueryBuilder('iepc');
        $qb->select('iepc.idExecutive')
            ->distinct()
            ->where('iepc.siren = :siren')
            ->andWhere('iepc.ended >= :date')
            ->andWhere('iepc.idExecutive NOT IN (:currentExecutives)')
            ->setParameter('siren', $siren)
            ->setParameter('currentExecutives', $currentExecutives)
            ->setParameter('date', $date);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param string $siren
     * @param \DateTime $since
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAllMandatesExceptGivenSirenOnActiveExecutives(string $siren, \DateTime $since) : array
    {
        $currentExecutives = array_column($this->getActiveExecutives($siren), 'idExecutive');

        $queryBuilder = $this->createQueryBuilder('iepc');
        $queryBuilder->select('iepc.siren')->distinct()
            ->where('iepc.idExecutive IN (:currentExecutives)')
            ->andWhere('iepc.siren != :siren')
            ->andWhere('iepc.ended IS NULL OR iepc.ended >= :mandateEndDate')
            ->setParameter('currentExecutives', $currentExecutives)
            ->setParameter('siren', $siren)
            ->setParameter('mandateEndDate', $since);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * @param array     $executiveIds
     * @param \DateTime $mandateEndDate
     *
     * @return InfolegaleExecutivePersonalChange[]
     */
    public function findMandatesByExecutivesSince(array $executiveIds, \DateTime $mandateEndDate) : array
    {
        $queryBuilder = $this->createQueryBuilder('iepc');
        $queryBuilder->where('iepc.idExecutive IN (:executiveIds)')
            ->setParameter('executiveIds', $executiveIds)
            ->andWhere('iepc.ended IS NULL OR iepc.ended >= :mandateEndDate')
            ->setParameter('mandateEndDate', $mandateEndDate->format('Y-m-d'));

        return $queryBuilder->getQuery()->getResult();
    }
}
