<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\{
    AbstractQuery, EntityRepository, NonUniqueResultException, NoResultException
};
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\UnexpectedResultException;
use PDO;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AttachmentType, Clients, ClientsStatus, Companies, CompanyClient, Loans, OperationType, PaysV2, WalletType
};

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
     * @param string $email
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

        try {
            $result = $query->getSingleScalarResult();
        } catch (UnexpectedResultException $exception) {
            return false;
        }

        return $result > 0;
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
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClient = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'c.idClientStatusHistory = csh.id')
            ->where('csh.idStatus IN (:status)')
            ->andWhere('wt.label = :lender')
            ->setParameter('status', $status, Connection::PARAM_INT_ARRAY)
            ->setParameter('lender', WalletType::LENDER);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int $limit
     *
     * @return array
     * @throws DBALException
     */
    public function getLendersToMatchCity(int $limit): array
    {
        $query = '
            SELECT * FROM (
              SELECT c.id_client, ca.id_adresse, c.prenom, c.nom, ca.cp_fiscal AS zip, ca.ville_fiscal AS city, ca.cp, ca.ville, 0 AS is_company
              FROM clients_adresses ca
              INNER JOIN clients c ON ca.id_client = c.id_client
              INNER JOIN wallet w ON c.id_client = w.id_client
              INNER JOIN wallet_type wt ON w.id_type = wt.id
              INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
              WHERE csh.id_status IN (' . implode(',', ClientsStatus::GRANTED_LOGIN) . ')
                AND wt.label = "' . WalletType::LENDER . '"
                AND (ca.id_pays_fiscal = ' . PaysV2::COUNTRY_FRANCE . ' OR ca.id_pays_fiscal = 0)
                AND c.type IN (' . Clients::TYPE_PERSON . ', ' . Clients::TYPE_PERSON_FOREIGNER . ')
                AND (
                  NOT EXISTS (SELECT cp FROM villes v WHERE v.cp = ca.cp_fiscal)
                  OR (SELECT COUNT(*) FROM villes v WHERE v.cp = ca.cp_fiscal AND v.ville = ca.ville_fiscal) <> 1
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
              INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
              WHERE csh.id_status IN (' . implode(',', ClientsStatus::GRANTED_LOGIN) . ')
                AND wt.label = "' . WalletType::LENDER . '"
                AND (ca.id_pays_fiscal = ' . PaysV2::COUNTRY_FRANCE . ' OR ca.id_pays_fiscal = 0)
                AND (
                  NOT EXISTS (SELECT cp FROM villes v WHERE v.cp = co.zip)
                  OR (SELECT COUNT(*) FROM villes v WHERE v.cp = co.zip AND v.ville = co.city) <> 1
                ) 
              LIMIT :limit
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
    public function getLendersToMatchBirthCity(int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->select('c.idClient, c.prenom, c.nom, c.villeNaissance')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClient = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'c.idClientStatusHistory = csh.id')
            ->where('wt.label = :lender')
            ->andWhere('csh.idStatus IN (:statusOnline)')
            ->andWhere('c.idPaysNaissance = :France')
            ->andWhere('c.inseeBirth IS NULL')
            ->setParameter('lender', WalletType::LENDER)
            ->setParameter('statusOnline', ClientsStatus::GRANTED_LOGIN, Connection::PARAM_INT_ARRAY)
            ->setParameter('France', PaysV2::COUNTRY_FRANCE)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * If true only lenders activated at least once (active lenders)
     * If false all online lender (Community)
     *
     * @param bool $onlyActive
     *
     * @return int|null
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countLenders(bool $onlyActive = false): ?int
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->select('COUNT(DISTINCT(c.idClient))')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClient = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'c.idClientStatusHistory = csh.id')
            ->where('wt.label = :lender')
            ->andWhere('csh.idStatus IN (:statusOnline)')
            ->setParameter('lender', WalletType::LENDER)
            ->setParameter('statusOnline', ClientsStatus::GRANTED_LOGIN, Connection::PARAM_INT_ARRAY);

        if ($onlyActive) {
            $queryBuilder->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'valid', Join::WITH, 'valid.idClient = c.idClient AND valid.idStatus = ' . ClientsStatus::STATUS_VALIDATED);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int[] $clientType
     * @param bool  $onlyActive
     *
     * @return int|null
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countLendersByClientType(array $clientType, bool $onlyActive = false): ?int
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->select('COUNT(DISTINCT(c.idClient))')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClient = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'c.idClientStatusHistory = csh.id')
            ->where('wt.label = :lender')
            ->andWhere('csh.idStatus IN (:statusOnline)')
            ->andWhere('c.type IN (:types)')
            ->setParameter('lender', WalletType::LENDER)
            ->setParameter('statusOnline', ClientsStatus::GRANTED_LOGIN, Connection::PARAM_INT_ARRAY)
            ->setParameter('types', $clientType, Connection::PARAM_INT_ARRAY);

        if ($onlyActive) {
            $queryBuilder->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'valid', Join::WITH, 'valid.idClient = c.idClient AND valid.idStatus = ' . ClientsStatus::STATUS_VALIDATED);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Statement
     * @throws DBALException
     */
    public function getLendersSalesForce(): Statement
    {
        $query = "
            SELECT
              c.id_client AS 'IDClient',
              c.id_client AS 'IDPreteur',
              c.id_langue AS 'Langue',
              REPLACE(c.source, ',', '') AS 'Source1',
              REPLACE(c.source2, ',', '') AS 'Source2',
              REPLACE(c.source3, ',', '') AS 'Source3',
              REPLACE(c.civilite, ',', '') AS 'Civilite',
              REPLACE(c.nom, ',', '') AS 'Nom',
              REPLACE(c.nom_usage, ',', '') AS 'NomUsage',
              REPLACE(c.prenom, ',', '') AS 'Prenom',
              REPLACE(c.fonction, ',', '') AS 'Fonction',
              CASE c.naissance
                WHEN '0000-00-00' THEN '2001-01-01'
                ELSE
                  CASE SUBSTRING(c.naissance, 1, 1)
                    WHEN '0' THEN '2001-01-01'
                    ELSE c.naissance
                  END
              END AS 'Datenaissance',
              REPLACE(ville_naissance, ',', '') AS 'Villenaissance',
              ccountry.fr AS 'PaysNaissance',
              nv2.fr_f AS 'Nationalite',
              REPLACE(c.telephone, '\t', '') AS 'Telephone',
              REPLACE(c.mobile, ',', '') AS 'Mobile',
              REPLACE(c.email, ',', '') AS 'Email',
              c.etape_inscription_preteur AS 'EtapeInscriptionPreteur',
              CASE c.type
                WHEN 1 THEN 'Physique'
                WHEN 2 THEN 'Morale'
                WHEN 3 THEN 'Physique'
                ELSE 'Morale'
              END AS 'TypeContact',
              CASE csh.id_status
                WHEN " . ClientsStatus::STATUS_VALIDATED . " THEN 'oui'
                ELSE 'non'
              END AS 'Valide',
              cs.label AS 'StatusCompletude',
              CASE c.added
                WHEN '0000-00-00 00:00:00' THEN ''
                ELSE c.added
              END AS 'DateInscription',
              CASE c.updated
                WHEN '0000-00-00 00:00:00' THEN ''
                ELSE c.updated
              END AS 'DateDerniereMiseaJour',
              CASE c.lastlogin
                WHEN '0000-00-00 00:00:00' THEN ''
                ELSE c.lastlogin
              END AS 'DateDernierLogin',
              CASE csh.id_status
                WHEN " . ClientsStatus::STATUS_VALIDATED . " THEN 1
                ELSE 0
              END AS 'StatutValidation',
              status_inscription_preteur AS 'StatusInscription',
              COUNT(DISTINCT l.id_project) AS 'NbPretsValides',
              REPLACE(ca.adresse1, ',', '') AS 'Adresse1',
              REPLACE(ca.adresse2, ',', '') AS 'Adresse2',
              REPLACE(ca.adresse3, ',', '') AS 'Adresse3',
              REPLACE(ca.cp, ',', '') AS 'CP',
              REPLACE(ca.ville, ',', '') AS 'Ville',
              acountry.fr AS 'Pays',
              SUM(l.amount) / 100 AS 'TotalPretEur',
              CASE p.id_prospect 
                WHEN NULL THEN '' 
                ELSE CONCAT('P', p.id_prospect)
              END AS 'DeletingProspect',
              '0012400000K0Bxw' AS 'Sfcompte'
            FROM clients c
            INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
            INNER JOIN wallet w FORCE INDEX (idx_id_client) ON w.id_client = c.id_client
            INNER JOIN wallet_type wt ON w.id_type = wt.id
            LEFT JOIN clients_adresses ca ON c.id_client = ca.id_client
            LEFT JOIN pays_v2 ccountry ON c.id_pays_naissance = ccountry.id_pays
            LEFT JOIN pays_v2 acountry ON ca.id_pays = acountry.id_pays
            LEFT JOIN nationalites_v2 nv2 ON c.id_nationalite = nv2.id_nationalite
            LEFT JOIN loans l ON w.id = l.id_lender and l.status = " . Loans::STATUS_ACCEPTED . "
            LEFT JOIN clients_status cs ON csh.id_status = cs.id
            LEFT JOIN prospects p ON p.email = c.email
            WHERE csh.id_status IN (" . implode(',', ClientsStatus::GRANTED_LOGIN) . ")
              AND wt.label = '" . WalletType::LENDER . "' 
            GROUP BY c.id_client";

        return $this->getEntityManager()->getConnection()->executeQuery($query);
    }

    /**
     * @param array $status
     *
     * @return array
     */
    public function getClientsToValidate(array $status): array
    {
        $query = '
            SELECT
              c.*,
              cs.id AS clients_status,
              cs.label AS label_status,
              w.available_balance AS balance
            FROM clients c
              INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
              INNER JOIN clients_status cs ON csh.id_status = cs.id
              INNER JOIN wallet w ON c.id_client = w.id_client
              LEFT JOIN companies com ON c.id_client = com.id_client_owner
            WHERE csh.id_status IN (:clientsStatus)
            ORDER BY FIELD(csh.id_status, :clientsStatus), c.added DESC';

        $result = $this->getEntityManager()->getConnection()
            ->executeQuery($query, ['clientsStatus' => $status], ['clientsStatus' => Connection::PARAM_INT_ARRAY])
            ->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * @param array     $clientType
     * @param \DateTime $start
     * @param \DateTime $end
     * @param bool      $onlyActive
     *
     * @return int|null
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countLendersByClientTypeBetweenDates(array $clientType, \DateTime $start, \DateTime $end, bool $onlyActive = false): ?int
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->select('COUNT(DISTINCT(c.idClient))')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClient = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'c.idClientStatusHistory = csh.id')
            ->where('wt.label = :lender')
            ->andWhere('csh.idStatus IN (:statusOnline)')
            ->andWhere('c.type IN (:types)')
            ->andWhere('c.added BETWEEN :start AND :end')
            ->setParameter('lender', WalletType::LENDER)
            ->setParameter('statusOnline', ClientsStatus::GRANTED_LOGIN, Connection::PARAM_INT_ARRAY)
            ->setParameter('types', $clientType, Connection::PARAM_INT_ARRAY)
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

        if ($onlyActive) {
            $queryBuilder->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'valid', Join::WITH, 'valid.idClient = c.idClient AND valid.idStatus = ' . ClientsStatus::STATUS_VALIDATED);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * If true only lenders activated at least once (active lenders)
     * If false all online lender (Community)
     *
     * @param \DateTime $start
     * @param \DateTime $end
     * @param bool      $onlyActive
     *
     * @return int|null
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countLendersBetweenDates(\DateTime $start, \DateTime $end, bool $onlyActive = false): ?int
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->select('COUNT(DISTINCT(c.idClient))')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClient = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'c.idClientStatusHistory = csh.id')
            ->where('wt.label = :lender')
            ->andWhere('csh.idStatus IN (:statusOnline)')
            ->andWhere('c.added BETWEEN :start AND :end')
            ->setParameter('lender', WalletType::LENDER)
            ->setParameter('statusOnline', ClientsStatus::GRANTED_LOGIN, Connection::PARAM_INT_ARRAY)
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

        if ($onlyActive) {
            $queryBuilder->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'valid', Join::WITH, 'valid.idClient = c.idClient AND valid.idStatus = ' . ClientsStatus::STATUS_VALIDATED);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $year
     *
     * @return array
     */
    public function findValidatedClientsUntilYear($year)
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClient = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'c.idClientStatusHistory = csh.id')
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'valid', Join::WITH, 'c.idClient = valid.idClient')
            ->where('wt.label = :lender')
            ->andWhere('csh.idStatus IN (:onlineStatus)')
            ->andWhere('valid.idStatus = :validatedStatus')
            ->andWhere('valid.added <= :year')
            ->setParameter('lender', WalletType::LENDER)
            ->setParameter('onlineStatus', ClientsStatus::GRANTED_LOGIN, Connection::PARAM_INT_ARRAY)
            ->setParameter('validatedStatus', ClientsStatus::STATUS_VALIDATED)
            ->setParameter('year', $year . '-12-31 23:59:59');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return array
     * @throws DBALException
     */
    public function findAllClientsForLoiEckert(): array
    {
        $query = '
            SELECT
              c.*,
              csh.id_status,
              IF (
                o_provision.added IS NOT NULL, 
                IF (
                  o_withdraw.added IS NOT NULL,
                  IF (MAX(o_provision.added) > MAX(o_withdraw.added), MAX(o_provision.added), MAX(o_withdraw.added)),
                  MAX(o_provision.added)
                ), 
                MAX(o_withdraw.added)
              ) AS lastMovement,
              w.available_balance AS availableBalance,
              MIN(csh_valid.added) AS validationDate
            FROM clients c
            INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
            INNER JOIN wallet w ON c.id_client = w.id_client
            INNER JOIN wallet_type wt ON w.id_type = wt.id AND wt.label = "' . WalletType::LENDER . '"
            LEFT JOIN operation o_provision ON w.id = o_provision.id_wallet_creditor AND o_provision.id_type = (SELECT id FROM operation_type WHERE label = "'. OperationType::LENDER_PROVISION . '")
            LEFT JOIN operation o_withdraw ON w.id = o_withdraw.id_wallet_debtor AND o_withdraw.id_type = (SELECT id FROM operation_type WHERE label = "'. OperationType::LENDER_WITHDRAW . '")
            LEFT JOIN clients_status_history csh_valid ON c.id_client = csh_valid.id_client AND csh_valid.id_status = ' . ClientsStatus::STATUS_VALIDATED . '
            WHERE csh_valid.id IS NOT NULL OR available_balance > 0
            GROUP BY c.id_client
            ORDER BY c.lastlogin ASC';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string    $lastName
     * @param string    $firstName
     * @param \DateTime $birthday
     *
     * @return array
     * @throws DBALException
     */
    public function getDuplicatesByName(string $lastName, string $firstName, \DateTime $birthday): array
    {
        $replaceCharacters   = '';
        $charactersToReplace = [' ', '-', '_', '*', ',', '^', '`', ':', ';', ',', '.', '!', '&', '"', '\'', '<', '>', '(', ')', '@'];
        $firstName           = str_replace($charactersToReplace, '', htmlspecialchars_decode($firstName));
        $lastName            = str_replace($charactersToReplace, '', htmlspecialchars_decode($lastName));

        foreach ($charactersToReplace as $character) {
            $replaceCharacters .= ',\'' . addslashes($character) . '\', \'\')';
        }

        $query = '
            SELECT c.*
            FROM clients c
            INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
            WHERE ' . str_repeat('REPLACE(', count($charactersToReplace)) . 'c.nom' . $replaceCharacters . ' LIKE :lastName
              AND ' . str_repeat('REPLACE(', count($charactersToReplace)) . 'c.prenom' . $replaceCharacters . ' LIKE :firstName
              AND c.naissance = :birthday
              AND csh.id_status IN (' . implode(',', ClientsStatus::GRANTED_LOGIN) . ')';

        $result = $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, ['lastName' => '%' . $lastName . '%', 'firstName' => '%' . $firstName . '%', 'birthday' => $birthday->format('Y-m-d')])
            ->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * @param string   $search
     * @param int|null $limit
     *
     * @return array
     */
    public function findLendersByAutocomplete($search, $limit = null)
    {
        $search       = trim(filter_var($search, FILTER_SANITIZE_STRING));
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->select('c.idClient')
            ->addSelect('c.type')
            ->addSelect('IFNULL (CASE WHEN c.type IN (:companyType) THEN co.name ELSE CONCAT(c.nom, \', \', c.prenom) END, \' \') AS name')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClient = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType= wt.id AND wt.label = :lenderWalletType')
            ->leftJoin('UnilendCoreBusinessBundle:Companies', 'co', Join::WITH, 'c.idClient = co.idClientOwner AND c.type IN (:companyType)')
            ->setParameter('lenderWalletType', WalletType::LENDER)
            ->setParameter('companyType', [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER], Connection::PARAM_INT_ARRAY)
            ->orderBy('c.added', 'DESC');

        if (filter_var($search, FILTER_VALIDATE_INT)) {
            $queryBuilder
                ->where('c.idClient = :search')
                ->setParameter('search', $search . '%');
        } else {
            $queryBuilder
                ->where('c.nom LIKE :search')
                ->orWhere('co.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if (is_int($limit)) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int|null    $idClient
     * @param string|null $email
     * @param string|null $name
     * @param string|null $firstName
     * @param string|null $companyName
     * @param string|null $siren
     * @param bool|null   $online
     *
     * @return array
     * @throws DBALException
     */
    public function findLenders(?int $idClient = null, ?string $email = null, ?string $name = null, ?string $firstName = null, ?string $companyName = null, ?string $siren = null, ?bool $online = null): array
    {
        $query = '
            SELECT
              c.id_client AS id_client,
              cs.label AS status,
              c.email AS email,
              c.telephone AS telephone,
              c.status_inscription_preteur AS status_inscription_preteur,
              CASE c.type
                WHEN ' . Clients::TYPE_PERSON . ' THEN c.prenom
                WHEN ' . Clients::TYPE_PERSON_FOREIGNER . ' THEN c.prenom
                ELSE
                (SELECT
                   CASE co.status_client
                   WHEN ' . Companies::CLIENT_STATUS_MANAGER . ' THEN CONCAT(c.prenom, " ", c.nom)
                   ELSE CONCAT(co.prenom_dirigeant, " ", co.nom_dirigeant)
                   END AS dirigeant
                 FROM companies co WHERE co.id_client_owner = c.id_client)
              END AS prenom_ou_dirigeant,
              CASE c.type
                WHEN ' . Clients::TYPE_PERSON . ' THEN c.nom
                WHEN ' . Clients::TYPE_PERSON_FOREIGNER . ' THEN c.nom
                ELSE (SELECT co.name FROM companies co WHERE co.id_client_owner = c.id_client)
              END AS nom_ou_societe,
              CASE c.type
                WHEN ' . Clients::TYPE_PERSON . ' THEN REPLACE(c.nom_usage, "Nom D\'usage" ,"")
                WHEN ' . Clients::TYPE_PERSON_FOREIGNER . ' THEN REPLACE(c.nom_usage, "Nom D\'usage" ,"")
                ELSE ""
              END AS nom_usage
            FROM clients c
            INNER JOIN wallet w FORCE INDEX (idx_id_client) ON w.id_client = c.id_client
            INNER JOIN wallet_type wt ON w.id_type = wt.id AND wt.label = "' . WalletType::LENDER . '"
            INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
            INNER JOIN clients_status cs ON csh.id_status = cs.id
            LEFT JOIN companies co ON co.id_client_owner = c.id_client
            WHERE 1' ;

        $parameters = [];

        if (null !== $idClient) {
            $query                  .= ' AND c.id_client IN (:idClient)';
            $parameters['idClient'] = $idClient;
        }

        if (null !== $email) {
            $query               .= ' AND c.email LIKE :email';
            $parameters['email'] = $email . '%';
        }

        if (null !== $name) {
            $query              .= ' AND (c.nom LIKE :name OR c.nom_usage LIKE :name)';
            $parameters['name'] = $name . '%';
        }

        if (null !== $firstName) {
            $query                   .= ' AND c.prenom LIKE :firstName';
            $parameters['firstName'] = $firstName . '%';
        }

        if (null !== $companyName) {
            $query                     .= ' AND co.name LIKE :companyName';
            $parameters['companyName'] = $companyName . '%';
        }

        if (null !== $siren) {
            $query               .= ' AND co.siren = :siren';
            $parameters['siren'] = $siren;
        }

        if (null !== $online) {
            $query .= $online ? ' AND csh.id_status IN (' . implode(',', ClientsStatus::GRANTED_LOGIN) . ')' : ' AND csh.id_status NOT IN (' . implode(',', ClientsStatus::GRANTED_LOGIN) . ')';
        }

        $query .= '
            GROUP BY c.id_client
            ORDER BY c.id_client DESC';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, $parameters)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string $hashSegment
     *
     * @return null|Clients
     */
    public function findClientByOldSponsorCode($hashSegment)
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder->where('c.hash LIKE :hashSegment')
            ->setParameter('hashSegment', $hashSegment . '%');

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Method to be deleted once the query does not return any results
     *
     * @param int $limit
     *
     * @return array
     */
    public function findClientsWithSponsorCodeToRepair($limit)
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addEntityResult('UnilendCoreBusinessBundle:Clients', 'c')
            ->addFieldResult('c', 'id_client', 'idClient')
            ->addFieldResult('c', 'sponsor_code', 'sponsorCode')
            ->addFieldResult('c', 'nom', 'nom');

        $query = $this->_em->createNativeQuery('SELECT id_client, sponsor_code, nom FROM clients WHERE sponsor_code REGEXP "[^a-zA-Z0-9]" LIMIT :limit ', $resultSetMapping);
        $query->setParameter('limit', $limit);

        return $query->getResult();
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function findBeneficialOwnerByName($name)
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder->select('
                c.idClient,
                c.nom,
                c.prenom,
                c.naissance,
                c.villeNaissance,
                c.idPaysNaissance,
                ca.idPaysFiscal,
                a.id AS attachmentId,
                a.originalName AS attachmentOriginalName,
                a.path AS attachmentPath'
            )
            ->leftJoin('UnilendCoreBusinessBundle:Attachment', 'a', Join::WITH, 'a.idClient = c.idClient AND a.idType = ' . AttachmentType::CNI_PASSPORTE)
            ->leftJoin('UnilendCoreBusinessBundle:ClientsAdresses', 'ca', Join::WITH, 'c.idClient = ca.idClient')
            ->where('c.nom LIKE :name')
            ->andWhere('c.type NOT IN (:lenderTypes)')
            ->setParameter('name', '%' . $name . '%')
            ->setParameter('lenderTypes', [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER, Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER]);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param CompanyClient $companyClient
     *
     * @return int
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countDuplicatesByFullName(CompanyClient $companyClient) : int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.idClient)')
            ->innerJoin('UnilendCoreBusinessBundle:CompanyClient', 'cc', Join::WITH, 'c.idClient = cc.idClient')
            ->where('LOWER(c.nom) LIKE LOWER(:lastname)')
            ->andWhere('LOWER(c.prenom) LIKE LOWER(:firstname)')
            ->andWhere('cc.idCompany = :company')
            ->setParameter('lastname', $companyClient->getIdClient()->getNom())
            ->setParameter('firstname', $companyClient->getIdClient()->getPrenom())
            ->setParameter('company', $companyClient->getIdCompany()->getIdCompany())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param string $email
     *
     * @return array
     */
    public function findDuplicatesByEmail(string $email) : array
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder->select('
                c.idClient,
                c.email,
                c.nom,
                c.prenom,
                c.type AS clientType,
                IDENTITY(csh.idStatus) AS status,
                cs.label AS statusLabel,
                c.added AS creationDate,
                wt.label AS walletType,
                co.idCompany,
                co.name AS companyName,
                COUNT(wbh.id) AS operations,
                pa.id AS idPartner,
                cp.name AS partnerName,
                COUNT(DISTINCT bo.id) AS beneficialOwner,
                pr.idPrescripteur'
            )
            ->leftJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClient = w.idClient')
            ->leftJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->leftJoin('UnilendCoreBusinessBundle:WalletBalanceHistory', 'wbh', Join::WITH, 'w.id = wbh.idWallet')
            ->leftJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'c.idClientStatusHistory = csh.id')
            ->leftJoin('UnilendCoreBusinessBundle:ClientsStatus', 'cs', Join::WITH, 'csh.idStatus = cs.id')
            ->leftJoin('UnilendCoreBusinessBundle:Companies', 'co', Join::WITH, 'c.idClient = co.idClientOwner')
            ->leftJoin('UnilendCoreBusinessBundle:BeneficialOwner', 'bo', Join::WITH, 'c.idClient = bo.idClient')
            ->leftJoin('UnilendCoreBusinessBundle:CompanyClient', 'cc', Join::WITH, 'c.idClient = cc.idClient')
            ->leftJoin('UnilendCoreBusinessBundle:Companies', 'coc', Join::WITH, 'cc.idCompany = coc.idCompany')
            ->leftJoin('UnilendCoreBusinessBundle:Companies', 'cp', Join::WITH, 'coc.idParentCompany = cp.idCompany')
            ->leftJoin('UnilendCoreBusinessBundle:Partner', 'pa', Join::WITH, 'cp.idCompany = pa.idCompany')
            ->leftJoin('UnilendCoreBusinessBundle:Prescripteurs', 'pr', Join::WITH, 'c.idClient = pr.idClient')
            ->where('c.email LIKE :email')
            ->setParameter('email', $email . '%', \PDO::PARAM_STR)
            ->groupBy('c.idClient');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string $email
     * @param int[]  $status
     *
     * @return array
     */
    public function findByEmailAndStatus(string $email, array $status): array
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'c.idClientStatusHistory = csh.id')
            ->where('c.email = :email')
            ->andWhere('csh.idStatus IN (:status)')
            ->setParameter('email', $email, \PDO::PARAM_STR)
            ->setParameter('status', $status);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string $hash
     * @param int[]  $status
     *
     * @return Clients|null
     */
    public function findOneByHashAndStatus(string $hash, array $status): ?Clients
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'c.idClientStatusHistory = csh.id')
            ->where('c.hash = :hash')
            ->andWhere('csh.idStatus IN (:status)')
            ->setParameter('hash', $hash, \PDO::PARAM_STR)
            ->setParameter('status', $status);

        try {
            return $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $exception) {
            return null;
        }
    }
}
