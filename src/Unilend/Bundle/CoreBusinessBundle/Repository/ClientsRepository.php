<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use PDO;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class ClientsRepository extends EntityRepository
{

    /**
     * @param integer|Clients $idClient
     *
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
     * @param $email
     *
     * @return bool
     */
    public function existEmail($email)
    {
        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->select('COUNT(c)')
            ->where('c.email = :email')
            ->setParameter('email', $email);
        $query = $queryBuilder->getQuery();

        return $query->getSingleScalarResult() > 0;
    }

    /**
     * @param \DateTime $birthDate
     * @param \DateTime $subscriptionDate
     *
     * @return Clients[]
     */
    public function getClientByAgeAndSubscriptionDate(\DateTime $birthDate, \DateTime $subscriptionDate)
    {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClient = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'wt.id = w.idType AND wt.label = :lender')
            ->where('c.naissance <= :birthDate')
            ->andWhere('c.added >= :added')
            ->andWhere('c.type IN (:physicalPerson)')
            ->setParameter('birthDate', $birthDate)
            ->setParameter('added', $subscriptionDate)
            ->setParameter('lender', WalletType::LENDER)
            ->setParameter('physicalPerson', [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER], Connection::PARAM_INT_ARRAY);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \DateTime $operationDateSince
     * @param float     $amount
     * @param bool      $sum
     *
     * @return array
     */
    public function getClientsByDepositAmountAndDate(\DateTime $operationDateSince, $amount, $sum = false)
    {
        if (true === $sum) {
            $select = 'c.idClient, GROUP_CONCAT(o.id) as operation, SUM(o.amount) as depositAmount';
        } else {
            $select = 'c.idClient, o.id as operation, o.amount as depositAmount';
        }
        $operationType = $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:OperationType');

        $qb = $this->createQueryBuilder('c')
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
     *
     * @return array
     */
    public function getClientsWithMultipleBankAccountsOnPeriod(\DateTime $fromDate, $maxRibChange)
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.idClient, COUNT(ba.id) AS nbRibChange')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'w.idClient = c.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'wt.id = w.idType')
            ->innerJoin('UnilendCoreBusinessBundle:BankAccount', 'ba', Join::WITH, 'ba.idClient = w.idClient')
            ->where('wt.label = :lender')
            ->setParameter('lender', WalletType::LENDER)
            ->andWhere('ba.dateValidated >= :fromDate')
            ->setParameter('fromDate', $fromDate)
            ->groupBy('c.idClient')
            ->having('nbRibChange >= :maxRibChange')
            ->setParameter('maxRibChange', $maxRibChange);

        return $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);
    }

    /**
     * @param int       $vigilanceStatus
     * @param \DateTime $date
     *
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

    /**
     * @param array $status
     *
     * @return Clients[]
     */
    public function getLendersInStatus(array $status)
    {
        $qb  = $this->createQueryBuilder('c');
        $qb->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'c.idClient = csh.idClient')
           ->innerJoin('UnilendCoreBusinessBundle:ClientsStatus', 'cs', Join::WITH, 'csh.idClientStatus = cs.idClientStatus')
           ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClient = w.idClient')
           ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
           ->where(
               $qb->expr()->eq(
                   'csh.idClientStatusHistory',
                   '(SELECT MAX(csh1 . idClientStatusHistory) FROM UnilendCoreBusinessBundle:ClientsStatusHistory csh1 WHERE csh1.idClient = csh.idClient)'
               )
           )
           ->andWhere('wt.label = :lender')
           ->andWhere('cs.status IN (:status)')
           ->setParameter(':lender', WalletType::LENDER)
           ->setParameter('status', $status, Connection::PARAM_INT_ARRAY);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param $limit
     *
     * @return array
     */
    public function getLendersToMatchCity($limit)
    {
        $query = 'SELECT * FROM (
                    SELECT c.id_client, ca.id_adresse, c.prenom, c.nom, ca.cp_fiscal AS zip, ca.ville_fiscal AS city, ca.cp, ca.ville, 0 AS is_company
                    FROM clients_adresses ca
                    INNER JOIN clients c ON ca.id_client = c.id_client
                    INNER JOIN wallet w ON c.id_client = w.id_client
                    INNER JOIN wallet_type wt ON w.id_type = wt.id
                    WHERE c.status = ' . Clients::STATUS_ONLINE . '
                      AND wt.label = "' . WalletType::LENDER . '"
                      AND (ca.id_pays_fiscal = ' . PaysV2::COUNTRY_FRANCE . ' OR ca.id_pays_fiscal = 0)
                      AND la.id_company_owner = 0
                      AND (
                        NOT EXISTS (SELECT cp FROM villes v WHERE v.cp = ca.cp_fiscal)
-                        OR (SELECT COUNT(*) FROM villes v WHERE v.cp = ca.cp_fiscal AND v.ville = ca.ville_fiscal) <> 1
                      )
                  LIMIT :limit
                ) perso
                UNION
                SELECT * FROM (
                  SELECT c.id_client, ca.id_adresse, c.prenom, c.nom, co.zip, co.city, ca.cp, ca.ville, 1 AS is_company
                  FROM clients_adresses ca
                    INNER JOIN clients c ON ca.id_client = c.id_client
                    INNER JOIN wallet w ON c.id_client = w.id_client
                    INNER JOIN wallet_type wt ON w.id_type = wt.id
                    INNER JOIN companies co ON co.id_client_owner = ca.id_client
                  WHERE c.status = ' . Clients::STATUS_ONLINE . '
                    AND wt.label = "' . WalletType::LENDER . '"
                    AND (ca.id_pays_fiscal = ' . PaysV2::COUNTRY_FRANCE . ' OR ca.id_pays_fiscal = 0)
                    AND (
                      NOT EXISTS (SELECT cp FROM villes v WHERE v.cp = co.zip)
                      OR (SELECT COUNT(*) FROM villes v WHERE v.cp = co.zip AND v.ville = co.city) <> 1
                    )  LIMIT :limit
                ) company';

        $result =  $this->getEntityManager()->getConnection()
            ->executeQuery($query, ['limit' => floor($limit / 2)], ['limit' => PDO::PARAM_INT])
            ->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * @param int $limit
     *
     * @return array
     */
    public function getLendersToMatchBirthCity($limit)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c.idClient, c.prenom, c.nom, c.villeNaissance')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClient = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->andWhere('wt.label = :lender')
            ->andWhere('c.status = :statusOnline')
            ->andWhere('c.idPaysNaissance = :France')
            ->andWhere('c.inseeBirth IS NULL')
            ->setParameter('lender', WalletType::LENDER)
            ->setParameter('statusOnline', Clients::STATUS_ONLINE)
            ->setParameter('France', PaysV2::COUNTRY_FRANCE)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * if true only lenders activated at least once (active lenders)
     * if false all online lender (Community)
     * @param bool $onlyActive
     *
     * @return int
     */
    public function countLenders($onlyActive = false)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('COUNT(DISTINCT(c.idClient))')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClient = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->andWhere('wt.label = :lender')
            ->andWhere('c.status = :statusOnline')
            ->setParameter('lender', WalletType::LENDER)
            ->setParameter('statusOnline', Clients::STATUS_ONLINE);

        if ($onlyActive) {
            $qb->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'csh.idClient = c.idClient AND csh.idClientStatus = 6');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array $clientType
     * @param bool  $onlyActive
     *
     * @return int
     */
    public function countLendersByClientType(array $clientType, $onlyActive = false)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('COUNT(DISTINCT(c.idClient))')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClient = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->andWhere('wt.label = :lender')
            ->andWhere('c.status = :statusOnline')
            ->andWhere('c.type IN (:types)')
            ->setParameter('lender', WalletType::LENDER)
            ->setParameter('statusOnline', Clients::STATUS_ONLINE)
            ->setParameter('types', $clientType, Connection::PARAM_INT_ARRAY);

        if ($onlyActive) {
            $qb->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'csh.idClient = c.idClient AND csh.idClientStatus = 6');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
