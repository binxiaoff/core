<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

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
        $query  = $qb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }

    /**
     * @param $criteria
     * @param $operator
     * @return Clients[]
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
     * @param \DateTime $operationDateSince
     * @param float     $amount
     * @param bool      $sum
     * @return array
     */
    public function getClientsByDepositAmountAndDate(\DateTime $operationDateSince, $amount, $sum = false)
    {
        if (true === $sum) {
            $select = 'c.idClient, GROUP_CONCAT(o.id) as operation SUM(o.amount) as depositAmount';
        } else {
            $select = 'c.idClient, o.id as operation, o.amount as depositAmount';
        }
        $operationType = $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:OperationType');
        $qb            = $this->createQueryBuilder('c');
        $qb->select($select)
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'w.idClient = c.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:Operation', 'o', Join::WITH, 'o.idWallet = w.id')
            ->where('o.idType = :operation_type')
            ->setParameter('operation_type', $operationType->findOneBy(['label' => OperationType::LENDER_PROVISION]))
            ->andWhere('o.added >= :operation_date')
            ->setParameter('operation_date', $operationDateSince)
            ->andWhere('o.amount >= :operation_amount')
            ->setParameter('operation_amount', $amount)
            ->groupBy('c.idClient');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \DateTime $fromDate
     * @param int       $maxRibChange
     * @return array
     */
    public function getClientsWithMultipleBankAccountsOnPeriod(\DateTime $fromDate, $maxRibChange)
    {
        $query = '
        SELECT *
        FROM
          clients c
          INNER JOIN wallet w ON w.id_client = c.id_client
          INNER JOIN wallet_type wt ON wt.id = w.id_type
        WHERE
          wt.label = :wallet_type_label
          AND (SELECT COUNT(ba.id) FROM bank_account ba WHERE ba.id_client = c.id_client AND ba.added >= :from_date) >= :max_rib_change
        ';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, ['from_date' => $fromDate->format('Y-m-d H:i:s'), 'max_rib_change' => $maxRibChange, 'wallet_type_label' => WalletType::LENDER])->fetchAll();
    }

    /**
     * @param int $vigilanceStatus
     * @return array
     */
    public function getClientsByFiscalCountryStatus($vigilanceStatus)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->innerJoin('UnilendCoreBusinessBundle:ClientsAdresses', 'ca', Join::WITH, 'c.idClient = ca.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:PaysV2', 'p', Join::WITH, 'p.idPays= ca.idPaysFiscal')
            ->where('p.vigilanceStatus = :vigilance_status')
            ->setParameter('vigilance_status', $vigilanceStatus);

        return $qb->getQuery()->getResult();
    }
}
