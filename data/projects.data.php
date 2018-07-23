<?php

use Doctrine\DBAL\Statement;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;

class projects extends projects_crud
{
    const MINIMUM_REVENUE = 100000;

    const DISPLAY_PROJECT_ON  = 0;
    const DISPLAY_PROJECT_OFF = 1;

    const SORT_FIELD_SECTOR = 'sector';
    const SORT_FIELD_AMOUNT = 'amount';
    const SORT_FIELD_RATE   = 'rate';
    const SORT_FIELD_RISK   = 'risk';
    const SORT_FIELD_END    = 'end';

    const SORT_DIRECTION_ASC  = 'ASC';
    const SORT_DIRECTION_DESC = 'DESC';

    public function __construct($bdd, $params = '')
    {
        parent::__construct($bdd, $params);
    }

    public function create($cs = '')
    {
        parent::create($cs);

        $this->hash = md5($this->id_project . $this->added);
        $this->update();

        return $this->id_project;
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `projects`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $result = $this->bdd->query('SELECT COUNT(*) FROM projects ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_project')
    {
        $result = $this->bdd->query('SELECT * FROM projects WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_assoc($result) > 0);
    }

    public function searchDossiers($date1 = '', $date2 = '', $need = '', $duree = '', $status = '', $analyste = '', $siren = '', $id = '', $raison_sociale = '', $iAdvisorId = '', $iSalesPersonId = '', $start = '', $nb = '')
    {
        $where = [];

        if (false === empty($date1)) {
            $where[] = 'p.added >= "' . $date1 . ' 00:00:00"';
        }
        if (false === empty($date2)) {
            $where[] = 'p.added <= "' . $date2 . ' 23:59:59"';
        }
        if (false === empty($need)) {
            $where[] = 'p.id_project_need = "' . $need . '"';
        }
        if (false === empty($duree)) {
            $where[] = 'p.period = "' . $duree . '"';
        }
        if (false === empty($status)) {
            $where[] = 'p.status IN (' . $status . ')';
        }
        if (false === empty($analyste)) {
            $where[] = 'p.id_analyste = "' . $analyste . '"';
        }
        if (false === empty($siren)) {
            $where[] = 'co.siren LIKE "%' . $siren . '%"';
        }
        if (false === empty($id)) {
            $where[] = 'p.id_project = "' . $id . '"';
        }
        if (false === empty($raison_sociale)) {
            $where[] = 'co.name LIKE "%' . $raison_sociale . '%"';
        }
        if (false === empty($iAdvisorId)) {
            $where[] = 'p.id_prescripteur = ' . $iAdvisorId;
        }
        if (false === empty($iSalesPersonId)) {
            $where[] = 'p.id_commercial = ' . $iSalesPersonId;
        }

        $sSqlCount = '
            SELECT COUNT(*)
            FROM projects p
            LEFT JOIN companies co ON (p.id_company = co.id_company)';
        $sSqlCount .= 0 < count($where) ? ' WHERE ' . implode(' AND ', $where) : '';

        $rResult        = $this->bdd->query($sSqlCount);
        $iCountProjects = (int) $this->bdd->result($rResult, 0, 0);

        $sql = '
            SELECT
                p.*,
                co.id_client_owner,
                co.siren,
                co.name,
                ps.label,
                IFNULL(pn.pre_scoring, -1) AS pre_scoring
            FROM projects p
            LEFT JOIN companies co ON p.id_company = co.id_company
            LEFT JOIN projects_status ps on ps.status = p.status
            LEFT JOIN projects_notes pn ON p.id_project = pn.id_project';
        $sql .= 0 < count($where) ? ' WHERE ' . implode(' AND ', $where) : '';
        $sql .= ' ORDER BY p.added DESC
            ' . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);

        $result    = array();
        $result[0] = $iCountProjects;

        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }

        return $result;
    }

    /**
     * @param array  $status
     * @param string $where
     * @param array  $sort
     * @param string $start
     * @param string $nb
     * @param bool   $useCache
     * @param int[]  $products
     *
     * @return array
     */
    public function selectProjectsByStatus(array $status, $where = '', array $sort = [], $start = '', $nb = '', $useCache = true, array $products = [])
    {
        $binds = ['fundingStatus' => ProjectsStatus::EN_FUNDING, 'status' => $status];

        if ($useCache) {
            $QCProfile = new \Doctrine\DBAL\Cache\QueryCacheProfile(60, md5(__METHOD__));
        } else {
            $QCProfile = null;
        }

        $select = '
            SELECT p.*,
                DATEDIFF(p.date_retrait, NOW()) AS daysLeft,
                CASE WHEN status = :fundingStatus
                    THEN "1"
                    ELSE "2"
                END AS lestatut';

        $tables = '
            FROM projects p FORCE INDEX (status)';

        $sortField     = self::SORT_FIELD_END;
        $sortDirection = self::SORT_DIRECTION_DESC;

        if (false === empty($sort)) {
            $field     = key($sort);
            $direction = current($sort);

            if (
                in_array($field, [\projects::SORT_FIELD_SECTOR, \projects::SORT_FIELD_AMOUNT, \projects::SORT_FIELD_RATE, \projects::SORT_FIELD_RISK, \projects::SORT_FIELD_END])
                && in_array($direction, [self::SORT_DIRECTION_ASC, self::SORT_DIRECTION_DESC])
            ) {
                $sortField     = $field;
                $sortDirection = $direction;
            }
        }

        switch ($sortField) {
            case self::SORT_FIELD_SECTOR:
                $order  = 'c.sector ' . $sortDirection . ', p.date_retrait DESC, p.status ASC';
                $tables .= '
                    INNER JOIN companies c ON p.id_company = c.id_company';
                break;
            case self::SORT_FIELD_AMOUNT:
                $order = 'p.amount ' . $sortDirection . ', p.date_retrait DESC, p.status ASC';
                break;
            case self::SORT_FIELD_RATE:
                $select .= ',
                    CASE
                        WHEN p.status IN (' . implode(', ', [ProjectsStatus::FUNDE, ProjectsStatus::REMBOURSEMENT, ProjectsStatus::REMBOURSE, ProjectsStatus::REMBOURSEMENT_ANTICIPE, ProjectsStatus::PROBLEME, ProjectsStatus::LOSS]) . ') THEN (SELECT SUM(amount * rate) / SUM(amount) AS avg_rate FROM loans WHERE id_project = p.id_project)
                        WHEN p.status IN (' . implode(', ', [ProjectsStatus::PRET_REFUSE, ProjectsStatus::EN_FUNDING, ProjectsStatus::AUTO_BID_PLACED, ProjectsStatus::A_FUNDER]) . ') THEN (SELECT SUM(amount * rate) / SUM(amount) AS avg_rate FROM bids WHERE id_project = p.id_project AND status IN (0, 1))
                        WHEN p.status IN (' . implode(', ', [ProjectsStatus::FUNDING_KO]) . ') THEN (SELECT SUM(amount * rate) / SUM(amount) AS avg_rate FROM bids WHERE id_project = p.id_project)
                    END AS avg_rate';
                $order  = 'avg_rate ' . $sortDirection . ', p.date_retrait DESC, p.status ASC';
                break;
            case self::SORT_FIELD_RISK:
                $sortDirection = $sortDirection === self::SORT_DIRECTION_DESC ? self::SORT_DIRECTION_ASC : self::SORT_DIRECTION_DESC;
                $order         = 'p.risk ' . $sortDirection . ', p.date_retrait DESC, p.status ASC';
                break;
            case self::SORT_FIELD_END:
            default:
                if ($sortDirection === self::SORT_DIRECTION_ASC) {
                    $order = 'lestatut DESC, IF(lestatut = 2, p.date_fin, "") ASC, IF(lestatut = 1, p.date_retrait, "") DESC, p.status ASC';
                } else {
                    $order = 'lestatut ASC, IF(lestatut = 2, p.date_fin, "") DESC, IF(lestatut = 1, p.date_retrait, "") ASC, p.status DESC';
                }
                break;
        }

        if (false === empty($products)) {
            $binds['products'] = $products;
            $where             .= ' AND p.id_product IN (:products)';
        }

        $sql = $select . $tables . '
            WHERE p.status IN (:status) ' . $where . '
            ORDER BY ' . $order;

        if (is_numeric($nb)) {
            $binds['number'] = $nb;
            $sql             .= ' LIMIT :number ';

            if (is_numeric($start)) {
                $binds['start'] = $start;
                $sql            .= ' OFFSET :start';
            }
        }

        try {
            $aTypes    = [
                'status'        => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                'products'      => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                'fundingStatus' => \PDO::PARAM_INT,
                'minRateRange'  => \PDO::PARAM_INT,
                'maxRateRange'  => \PDO::PARAM_INT,
                'number'        => \PDO::PARAM_INT,
                'start'         => \PDO::PARAM_INT
            ];
            $statement = $this->bdd->executeQuery($sql, $binds, $aTypes, $QCProfile);
            $result    = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $statement->closeCursor();
        } catch (\Doctrine\DBAL\DBALException $ex) {
            $result = [];
        }
        return $result;
    }

    /**
     * @param array  $status
     * @param string $where
     * @param int[]  $products
     *
     * @return int
     */
    public function countSelectProjectsByStatus(array $status, $where = '', array $products = [])
    {
        $bind  = ['status' => $status];
        $type  = ['status' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY];
        $query = '
            SELECT COUNT(*)
            FROM projects
            WHERE status IN (:status)' . $where;

        if (false === empty($products)) {
            $query            .= ' AND id_product IN (:products)';
            $bind['products'] = $products;
            $type['products'] = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
        }

        try {
            /** @var \Doctrine\DBAL\Driver\Statement $statement */
            $statement = $this->bdd->executeQuery($query, $bind, $type);
            $result    = $statement->fetchColumn();
            $statement->closeCursor();

            if (empty($result)) {
                return 0;
            }

            return (int) $result;
        } catch (\Doctrine\DBAL\DBALException $exception) {
            return 0;
        }
    }

    public function searchDossiersByStatus(array $aStatus, $siren = null, $societe = null, $nom = null, $prenom = null, $projet = null, $email = null, $start = null, $nb = null)
    {
        $where = '';
        if (false === empty($siren)) {
            $where .= ' AND co.siren = "' . $siren . '"';
        }
        if (false === empty($societe)) {
            $where .= ' AND co.name = "' . $societe . '"';
        }
        if (false === empty($nom)) {
            $where .= ' AND c.nom = "' . $nom . '"';
        }
        if (false === empty($prenom)) {
            $where .= ' AND c.prenom = "' . $prenom . '"';
        }
        if (false === empty($projet)) {
            $where .= ' AND p.title LIKE "%' . $projet . '%"';
        }
        if (false === empty($email)) {
            $where .= ' AND c.email = "' . $email . '"';
        }

        $result   = array();
        $resultat = $this->bdd->query('
            SELECT p.id_project,
                p.title,
                p.remb_auto,
                c.nom,
                c.prenom,
                c.email,
                co.name AS company,
                ps.label AS status_label
            FROM projects p
            INNER JOIN projects_status ps ON (p.status = ps.status)
            LEFT JOIN companies co ON (p.id_company = co.id_company)
            LEFT JOIN clients c ON (co.id_client_owner = c.id_client)
            WHERE p.status IN (' . implode(', ', $aStatus) . ')
            ' . $where . '
            ORDER BY p.added DESC
            ' . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''))
        );

        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function positionProject($projectId, array $status, $order, array $products = [])
    {
        $aProjects = $this->selectProjectsByStatus($status, ' AND p.display = ' . self::DISPLAY_PROJECT_ON, $order, '', '', true, $products);
        $previous  = '';
        $next      = '';

        foreach ($aProjects as $k => $p) {
            if ($p['id_project'] == $projectId) {
                $previous = isset($aProjects[$k - 1]) ? $aProjects[$k - 1] : null;
                $next     = isset($aProjects[$k + 1]) ? $aProjects[$k + 1] : null;
                break;
            }
        }
        return ['previousProject' => $previous, 'nextProject' => $next];
    }

    public function countProjectsByStatusAndLender($lender, array $status)
    {
        $sql = '
            SELECT COUNT(DISTINCT l.id_project)
            FROM loans l
            INNER JOIN projects p ON l.id_project = p.id_project
            WHERE id_lender = ' . $lender . '
            AND l.status = ' . Loans::STATUS_ACCEPTED . '
            AND p.status IN (' . implode(',', $status) . ')';

        $result = $this->bdd->query($sql);
        $record = $this->bdd->result($result);

        return $record;
    }

    public function countProjectsSinceLendersubscription($client, $status)
    {
        if (is_array($status)) {
            $statusString = implode(',', $status);
        }

        $sql    = '
            SELECT COUNT(*)
            FROM projects
            WHERE date_publication >= (SELECT added FROM clients WHERE id_client = ' . $client . ')
                AND status IN (' . $statusString . ')';
        $result = $this->bdd->query($sql);
        $record = $this->bdd->result($result);

        return $record;
    }

    /**
     * Retrieve the list of project IDs that needs email reminder
     *
     * @param int $status Project status
     * @param int $daysInterval Interval in days since previous reminder
     * @param int $previousReminderIndex Previous reminder for counting days interval
     *
     * @return array
     */
    public function getReminders($status, $daysInterval, $previousReminderIndex)
    {
        $projects = [];
        $query    = '
            SELECT p.id_project
            FROM projects p
            INNER JOIN (SELECT id_project, MAX(id_project_status_history) AS id_project_status_history FROM projects_status_history GROUP BY id_project) plsh ON plsh.id_project = p.id_project
            INNER JOIN projects_status_history psh ON psh.id_project_status_history = plsh.id_project_status_history
            LEFT JOIN project_attachment pa ON pa.id_project = p.id_project
            LEFT JOIN attachment a ON a.id = pa.id_attachment AND a.archived IS NULL AND a.id_type = ' . \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType::DERNIERE_LIASSE_FISCAL . '
            WHERE p.status = ' . $status . '
                AND DATE_SUB(CURDATE(), INTERVAL ' . $daysInterval . ' DAY) = DATE(psh.added)
                AND psh.numero_relance = ' . $previousReminderIndex . '
                AND a.id IS NULL
            GROUP BY p.id_project';

        $statement = $this->bdd->query($query);

        if ($this->bdd->num_rows($statement) > 0) {
            while ($record = $this->bdd->fetch_assoc($statement)) {
                $projects[] = (int) $record['id_project'];
            }
        }

        return $projects;
    }

    /**
     * @deprecated Use ProjectsRepository::getAverageInterestRate instead
     *
     * @param bool $cache
     *
     * @return float
     */
    public function getAverageInterestRate($cache = true)
    {
        if (null !== $this->interest_rate && false === empty((float) $this->interest_rate)) {
            return $this->interest_rate;
        }

        $queryCacheProfile = null;
        $queryBuilder      = $this->bdd->createQueryBuilder();
        $queryBuilder
            ->select('SUM(amount * rate) / SUM(amount)')
            ->where('id_project = :projectId')
            ->setParameter('projectId', $this->id_project);

        switch ($this->status) {
            case ProjectsStatus::FUNDE:
            case ProjectsStatus::REMBOURSEMENT:
            case ProjectsStatus::REMBOURSE:
            case ProjectsStatus::PROBLEME:
            case ProjectsStatus::REMBOURSEMENT_ANTICIPE:
            case ProjectsStatus::LOSS:
                $queryBuilder
                    ->from('loans');
                break;
            case ProjectsStatus::PRET_REFUSE:
            case ProjectsStatus::EN_FUNDING:
            case ProjectsStatus::AUTO_BID_PLACED:
            case ProjectsStatus::BID_TERMINATED:
            case ProjectsStatus::A_FUNDER:
                $queryBuilder
                    ->from('bids')
                    ->andWhere('status IN (:status)')
                    ->setParameter('status', [Bids::STATUS_PENDING, Bids::STATUS_ACCEPTED], \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
                break;
            case ProjectsStatus::FUNDING_KO:
                $queryBuilder
                    ->from('bids');
                break;
            default:
                trigger_error('Unknown project status: ' . $this->status . ' Could not calculate amounts', E_USER_WARNING);
                return 0.0;
        }

        if ($this->status >= ProjectsStatus::PRET_REFUSE) {
            trigger_error('Interest rate should be saved in DB for project ' . $this->id_project, E_USER_WARNING);
        }

        if ($cache && $this->status != ProjectsStatus::A_FUNDER) {
            $cacheTime         = \Unilend\librairies\CacheKeys::VERY_SHORT_TIME;
            $cacheKey          = md5(__METHOD__);
            $queryCacheProfile = new \Doctrine\DBAL\Cache\QueryCacheProfile($cacheTime, $cacheKey);
        }

        try {
            $statement = $this->bdd->executeQuery(
                $queryBuilder->getSQL(),
                $queryBuilder->getParameters(),
                $queryBuilder->getParameterTypes(),
                $queryCacheProfile
            );

            if ($statement instanceof \Doctrine\DBAL\Driver\ResultStatement) {
                $result = $statement->fetchAll(PDO::FETCH_COLUMN);
                $statement->closeCursor();

                if (is_array($result) && false == empty($result)) {
                    return (float) $result[0];
                }
            }
        } catch (\Exception $exception) {
        }

        return 0.0;
    }

    public function getLoansAndLendersForProject($iProjectId = null)
    {
        if ($iProjectId === null) {
            $iProjectId = $this->id_project;
        }

        $sql = '
            SELECT
                l.id_lender,
                c.nom,
                c.prenom,
                com.name,
                l.amount,
                l.added as date
            FROM loans l
            LEFT JOIN wallet w ON l.id_lender = w.id
            LEFT JOIN clients c ON w.id_client = c.id_client
            LEFT JOIN companies com ON com.id_client_owner = c.id_client
            WHERE id_project = ' . $iProjectId;

        $result           = $this->bdd->query($sql);
        $aLoansAndLenders = array();

        while ($record = $this->bdd->fetch_assoc($result)) {
            $aLoansAndLenders[] = $record;
        }

        return $aLoansAndLenders;
    }

    /**
     * @param null|int $projectId
     * @param null|int $order
     *
     * @return array
     */
    public function getDuePaymentsAndLenders($projectId = null, $order = null)
    {
        if ($projectId === null) {
            $projectId = $this->id_project;
        }

        $orderQuery = (isset($order)) ? ' AND ordre = ' . $order : null;

        $sql = '
            SELECT
                e.id_lender,
                c.nom,
                c.prenom,
                com.name,
                e.montant,
                e.capital,
                e.interets,
                e.date_echeance_reel as date
            FROM echeanciers e
                LEFT JOIN wallet w ON w.id = e.id_lender
                LEFT JOIN clients c ON w.id_client = c.id_client
                LEFT JOIN companies com ON com.id_client_owner = c.id_client
            WHERE id_project = ' . $projectId . $orderQuery;

        $result                 = $this->bdd->query($sql);
        $duePaymentsAndLenders = [];

        while ($record = $this->bdd->fetch_assoc($result)) {
            $duePaymentsAndLenders[] = $record;
        }

        return $duePaymentsAndLenders;
    }

    public function getProblematicProjectsWithUpcomingRepayment()
    {
        $aProjects = array();
        $rResult   = $this->bdd->query('
            SELECT p.*
            FROM projects p
              INNER JOIN (SELECT id_project, MIN(date_echeance_emprunteur) AS date_echeance_emprunteur FROM echeanciers_emprunteur WHERE (capital + interets + commission + tva - paid_capital - paid_interest - paid_commission_vat_incl) > 0 GROUP BY id_project) min_unpaid ON min_unpaid.id_project = p.id_project
              INNER JOIN echeanciers_emprunteur prev ON prev.id_project = p.id_project AND prev.date_echeance_emprunteur = min_unpaid.date_echeance_emprunteur
              INNER JOIN echeanciers_emprunteur next ON next.id_project = p.id_project AND next.ordre = prev.ordre + 1 AND next.status_emprunteur = ' . EcheanciersEmprunteur::STATUS_PENDING . '
            WHERE p.status = ' . ProjectsStatus::PROBLEME . '
                  AND DATE(next.date_echeance_emprunteur) = DATE(ADDDATE(NOW(), INTERVAL 7 DAY))'
        );
        while ($aRecord = $this->bdd->fetch_assoc($rResult)) {
            $aProjects[] = $aRecord;
        }
        return $aProjects;
    }

    public function getAvgRate($risk = null, $durationMin = null, $durationMax = null, $startingDate = null)
    {
        $whereRisk        = '';
        $whereDurationMin = '';
        $whereDurationMax = '';
        $wherePublished   = '';
        $bind             = [];
        $type             = [];

        if (null !== $risk) {
            $whereRisk    = ' AND p.risk = :risk ';
            $bind['risk'] = $risk;
            $type['risk'] = \PDO::PARAM_STR;
        }

        if (null !== $durationMin) {
            $bind['p_min']    = $durationMin;
            $type['p_min']    = \PDO::PARAM_INT;
            $whereDurationMin = ' AND p.period >= :p_min';
        }

        if (null !== $durationMax) {
            $bind['p_max']    = $durationMax;
            $type['p_max']    = \PDO::PARAM_INT;
            $whereDurationMax = ' AND p.period <= :p_max';
        }

        if (null !== $startingDate) {
            $bind['starting_date'] = $startingDate;
            $type['starting_date'] = \PDO::PARAM_STR;
            $wherePublished        = ' AND DATE(p.date_publication) >=  :starting_date';
        }

        $sQuery = '
            SELECT AVG(t1.weighted_rate_by_project)
            FROM (
                SELECT SUM(t.amount * t.rate) / SUM(t.amount) as weighted_rate_by_project
                FROM (
                    SELECT l.id_loan, l.amount, l.rate, l.added, p.id_project, p.period
                    FROM loans l
                    INNER JOIN projects p ON p.id_project = l.id_project
                    WHERE p.status >= ' . ProjectsStatus::FUNDE . '
                        AND p.status != ' . ProjectsStatus::FUNDING_KO . $whereRisk . $whereDurationMin . $whereDurationMax . $wherePublished . '
                ) t
                GROUP BY t.id_project
            ) t1';

        try {
            $statement = $this->bdd->executeQuery($sQuery, $bind, $type, new \Doctrine\DBAL\Cache\QueryCacheProfile(1800, md5(__METHOD__)));
            $result    = $statement->fetchAll(PDO::FETCH_COLUMN);
            $statement->closeCursor();
            if (empty($result)) {
                return false;
            }
            return array_shift($result);
        } catch (\Doctrine\DBAL\DBALException $ex) {
            return false;
        }
    }

    public function getAutoBidProjectStatistic(\DateTime $oDateFrom, \DateTime $oDateTo)
    {
        $sQuery = 'SELECT pg.id_project, pg.period, pg.risk, pg.date_fin, pg.status_label, pg.amount_total, pg.weighted_avg_rate, pg.avg_amount,
                      pb.bids_nb, pa.amount_total_autobid, pa.avg_amount_autobid, pa.weighted_avg_rate_autobid
                    FROM (
                       SELECT t.id_project, t.period, t.risk, t.date_fin, t.status_label, ROUND(SUM(t.amount) / 100, 2) AS amount_total, SUM(t.amount * t.rate) / SUM(t.amount) as weighted_avg_rate, ROUND(AVG(t.amount) / 100, 2) as avg_amount
                       FROM (
                          SELECT l.id_loan, l.amount, l.rate, p.date_fin, p.id_project, p.period, p.risk, ps.label as status_label
                          FROM loans l
                          INNER JOIN projects p ON p.id_project = l.id_project
                          INNER JOIN projects_status ps ON ps.status = p.status
                          WHERE l.status = ' . Loans::STATUS_ACCEPTED . '
                            AND p.status > ' . ProjectsStatus::EN_FUNDING . '
                            AND p.date_fin BETWEEN "' . $oDateFrom->format('Y-m-d H:i:s') . '" AND "' . $oDateTo->format('Y-m-d H:i:s') . '"
                          GROUP BY l.id_loan
                       ) t
                       GROUP BY t.id_project
                    ) pg
                    INNER JOIN (
                      SELECT count(b.id_bid) as bids_nb, b.id_project
                      FROM bids b
                      WHERE b.status = ' . Bids::STATUS_ACCEPTED . '
                      GROUP BY b.id_project
                    ) pb ON pb.id_project = pg.id_project
                    LEFT JOIN (
                      SELECT t1.id_project, ROUND(SUM(t1.amount) / 100, 2) as amount_total_autobid, SUM(t1.amount * t1.rate) / SUM(t1.amount) as weighted_avg_rate_autobid, ROUND(AVG(t1.amount) / 100, 2) avg_amount_autobid
                      FROM (
                        SELECT id_project, amount, rate
                        FROM bids
                        WHERE status = ' . Bids::STATUS_ACCEPTED . '
                        AND id_autobid != ""
                      ) t1
                      GROUP BY t1.id_project
                    ) pa ON pa.id_project = pg.id_project';

        $aProjects = array();
        $rResult   = $this->bdd->query($sQuery);
        while ($aRecord = $this->bdd->fetch_assoc($rResult)) {
            $aProjects[] = $aRecord;
        }
        return $aProjects;
    }

    public function getAvailableRisks()
    {
        //F, G, H are not used today.
        return array('A', 'B', 'C', 'D', 'E');
    }

    public function getProjectsSalesForce()
    {
        $sQuery = "
            SELECT
                p.id_project AS 'IDProjet',
                REPLACE(cl.source,',','') AS 'Source1',
                REPLACE(cl.source2,',','') AS 'Source2',
                p.id_company AS 'IDCompany',
                p.amount AS 'Amount',
                p.period AS 'NbMois',
                CASE p.date_publication
                  WHEN '0000-00-00 00:00:00' THEN ''
                  ELSE DATE(p.date_publication)
                END AS 'Date_Publication',
                CASE p.date_retrait
                  WHEN '0000-00-00 00:00:00' THEN ''
                  ELSE DATE(p.date_retrait)
                END AS 'Date_Retrait',
                CASE p.added
                  WHEN '0000-00-00 00:00:00' THEN ''
                  ELSE p.added
                END AS 'Date_Ajout',
                CASE p.updated
                  WHEN '0000-00-00 00:00:00' THEN ''
                  ELSE p.updated
                END AS 'Date_Mise_Jour',
                REPLACE(ps.label,',','') AS 'Status',
                pn.note AS 'Note',
                CASE REPLACE(co.name,',','')
                  WHEN '' THEN 'A renseigner'
                  ELSE REPLACE(co.name,',','')
                END AS 'Nom_Societe',
                REPLACE(co.forme,',','') AS 'Forme',
                REPLACE(REPLACE(co.siren,'\t',''),',','') AS 'Siren',
                REPLACE(ca.address,',','') as 'Adresse1',
                '' as 'Adresse2',
                REPLACE(ca.zip,',','') AS 'CP',
                REPLACE(ca.city,',','') AS 'Ville',
                ca.id_country AS 'IdPays',
                REPLACE(co.phone,'\t','') AS 'Telephone',
                co.status_client AS 'Status_Client',
                CASE co.added
                  WHEN '0000-00-00 00:00:00' THEN ''
                  ELSE co.added
                END AS 'Date_ajout',
                CASE co.updated
                  WHEN '0000-00-00 00:00:00' THEN ''
                  ELSE co.updated
                END AS 'Date_Mise_A_Jour',
                co.id_client_owner AS 'IDClient'
            FROM projects p
            LEFT JOIN companies co ON (p.id_company = co.id_company)
            LEFT JOIN company_address ca ON co.id_company = ca.id_company
            LEFT JOIN clients cl ON (cl.id_client = co.id_client_owner)
            LEFT JOIN projects_notes pn ON (p.id_project = pn.id_project)
            LEFT JOIN projects_status ps ON ps.status = p.status";

        return $this->bdd->executeQuery($sQuery);
    }

    public function getAverageFundingTime(\DateTime $startingDate = null)
    {
        if (is_null($startingDate)) {
            $startingDate = new \DateTime('2014-04-01');
        }

        $query = 'SELECT
                      FLOOR(AVG(t.DurationFunding / 60 / 24)) AS days,
                      FLOOR(MOD(AVG(t.DurationFunding / 60), 24)) AS hours,
                      FLOOR(MOD(AVG(t.DurationFunding), 60)) AS minutes,
                      FLOOR(AVG(t.DurationFunding / 60)) AS totalHours,
                      ROUND(AVG(t.DurationFunding)) AS totalMinutes
                    FROM (
                     SELECT
                       date_funded,
                       date_publication,
                       ROUND(TIMESTAMPDIFF(SECOND, date_publication, date_funded) / 60) AS DurationFunding -- minutes
                     FROM projects
                     WHERE date_funded != "0000-00-00" AND date_retrait > :date
                    ) AS t ';

        $statement               = $this->bdd->executeQuery($query, ['date' => $startingDate->format('Y-m-d')], ['date' => \PDO::PARAM_STR]);
        $dateIntervalInformation = $statement->fetch(\PDO::FETCH_ASSOC);

        return $dateIntervalInformation;
    }

    public function getGlobalAverageRateOfFundedProjects($limit)
    {
        $query = '
            SELECT SUM(amount * rate) / SUM(amount)
            FROM (
                SELECT
                  loans.rate,
                  loans.amount
                FROM projects p
                   INNER JOIN loans ON p.id_project = loans.id_project
                WHERE p.status >= ' . ProjectsStatus::REMBOURSEMENT . '
                ORDER BY p.date_fin DESC
                LIMIT :limit
            ) AS last_loans';

        $statement = $this->bdd->executeQuery($query, ['limit' => $limit], ['limit' => \PDO::PARAM_INT]);

        return $statement->fetchColumn(0);
    }

    public function getAverageNumberOfLendersForProject()
    {
        $sQuery = '
            SELECT ROUND(AVG(t.lenderCount), 0) 
            FROM (
                SELECT id_project, 
                    COUNT(DISTINCT id_lender) AS lenderCount 
                FROM loans
                WHERE status = ' . Loans::STATUS_ACCEPTED . ' 
                GROUP BY id_project
            ) AS t ';

        $oStatement = $this->bdd->executeQuery($sQuery);

        return $oStatement->fetchColumn(0);
    }

    public function getAverageAmount()
    {
        $query     = 'SELECT ROUND(AVG(amount), 0)
                    FROM projects
                    WHERE status >= ' . ProjectsStatus::REMBOURSEMENT;
        $statement = $this->bdd->executeQuery($query);

        return $statement->fetchColumn(0);
    }

    public function getNumberOfUniqueProjectRequests($startDate)
    {
        $bind = ['startDate' => $startDate];
        $type = ['startDate' => \PDO::PARAM_STR];

        $query = 'SELECT COUNT(DISTINCT companies.siren)
                    FROM `projects`
                      INNER JOIN companies USING (id_company)
                    WHERE LENGTH(companies.siren) = 9 AND companies.siren NOT IN ("123456789", "987654321", "987456321", "123654789",
                    "999999999", "888888888", "777777777", "666666666", "555555555", "444444444","333333333", "222222222", "111111111", "000000000") AND projects.added > :startDate';

        $statement = $this->bdd->executeQuery($query, $bind, $type);

        return $statement->fetchColumn(0);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function countProjectsByRegion(): array
    {
        $query = 'SELECT
                      CASE
                      WHEN LEFT(client_base.cp, 2) IN (08, 10, 51, 52, 54, 55, 57, 67, 68, 88)
                        THEN "44"
                      WHEN LEFT(client_base.cp, 2) IN (16, 17, 19, 23, 24, 33, 40, 47, 64, 79, 86, 87)
                        THEN "75"
                      WHEN LEFT(client_base.cp, 2) IN (01, 03, 07, 15, 26, 38, 42, 43, 63, 69, 73, 74)
                        THEN "84"
                      WHEN LEFT(client_base.cp, 2) IN (21, 25, 39, 58, 70, 71, 89, 90)
                        THEN "27"
                      WHEN LEFT(client_base.cp, 2) IN (22, 29, 35, 56)
                        THEN "53"
                      WHEN LEFT(client_base.cp, 2) IN (18, 28, 36, 37, 41, 45)
                        THEN "24"
                      WHEN LEFT(client_base.cp, 2) IN (20)
                        THEN "94"
                      WHEN LEFT(client_base.cp, 3) IN (971)
                        THEN "01"
                      WHEN LEFT(client_base.cp, 3) IN (973)
                        THEN "03"
                      WHEN LEFT(client_base.cp, 2) IN (75, 77, 78, 91, 92, 93, 94, 95)
                        THEN "11"
                      WHEN LEFT(client_base.cp, 3) IN (974)
                        THEN "04"
                      WHEN LEFT(client_base.cp, 2) IN (09, 11, 12, 30, 31, 32, 34, 46, 48, 65, 66, 81, 82)
                        THEN "76"
                      WHEN LEFT(client_base.cp, 3) IN (972)
                        THEN "02"
                      WHEN LEFT(client_base.cp, 3) IN (976)
                        THEN "06"
                      WHEN LEFT(client_base.cp, 2) IN (02, 59, 60, 62, 80)
                        THEN "32"
                      WHEN LEFT(client_base.cp, 2) IN (14, 27, 50, 61, 76)
                        THEN "28"
                      WHEN LEFT(client_base.cp, 2) IN (44, 49, 53, 72, 85)
                        THEN "52"
                      WHEN LEFT(client_base.cp, 2) IN (04, 05, 06, 13, 83, 84)
                        THEN "93"
                      ELSE "0"
                      END AS insee_region_code,
                      COUNT(*) AS count
                    FROM (
                          SELECT
                            clients.id_client,
                            ca.zip AS cp
                          FROM projects
                            INNER JOIN companies ON projects.id_company = companies.id_company
                            INNER JOIN clients ON clients.id_client = companies.id_client_owner
                            INNER JOIN company_address ca ON ca.id_company = companies.id_company
                            INNER JOIN projects_status_history ON projects.id_project = projects_status_history.id_project AND projects_status_history.id_project_status = 4
                        ) AS client_base
                    GROUP BY insee_region_code
                    HAVING insee_region_code != "0"';

        $statement    = $this->bdd->executeQuery($query);
        $regionsCount = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $regionsCount[] = $row;
        }

        return $regionsCount;
    }

    public function countProjectsByCategory()
    {
        $query = 'SELECT
                  companies.sector,
                  count(companies.sector) AS count
                FROM projects
                  INNER JOIN companies ON projects.id_company = companies.id_company
                  INNER JOIN projects_status_history
                    ON projects.id_project = projects_status_history.id_project AND projects_status_history.id_project_status = 4
                GROUP BY companies.sector';

        $statement       = $this->bdd->executeQuery($query);
        $categoriesCount = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $categoriesCount[$row['sector']] = $row['count'];
        }

        return $categoriesCount;
    }

    /**
     * get the lender loans details split by sector
     * @param int $lenderId
     * @return array
     */
    public function getLoanDetailsAllocation($lenderId)
    {
        $result = [];
        $sql    = '
            SELECT
                companies.sector,
                COUNT(companies.sector) AS count,
                SUM(l.amount) / 100 AS loaned_amount,
                AVG(l.rate) AS average_rate
            FROM companies
            INNER JOIN projects ON projects.id_company = companies.id_company
            INNER JOIN projects_status_history ON projects.id_project = projects_status_history.id_project AND projects_status_history.id_project_status = 4
            INNER JOIN loans l ON l.id_project = projects.id_project
            WHERE l.id_lender = :id_lender AND l.status = ' . Loans::STATUS_ACCEPTED . '
            GROUP BY companies.sector';

        /** @var Statement $query */
        $query = $this->bdd->executeQuery($sql, ['id_lender' => $lenderId], ['id_lender' => \PDO::PARAM_INT]);

        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
            $result[$row['sector']] = $row;
        }
        return $result;
    }

    public function countProjectsFundedIn24Hours(\DateTime $startDate)
    {
        $bind = ['startDate' => $startDate->format('Y-m-d h:i:s')];
        $type = ['startDate' => \PDO::PARAM_STR];

        $query = 'SELECT count(projects.id_project)
                    FROM projects
                    WHERE ROUND(TIMESTAMPDIFF(SECOND, date_publication, date_funded)/120) <= 24
                    AND date_funded >= :startDate AND status >= ' . ProjectsStatus::FUNDE;

        $statement = $this->bdd->executeQuery($query, $bind, $type);

        return $statement->fetchColumn(0);
    }

    public function countProjectsFundedSince(\DateTime $startDate)
    {
        $bind = ['startDate' => $startDate->format('Y-m-d h:i:s')];
        $type = ['startDate' => \PDO::PARAM_STR];

        $query = 'SELECT count(projects.id_project)
                    FROM projects
                    WHERE date_funded >= :startDate AND status >=' . ProjectsStatus::FUNDE;

        $statement = $this->bdd->executeQuery($query, $bind, $type);

        return $statement->fetchColumn(0);
    }

    public function getHighestAmountObtainedFastest()
    {
        $query = 'SELECT
                      amount
                  FROM projects
                    WHERE date_funded != \'0000-00-00 00:00:00\'
                    GROUP BY id_project
                    ORDER BY SECOND(TIMEDIFF(date_funded, date_publication)) ASC, amount DESC
                    LIMIT 1';

        $statement = $this->bdd->executeQuery($query);

        return $statement->fetchColumn(0);
    }

    /**
     * @param bool $groupFirstYears
     *
     * @return mixed
     */
    public function countFundedProjectsByCohort($groupFirstYears = true)
    {
        if ($groupFirstYears) {
            $cohortSelect = 'CASE LEFT(projects_status_history.added, 4)
                                WHEN 2013 THEN "2013-2014"
                                WHEN 2014 THEN "2013-2014"
                                ELSE LEFT(projects_status_history.added, 4)
                             END';
        } else {
            $cohortSelect = 'LEFT(projects_status_history.added, 4)';
        }

        $query = 'SELECT COUNT(DISTINCT id_project) AS amount,
                    (
                        SELECT ' . $cohortSelect . ' AS date_range
                        FROM projects_status_history
                        INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                        WHERE  projects_status.status = ' . ProjectsStatus::REMBOURSEMENT . '
                          AND projects.id_project = projects_status_history.id_project
                        ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
                      ) AS cohort
                       FROM projects
                    WHERE projects.status >= ' . ProjectsStatus::REMBOURSEMENT . '
                    GROUP BY cohort';

        $statement = $this->bdd->executeQuery($query);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param DateTime $declarationDate
     * @param array    $contractType
     *
     * @return array
     * @throws Exception
     */
    public function getDataForBDFDeclaration(\DateTime $declarationDate, array $contractType): array
    {
        $bind = [
            'statusRepayment'                  => ProjectsStatus::REMBOURSEMENT,
            'statusProblem'                    => ProjectsStatus::PROBLEME,
            'loanAccepted'                     => Loans::STATUS_ACCEPTED,
            'declarationLastDay'               => $declarationDate->format('Y-m-t'),
            'inBonis'                          => CompanyStatus::STATUS_IN_BONIS,
            'collectiveProceeding'             => [
                CompanyStatus::STATUS_PRECAUTIONARY_PROCESS,
                CompanyStatus::STATUS_RECEIVERSHIP,
                CompanyStatus::STATUS_COMPULSORY_LIQUIDATION,
            ],
            'projectStatusList'                => [
                ProjectsStatus::REMBOURSEMENT,
                ProjectsStatus::PROBLEME,
                ProjectsStatus::LOSS,
            ],
            'clientTypePerson'                 => [
                Clients::TYPE_PERSON,
                Clients::TYPE_PERSON_FOREIGNER
            ],
            'clientTypeLegalEntity'            => [
                Clients::TYPE_LEGAL_ENTITY,
                Clients::TYPE_LEGAL_ENTITY_FOREIGNER
            ],
            'opSubTypeTypeRepayment'           => [
                OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION,
                OperationSubType::GROSS_INTEREST_REPAYMENT_DEBT_COLLECTION

            ],
            'opSubTypeRepaymentRegularization' => [
                OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION_REGULARIZATION,
                OperationSubType::GROSS_INTEREST_REPAYMENT_DEBT_COLLECTION_REGULARIZATION
            ],
            'contractType'                     => $contractType
        ];
        $type = [
            'statusRepayment'                  => \PDO::PARAM_INT,
            'statusProblem'                    => \PDO::PARAM_INT,
            'loanAccepted'                     => \PDO::PARAM_INT,
            'declarationLastDay'               => \PDO::PARAM_STR,
            'inBonis'                          => \PDO::PARAM_STR,
            'collectiveProceeding'             => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
            'projectStatusList'                => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            'clientTypePerson'                 => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            'clientTypeLegalEntity'            => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            'opSubTypeTypeRepayment'           => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
            'opSubTypeRepaymentRegularization' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
            'contractType'                     => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY,
        ];

        $sql = "
        SELECT
          com.siren,
          com.name,
          p.id_project,
          p.status AS projectStatus,
          cs.label AS companyStatusLabel,
          p.id_project_need,
          CASE
            WHEN p.id_project_need IN (14, 16, 17, 26, 29, 30) THEN 'AU'
            WHEN p.id_project_need IN (4, 5, 6, 7, 8) THEN 'CO'
            WHEN p.id_project_need IN (24, 25) THEN 'EX'
            WHEN p.id_project_need IN (2, 3, 9, 10, 28, 32) THEN 'IM'
            WHEN p.id_project_need IN (11, 12, 15, 18, 19, 20, 21, 22, 23, 27, 31, 33, 34, 35) THEN 'MA'
            WHEN p.id_project_need IN (13) THEN 'ST'
            ELSE 'AU'
          END AS loan_type,
          ROUND(SUM(l.amount) / 100, 0) AS partial_loan_amount,
          p.amount AS loan_amount,
          (SELECT MIN(psh.added) FROM projects_status_history psh INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status AND ps.status = :statusRepayment WHERE psh.id_project = p.id_project) AS loan_date,
          CASE
            WHEN p.close_out_netting_date IS NOT NULL AND p.close_out_netting_date != '0000-00-00' THEN NULL
            ELSE
              CASE
                WHEN cs.label = :inBonis AND p.status = :statusProblem THEN
                  (SELECT MAX(psh.added) FROM projects_status_history psh INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status AND ps.status = :statusProblem WHERE psh.id_project = p.id_project)
                ELSE NULL
              END
          END AS late_payment_date,
          p.period AS loan_duration,
          p.interest_rate AS average_loan_rate,
          ROUND(SUM(l.amount * l.rate) / SUM(l.amount), 2) AS partial_average_loan_rate, -- If we remove this line, the query does not finish !
          'M' AS repayment_frequency,
          (SELECT csh.changed_on FROM company_status_history csh WHERE csh.id = (
            SELECT MIN(csh_min.id) FROM company_status_history csh_min
            INNER JOIN company_status cs_min ON cs_min.id = csh_min.id_status AND cs_min.label IN (:collectiveProceeding) WHERE csh_min.id_company = p.id_company)
          ) AS judgement_date,
          CASE
            WHEN p.close_out_netting_date IS NOT NULL AND p.close_out_netting_date != '0000-00-00' THEN p.close_out_netting_date
            ELSE NULL
          END AS close_out_netting_date,
          (
            SELECT IFNULL(SUM(o.amount),0)
            FROM operation o FORCE INDEX (idx_operation_id_sub_type)
            WHERE id_sub_type in (SELECT id FROM operation_sub_type WHERE label IN (:opSubTypeTypeRepayment))
            AND id_project = p.id_project
          ) - (
            SELECT IFNULL(SUM(o.amount),0)
            FROM operation o FORCE INDEX (idx_operation_id_sub_type)
            WHERE id_sub_type in (SELECT id FROM operation_sub_type WHERE label IN (:opSubTypeRepaymentRegularization))
                  AND id_project = p.id_project
          ) AS debt_collection_repayment,
          (SELECT IFNULL(COUNT(DISTINCT l.id_lender), 0) FROM loans l
            INNER JOIN wallet w ON w.id = l.id_lender
            INNER JOIN clients c ON c.id_client = w.id_client
            INNER JOIN underlying_contract contract ON l.id_type_contract = contract.id_contract
          WHERE l.id_project = p.id_project AND c.type IN (:clientTypePerson) AND contract.label IN (:contractType)) AS contributor_person_number,
          (SELECT ROUND(SUM(IFNULL(l.amount, 0)) / 100, 2) FROM loans l
            INNER JOIN wallet w ON w.id = l.id_lender
            INNER JOIN clients c ON c.id_client = w.id_client
            INNER JOIN underlying_contract contract ON l.id_type_contract = contract.id_contract
          WHERE l.id_project = p.id_project AND c.type IN (:clientTypePerson) AND contract.label IN (:contractType)) AS contributor_person_amount,
          (SELECT IFNULL(COUNT(DISTINCT l.id_lender), 0) FROM loans l
            INNER JOIN wallet w ON w.id = l.id_lender
            INNER JOIN clients c ON c.id_client = w.id_client
            INNER JOIN underlying_contract contract ON l.id_type_contract = contract.id_contract
          WHERE l.id_project = p.id_project AND c.type IN (:clientTypeLegalEntity) AND c.id_client NOT IN (15112) AND contract.label IN (:contractType)) AS contributor_legal_entity_number,
          (SELECT ROUND(SUM(IFNULL(l.amount, 0)) / 100, 2) FROM loans l
            INNER JOIN wallet w ON w.id = l.id_lender
            INNER JOIN clients c ON c.id_client = w.id_client
            INNER JOIN underlying_contract contract ON l.id_type_contract = contract.id_contract
          WHERE l.id_project = p.id_project AND c.type IN (:clientTypeLegalEntity) AND c.id_client NOT IN (15112) AND contract.label IN (:contractType)) AS contributor_legal_entity_amount,
          (SELECT IFNULL(COUNT(DISTINCT l.id_lender), 0) FROM loans l
            INNER JOIN underlying_contract contract ON l.id_type_contract = contract.id_contract
          WHERE l.id_project = p.id_project AND l.id_lender = (SELECT w.id FROM wallet w WHERE w.id_client = 15112) AND contract.label IN (:contractType)) AS contributor_credit_institution_number,
          (SELECT ROUND(SUM(IFNULL(l.amount, 0)) / 100, 2) FROM loans l
            INNER JOIN underlying_contract contract ON l.id_type_contract = contract.id_contract
          WHERE l.id_project = p.id_project AND l.id_lender = (SELECT w.id FROM wallet w WHERE w.id_client = 15112) AND contract.label IN (:contractType)) AS contributor_credit_institution_amount
        FROM projects p
          INNER JOIN companies com ON  com.id_company = p.id_company
          INNER JOIN loans l ON l.id_project = p.id_project AND l.status = :loanAccepted
          INNER JOIN company_status cs ON cs.id = com.id_status
          INNER JOIN underlying_contract contract ON l.id_type_contract = contract.id_contract
        WHERE p.status IN (:projectStatusList) AND contract.label IN (:contractType)
        GROUP BY l.id_project
        HAVING DATE(loan_date) <= :declarationLastDay
        ORDER BY loan_date ASC";

        /** @var Statement $statement */
        $statement = $this->bdd->executeQuery($sql, $bind, $type);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string $search
     *
     * @return array
     */
    public function searchProjectsByName($search)
    {
        if (empty($search)) {
            return [];
        }

        $query = '
            SELECT
              p.id_project,
              p.slug AS slug,
              p.title AS title,
              (SELECT ps.status FROM projects_status ps LEFT JOIN projects_status_history psh ON (ps.id_project_status = psh.id_project_status) WHERE psh.id_project = p.id_project ORDER BY psh.added DESC, psh.id_project_status_history DESC LIMIT 1) AS status
            FROM projects p
            WHERE p.display = ' . self::DISPLAY_PROJECT_ON . ' AND p.title LIKE :search
            HAVING status >= ' . ProjectsStatus::EN_FUNDING . '
            ORDER BY p.title ASC';

        /** @var \Doctrine\DBAL\Statement $statement */
        $statement             = $this->bdd->executeQuery($query, ['search' => '%' . $search . '%']);
        $searchProjectsResults = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $result                = [];

        if (false === empty($searchProjectsResults)) {
            foreach ($searchProjectsResults as $recordProjects) {
                $result[] = [
                    'projectId' => $recordProjects['id_project'],
                    'title'     => $recordProjects['title'],
                    'slug'      => 'projects/detail/' . $recordProjects['slug']
                ];
            }

            usort($result, function ($firstElement, $secondElement) {
                return strcasecmp($firstElement['title'], $secondElement['title']);
            });
        }

        return $result;
    }

    /**
     * @param users $user
     * @return array
     */
    public function getRiskUserProjects(\users $user)
    {
        $statement = $this->getRiskProjectsQuery()
            ->andWhere('p.id_analyste = :userId')
            ->setParameter('userId', $user->id_user)
            ->execute();

        $projects = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $projects;
    }

    /**
     * @param users $user
     * @return array
     */
    public function getRiskProjectsExcludingUser(\users $user)
    {
        $statement = $this->getRiskProjectsQuery()
            ->andWhere('p.id_analyste != :userId')
            ->setParameter('userId', $user->id_user)
            ->execute();

        $projects = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $projects;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function getRiskProjectsQuery()
    {
        return $this->bdd->createQueryBuilder()
            ->select('p.id_project,
                IFNULL(pa.logo, "") AS partner_logo,
                p.amount AS amount,
                p.period AS duration,
                p.status AS status,
                ps.label AS status_label,
                co.name AS company_name,
                co.siren AS siren,
                p.added AS creation,
                (SELECT MAX(added) FROM projects_status_history psh INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status WHERE psh.id_project = p.id_project AND ps.status = :waitingAnalystStatus) AS risk_status_datetime,
                TIMESTAMPDIFF(HOUR, (SELECT MAX(added) FROM projects_status_history psh INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status WHERE psh.id_project = p.id_project AND ps.status = :waitingAnalystStatus), NOW()) AS risk_status_duration,
                IFNULL((SELECT content FROM projects_comments WHERE id_project = p.id_project ORDER BY added DESC, id_project_comment DESC LIMIT 1), "") AS memo_content,
                IFNULL((SELECT added FROM projects_comments WHERE id_project = p.id_project ORDER BY added DESC, id_project_comment DESC LIMIT 1), "") AS memo_datetime,
                IFNULL((SELECT CONCAT(users.firstname, " ", users.name) FROM projects_comments INNER JOIN users ON projects_comments.id_user = users.id_user WHERE id_project = p.id_project ORDER BY projects_comments.added DESC, id_project_comment DESC LIMIT 1), "") AS memo_author,
                IFNULL(need.label, "") AS need,
                IFNULL(pn.pre_scoring, "") AS pre_scoring
            ')
            ->from('projects', 'p')
            ->innerJoin('p', 'companies', 'co', 'p.id_company = co.id_company')
            ->innerJoin('co', 'clients', 'cl', 'co.id_client_owner = cl.id_client')
            ->innerJoin('p', 'projects_status', 'ps', 'p.status = ps.status')
            ->leftJoin('p', 'partner', 'pa', 'p.id_partner = pa.id')
            ->leftJoin('p', 'projects_notes', 'pn', 'p.id_project = pn.id_project')
            ->leftJoin('p', 'project_need', 'need', 'p.id_project_need = need.id_project_need')
            ->where('p.status IN (:riskStatus)')
            ->andWhere('co.siren != \'\'')
            ->andWhere('co.siren IS NOT NULL')
            ->setParameter('waitingAnalystStatus', ProjectsStatus::PENDING_ANALYSIS)
            ->setParameter('riskStatus', ProjectsStatus::RISK_TEAM, Connection::PARAM_INT_ARRAY)
            ->addOrderBy('status', 'ASC')
            ->addOrderBy('risk_status_duration', 'DESC');
    }

    /**
     * @param users $user
     * @return array
     */
    public function getSaleUserProjects(\users $user)
    {
        $statement = $this->getSaleProjectsQuery(ProjectsStatus::SALES_TEAM)
            ->andWhere('p.id_commercial = :userId')
            ->setParameter('userId', $user->id_user)
            ->execute();

        $projects = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $projects;
    }

    /**
     * @param users $user
     * @return array
     */
    public function getSaleProjectsExcludingUser(\users $user)
    {
        $statement = $this->getSaleProjectsQuery(ProjectsStatus::SALES_TEAM)
            ->andWhere('p.id_commercial != :userId')
            ->setParameter('userId', $user->id_user)
            ->execute();

        $projects = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $projects;
    }

    /**
     * @return array
     */
    public function getUpcomingSaleProjects()
    {
        $queryBuilder = $this->getSaleProjectsQuery(ProjectsStatus::SALES_TEAM_UPCOMING_STATUS);
        $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->orX(
                    'p.status = :incompleteProjectStatus AND DATE_SUB(NOW(), INTERVAL 1 WEEK) < p.updated',
                    'p.status != :incompleteProjectStatus'
                )
            )
            ->setParameter('incompleteProjectStatus', ProjectsStatus::INCOMPLETE_REQUEST);

        $statement = $queryBuilder->execute();
        $projects  = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $projects;
    }

    /**
     * @param array $status
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function getSaleProjectsQuery(array $status)
    {
        return $this->bdd->createQueryBuilder()
            ->select('p.id_project,
                IFNULL(pa.logo, "") AS partner_logo,
                p.amount AS amount,
                p.period AS duration,
                p.status AS status,
                ps.label AS status_label,
                co.name AS company_name,
                co.siren AS siren,
                CONCAT(cl.prenom, " ", cl.nom) AS client_name,
                cl.telephone AS client_phone,
                p.added AS creation,
                IF(u.id_user IS NULL, "", CONCAT(u.firstname, " ", u.name)) AS assignee,
                IFNULL((SELECT content FROM projects_comments WHERE id_project = p.id_project ORDER BY added DESC, id_project_comment DESC LIMIT 1), "") AS memo_content,
                IFNULL((SELECT added FROM projects_comments WHERE id_project = p.id_project ORDER BY added DESC, id_project_comment DESC LIMIT 1), "") AS memo_datetime,
                IFNULL((SELECT CONCAT(users.firstname, " ", users.name) FROM projects_comments INNER JOIN users ON projects_comments.id_user = users.id_user WHERE id_project = p.id_project ORDER BY projects_comments.added DESC, id_project_comment DESC LIMIT 1), "") AS memo_author,
                IFNULL(pn.pre_scoring, -1) AS priority
            ')
            ->from('projects', 'p')
            ->innerJoin('p', 'companies', 'co', 'p.id_company = co.id_company')
            ->innerJoin('co', 'clients', 'cl', 'co.id_client_owner = cl.id_client')
            ->innerJoin('p', 'projects_status', 'ps', 'p.status = ps.status')
            ->leftJoin('p', 'users', 'u', 'p.id_commercial = u.id_user')
            ->leftJoin('p', 'partner', 'pa', 'p.id_partner = pa.id')
            ->leftJoin('p', 'projects_notes', 'pn', 'p.id_project = pn.id_project')
            ->where('p.status IN (:commercialStatus)')
            ->setParameter('commercialStatus', $status, Connection::PARAM_INT_ARRAY)
            ->addOrderBy('status', 'DESC')
            ->addOrderBy('priority', 'DESC')
            ->addOrderBy('amount', 'DESC')
            ->addOrderBy('duration', 'DESC')
            ->addOrderBy('creation', 'ASC');
    }
}
