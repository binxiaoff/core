<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
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

    public function getLendersSalesForce()
    {
        $query = "SELECT
                      c.id_client as 'IDClient',
                      c.id_client as 'IDPreteur',
                      c.id_langue as 'Langue',
                      REPLACE(c.source,',','') as 'Source1',
                      REPLACE(c.source2,',','') as 'Source2',
                      REPLACE(c.source3,',','') as 'Source3',
                      REPLACE(c.civilite,',','') as 'Civilite',
                      REPLACE(c.nom,',','') as 'Nom',
                      REPLACE(c.nom_usage,',','') as 'NomUsage',
                      REPLACE(c.prenom,',','') as 'Prenom',
                      REPLACE(c.fonction,',','') as 'Fonction',
                      CASE c.naissance
                      WHEN '0000-00-00' then '2001-01-01'
                      ELSE
                        CASE SUBSTRING(c.naissance,1,1)
                        WHEN '0' then '2001-01-01'
                        ELSE c.naissance
                        END
                      END as 'Datenaissance',
                      REPLACE(ville_naissance,',','') as 'Villenaissance',
                      ccountry.fr as 'PaysNaissance',
                      nv2.fr_f as 'Nationalite',
                      REPLACE(c.telephone,'\t','') as 'Telephone',
                      REPLACE(c.mobile,',','') as 'Mobile',
                      REPLACE(c.email,',','') as 'Email',
                      c.etape_inscription_preteur as 'EtapeInscriptionPreteur',
                      CASE c.type
                      WHEN 1 THEN 'Physique'
                      WHEN 2 THEN 'Morale'
                      WHEN 3 THEN 'Physique'
                      ELSE 'Morale'
                      END as 'TypeContact',
                      CASE cs.status
                      WHEN 6 THEN 'oui'
                      ELSE 'non'
                      END as 'Valide',
                      (
                        SELECT cs.label FROM clients_status_history cshs1
                        INNER JOIN clients_status cs on cshs1.id_client_status =cs.id_client_status
                        WHERE cshs1.id_client=c.id_client
                        ORDER BY cshs1.added DESC LIMIT 1
                      ) AS 'StatusCompletude',
                      CASE c.added
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.added
                      END as 'DateInscription',
                      CASE c.updated
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.updated
                      END as 'DateDerniereMiseaJour',
                      CASE c.lastlogin
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.lastlogin
                      END as 'DateDernierLogin',
                      cs.id_client_status as 'StatutValidation',
                      status_inscription_preteur as 'StatusInscription',
                      count(
                          distinct(l.id_project)
                      ) as 'NbPretsValides',
                      REPLACE(ca.adresse1,',','') as 'Adresse1',
                      REPLACE(ca.adresse2,',','') as 'Adresse2',
                      REPLACE(ca.adresse3,',','') as 'Adresse3',
                      REPLACE(ca.cp,',','') as 'CP',
                      REPLACE(ca.ville,',','') as 'Ville',
                      acountry.fr as 'Pays',
                      SUM(l.amount)/100 as 'TotalPretEur',
                      CASE p.id_prospect WHEN NULL THEN '' ELSE CONCAT('P', p.id_prospect) END AS 'DeletingProspect',
                      '0012400000K0Bxw' as 'Sfcompte'
                    FROM
                      clients c
                      INNER JOIN wallet w on w.id_client = c.id_client
                      LEFT JOIN clients_adresses ca on c.id_client = ca.id_client
                      LEFT JOIN pays_v2 ccountry on c.id_pays_naissance = ccountry.id_pays
                      LEFT JOIN pays_v2 acountry on ca.id_pays = acountry.id_pays
                      LEFT JOIN nationalites_v2 nv2 on c.id_nationalite = nv2.id_nationalite
                      LEFT JOIN loans l on w.id = l.id_lender and l.status = 0
                      LEFT JOIN clients_status cs on c.status = cs.id_client_status
                      LEFT JOIN prospects p ON p.email = c.email
                    WHERE c.status = 1
                    GROUP BY
                      c.id_client";

        return $this->getEntityManager()->getConnection()->executeQuery($query);
    }
}
