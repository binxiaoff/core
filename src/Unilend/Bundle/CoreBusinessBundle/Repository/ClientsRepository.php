<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\AbstractQuery;
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
            $select = 'c.idClient, o.id as operation, SUM(o.amount) as depositAmount'; // @todo find a solution to get concatenation of operation IDs
        } else {
            $select = 'c.idClient, o.id as operation, o.amount as depositAmount';
        }
        $operationType = $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:OperationType');
        $qb            = $this->createQueryBuilder('c')
            ->select($select)
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'w.idClient = c.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:Operation', 'o', Join::WITH, 'o.idWalletCreditor = w.id')
            ->where('o.idType = :operation_type')
            ->setParameter('operation_type', $operationType->findOneBy(['label' => OperationType::LENDER_PROVISION]))
            ->andWhere('o.added >= :operation_date')
            ->setParameter('operation_date', $operationDateSince)
            ->having('depositAmount >= :operation_amount')
            ->setParameter('operation_amount', $amount);

        if (true === $sum) {
            $qb->groupBy('o.idWalletCreditor');
        }

        return $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);
    }

    /**
     * @param \DateTime $fromDate
     * @param int       $maxRibChange
     * @return array
     */
    public function getClientsWithMultipleBankAccountsOnPeriod(\DateTime $fromDate, $maxRibChange)
    {
        $query = '
        SELECT c.id_client,
          w.id,
          (SELECT COUNT(*) FROM bank_account ba WHERE ba.id_client = c.id_client AND ba.added >= :from_date) AS nb_rib_change
        FROM
          clients c
          INNER JOIN wallet w ON w.id_client = c.id_client
          INNER JOIN wallet_type wt ON wt.id = w.id_type
        WHERE
          wt.label = :wallet_type_label
        HAVING nb_rib_change >= :max_rib_change
        ';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                $query,
                [
                    'from_date' => $fromDate->format('Y-m-d H:i:s'),
                    'max_rib_change' => $maxRibChange,
                    'wallet_type_label' => WalletType::LENDER
                ]
            )->fetchAll();
    }

    /**
     * @param int       $vigilanceStatus
     * @param \DateTime $date
     * @return array
     */
    public function getClientsByFiscalCountryStatus($vigilanceStatus, \DateTime $date)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c.idClient, ca.idPaysFiscal, p.fr as countryLabel')
            ->innerJoin('UnilendCoreBusinessBundle:ClientsAdresses', 'ca', Join::WITH, 'c.idClient = ca.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:PaysV2', 'p', Join::WITH, 'p.idPays= ca.idPaysFiscal')
            ->where('p.vigilanceStatus = :vigilance_status')
            ->setParameter('vigilance_status', $vigilanceStatus)
            ->andWhere('c.added >= :added_date OR ca.updated >= :updated_date')
            ->setParameter('added_date', $date)
            ->setParameter('updated_date', $date);

        return $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);
    }
}
