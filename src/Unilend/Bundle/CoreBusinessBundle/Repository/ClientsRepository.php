<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

class ClientsRepository extends EntityRepository
{

    /**
     * @param integer|Clients $idClient
     * @return mixed
     */
    public function getCompany($idClient)
    {
        if ($idClient instanceof Clients) {
            $idClient = $idClient->getIdClient();
        }

        $qb = $this->createQueryBuilder('c');
        $qb->select('co')
            ->innerJoin('UnilendCoreBusinessBundle:Companies', 'co', Join::WITH, 'c.idClient = co.idClientOwner')
            ->where('c.idClient = :idClient')
            ->setParameter('idClient', $idClient);
        $query = $qb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }

    /**
     * @param $criteria
     * @param $operator
     * @return array
     */
    public function getClientsBy($criteria, $operator)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c');

        foreach ($criteria as $field => $value) {
            $qb->andWhere('c.' . $field . $operator[$field] . ':' . $field)
                ->setParameter($field, $value);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \DateTime $fromDate
     * @return array
     */
    public function getClientsWithMultipleBankAccountsOnPeriod(\DateTime $fromDate)
    {
        $query  = '
                SELECT *
                FROM
                  clients c INNER JOIN lenders_accounts la ON la.id_client_owner = c.id_client
                WHERE
                  (SELECT COUNT(ba.id) FROM bank_account ba WHERE ba.id_client = c.id_client AND ba.added >= :fromDate) >= 2
                  ';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, ['fromDate' => $fromDate->format('Y-m-d H:i:s')])->fetchAll();
    }
}
