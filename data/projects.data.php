<?php

// **************************************************************************************************** //
// ***************************************    ASPARTAM    ********************************************* //
// **************************************************************************************************** //
//
// Copyright (c) 2008-2011, equinoa
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
// associated documentation files (the "Software"), to deal in the Software without restriction,
// including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
// subject to the following conditions:
// The above copyright notice and this permission notice shall be included in all copies
// or substantial portions of the Software.
// The Software is provided "as is", without warranty of any kind, express or implied, including but
// not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement.
// In no event shall the authors or copyright holders equinoa be liable for any claim,
// damages or other liability, whether in an action of contract, tort or otherwise, arising from,
// out of or in connection with the software or the use or other dealings in the Software.
// Except as contained in this notice, the name of equinoa shall not be used in advertising
// or otherwise to promote the sale, use or other dealings in this Software without
// prior written authorization from equinoa.
//
//  Version : 2.4.0
//  Date : 21/03/2011
//  Coupable : CM
//
// **************************************************************************************************** //
use \Doctrine\DBAL\Statement;

class projects extends projects_crud
{
    const MINIMUM_CREATION_DAYS_PROSPECT = 720;
    const MINIMUM_CREATION_DAYS          = 1080;
    const MINIMUM_REVENUE                = 80000;

    const DISPLAY_PROJECT_ON  = 0;
    const DISPLAY_PROJECT_OFF = 1;

    const RISK_A = 5;
    const RISK_B = 4.5;
    const RISK_C = 4;
    const RISK_D = 3.5;
    const RISK_E = 3;
    const RISK_F = 2.5;
    const RISK_G = 2;
    const RISK_H = 1.5;

    const SORT_FIELD_SECTOR = 'sector';
    const SORT_FIELD_AMOUNT = 'amount';
    const SORT_FIELD_RATE   = 'rate';
    const SORT_FIELD_RISK   = 'risk';
    const SORT_FIELD_END    = 'end';

    const SORT_DIRECTION_ASC  = 'ASC';
    const SORT_DIRECTION_DESC = 'DESC';

    public function __construct($bdd, $params = '')
    {
        parent::projects($bdd, $params);
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

    public function searchDossiers($date1 = '', $date2 = '', $montant = '', $duree = '', $status = '', $analyste = '', $siren = '', $id = '', $raison_sociale = '', $iAdvisorId = '', $iSalesPersonId = '', $start = '', $nb = '')
    {
        $where = [];

        if (false === empty($date1)) {
            $where[] = 'p.added >= "' . $date1 . ' 00:00:00"';
        }
        if (false === empty($date2)) {
            $where[] = 'p.added <= "' . $date2 . ' 23:59:59"';
        }
        if (false === empty($montant)) {
            $where[] = 'p.amount = "' . $montant . '"';
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
                co.siren,
                co.name,
                ps.label
            FROM projects p
            LEFT JOIN companies co ON (p.id_company = co.id_company)
            LEFT JOIN projects_status ps on ps.status = p.status';
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
     * @return array
     */
    public function selectProjectsByStatus(array $status, $where = '', array $sort = [], $start = '', $nb = '', $useCache = true)
    {
        $binds = array('fundingStatus' => \projects_status::EN_FUNDING, 'status' => $status);

        if ($useCache) {
            $QCProfile = new \Doctrine\DBAL\Cache\QueryCacheProfile(60, md5(__METHOD__));
        } else {
            $QCProfile = null;
        }

        $select = '
            SELECT p.*,
                DATEDIFF(p.date_retrait_full, NOW()) AS daysLeft,
                CASE WHEN status = :fundingStatus
                    THEN "1"
                    ELSE "2"
                END AS lestatut';

        $tables = '
            FROM projects p';

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
                $order = 'c.sector ' . $sortDirection . ', p.date_retrait_full DESC, p.status ASC';
                $tables .= '
                    INNER JOIN companies c ON p.id_company = c.id_company';
                break;
            case self::SORT_FIELD_AMOUNT:
                $order = 'p.amount ' . $sortDirection . ', p.date_retrait_full DESC, p.status ASC';
                break;
            case self::SORT_FIELD_RATE:
                $select .= ',
                    CASE
                        WHEN p.status IN (' . implode(', ', [\projects_status::FUNDE, \projects_status::REMBOURSEMENT, \projects_status::REMBOURSE, \projects_status::PROBLEME, \projects_status::PROBLEME_J_X, \projects_status::RECOUVREMENT, \projects_status::REMBOURSEMENT_ANTICIPE, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::DEFAUT]) . ') THEN (SELECT SUM(amount * rate) / SUM(amount) AS avg_rate FROM loans WHERE id_project = p.id_project)
                        WHEN p.status IN (' . implode(', ', [\projects_status::PRET_REFUSE, \projects_status::EN_FUNDING, \projects_status::AUTO_BID_PLACED, \projects_status::A_FUNDER]) . ') THEN (SELECT SUM(amount * rate) / SUM(amount) AS avg_rate FROM bids WHERE id_project = p.id_project AND status IN (0, 1))
                        WHEN p.status IN (' . implode(', ', [\projects_status::FUNDING_KO]) . ') THEN (SELECT SUM(amount * rate) / SUM(amount) AS avg_rate FROM bids WHERE id_project = p.id_project)
                    END AS avg_rate';
                $order = 'avg_rate ' . $sortDirection . ', p.date_retrait_full DESC, p.status ASC';
                break;
            case self::SORT_FIELD_RISK:
                $sortDirection = $sortDirection === self::SORT_DIRECTION_DESC ? self::SORT_DIRECTION_ASC : self::SORT_DIRECTION_DESC;
                $order         = 'p.risk ' . $sortDirection . ', p.date_retrait_full DESC, p.status ASC';
                break;
            case self::SORT_FIELD_END:
            default:
                if ($sortDirection === self::SORT_DIRECTION_ASC) {
                    $order = 'lestatut DESC, IF(lestatut = 2, p.date_fin, "") ASC, IF(lestatut = 1, p.date_fin, "") DESC, p.status ASC';
                } else {
                    $order = 'lestatut ASC, IF(lestatut = 2, p.date_fin, "") DESC, IF(lestatut = 1, p.date_fin, "") ASC, p.status DESC';
                }
                break;
        }

        $sql = $select . $tables . '
            WHERE p.status IN (:status) ' . $where . '
            ORDER BY ' . $order;

        if (is_numeric($nb)) {
            $binds['number'] = $nb;
            $sql .= ' LIMIT :number ';

            if (is_numeric($start)) {
                $binds['start'] = $start;
                $sql .= ' OFFSET :start';
            }
        }

        try {
            $aTypes = array(
                'status'        => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                'fundingStatus' => \PDO::PARAM_INT,
                'minRateRange'  => \PDO::PARAM_INT,
                'maxRateRange'  => \PDO::PARAM_INT,
                'number'        => \PDO::PARAM_INT,
                'start'         => \PDO::PARAM_INT
            );
            $statement = $this->bdd->executeQuery($sql, $binds, $aTypes, $QCProfile);
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $statement->closeCursor();
        } catch (\Doctrine\DBAL\DBALException $ex) {
            $result = array();
        }
        return $result;
    }

    public function countSelectProjectsByStatus($status, $where = '', $bUseCache = false)
    {
        if (true === $bUseCache) {
            $oQCProfile = new \Doctrine\DBAL\Cache\QueryCacheProfile(60, md5(__METHOD__));
        } else {
            $oQCProfile = null;
        }
        $aBind = array('status' => explode(',', $status));
        $aType = array('status' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        $sQuery = '
            SELECT COUNT(*) AS nb_project
            FROM projects
            WHERE status IN (:status)' . $where;

        try {
            $statement = $this->bdd->executeQuery($sQuery, $aBind, $aType, $oQCProfile);
            $result = $statement->fetchAll(PDO::FETCH_COLUMN);
            $statement->closeCursor();
            if (empty($result)) {
                return 0;
            }
            return array_shift($result);
        } catch (\Doctrine\DBAL\DBALException $ex) {
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
            $where .= ' AND p.title_bo LIKE "%' . $projet . '%"';
        }
        if (false === empty($email)) {
            $where .= ' AND c.email = "' . $email . '"';
        }

        $result   = array();
        $resultat = $this->bdd->query('
            SELECT p.id_project,
                p.title_bo,
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

    public function positionProject($projectId, array $status, $order)
    {
        $aProjects = $this->selectProjectsByStatus($status, ' AND p.display = 0', $order);
        $previous = '';
        $next = '';

        foreach ($aProjects as $k => $p) {
            if ($p['id_project'] == $projectId) {
                $previous = isset($aProjects[$k - 1]) ? $aProjects[$k - 1] : null;
                $next     = isset($aProjects[$k + 1]) ? $aProjects[$k + 1] : null;
                break;
            }
        }
        return array('previousProject' => $previous, 'nextProject' => $next);
    }

    // liste les projets favoris dont la date de retrait est dans j-2
    public function getDerniersFav($id_client)
    {
        $sql = 'SELECT * FROM `favoris` WHERE id_client = ' . $id_client;

        $resultat = $this->bdd->query($sql);
        $result   = array();

        if (0 < $this->bdd->num_rows($resultat)) {
            $lesfav   = '';
            $i        = 0;
            while ($f = $this->bdd->fetch_assoc($resultat)) {
                $lesfav .= ($i > 0 ? ',' : '') . $f['id_project'];
                $i++;
            }

            $sql = 'SELECT *,DATEDIFF(date_retrait,CURRENT_DATE) as datediff FROM projects WHERE id_project IN (' . $lesfav . ') AND DATEDIFF(date_retrait,CURRENT_DATE)<=2 AND DATEDIFF(date_retrait,CURRENT_DATE)>=0 AND date_fin = "0000-00-00 00:00:00" ORDER BY datediff';

            $resultat = $this->bdd->query($sql);

            while ($record = $this->bdd->fetch_assoc($resultat)) {
                $result[] = $record;
            }
        }

        return $result;
    }

    public function getLastProject($id_company)
    {
        $sql = 'SELECT id_project
                FROM projects
                WHERE id_company = ' . $id_company . '
                ORDER BY added DESC
                LIMIT 1';

        $result     = $this->bdd->query($sql);
        $id_project = (int) ($this->bdd->result($result, 0, 0));

        return parent::get($id_project, 'id_project');
    }

    public function countProjectsByStatusAndLender($lender, $status)
    {
        if (is_array($status)) {
            $statusString = implode(",", $status);
        }

        $sql = '
            SELECT COUNT(DISTINCT l.id_project)
            FROM loans l
            INNER JOIN projects p ON l.id_project = p.id_project
            WHERE id_lender = ' . $lender . '
            AND l.status = 0
            AND p.status IN (' . $statusString . ')';

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
            WHERE date_publication_full >= (SELECT added FROM clients WHERE id_client = ' . $client . ')
                AND status IN (' . $statusString . ')';
        $result = $this->bdd->query($sql);
        $record = $this->bdd->result($result);

        return $record;
    }

    public function getProjectsStatusAndCount(array $sListStatus, array $tabOrderProject, $iStart, $iLimit)
    {
        $aProjects   = $this->selectProjectsByStatus($sListStatus, ' AND p.display = 0', $tabOrderProject, $iStart, $iLimit);
        $anbProjects = $this->countSelectProjectsByStatus(implode(',', $sListStatus) . ',' . \projects_status::PRET_REFUSE, ' AND display = 0', true);
        $aElements   = array(
            'lProjectsFunding' => $aProjects,
            'nbProjects'       => $anbProjects
        );
        return $aElements;
    }

    public function getAttachments($project = null)
    {
        if (null === $project) {
            $project = $this->id_project;
        }

        if (! $project) {
            return false;
        }

        $sql = 'SELECT a.id, a.id_type, a.id_owner, a.type_owner, a.path, a.added, a.updated, a.archived
                FROM attachment a
                WHERE a.id_owner = ' . $project . '
                    AND a.type_owner = "projects"';

        $result      = $this->bdd->query($sql);
        $attachments = array();
        while ($record = $this->bdd->fetch_assoc($result)) {
            $attachments[$record["id_type"]] = $record;
        }
        return $attachments;
    }

    /**
     * Retrieve the list of project IDs that needs email reminder
     * @param int    $iStatus                Project status
     * @param int    $iDaysInterval          Interval in days since previous reminder
     * @param int    $iPreviousReminderIndex Previous reminder for counting days interval
     * @return array
     */
    public function getReminders($iStatus, $iDaysInterval, $iPreviousReminderIndex)
    {
        $aProjects = array();
        $rResult   = $this->bdd->query('
            SELECT p.id_project
            FROM projects p
            INNER JOIN (SELECT id_project, MAX(id_project_status_history) AS id_project_status_history FROM projects_status_history GROUP BY id_project) plsh ON plsh.id_project = p.id_project
            INNER JOIN projects_status_history psh ON psh.id_project_status_history = plsh.id_project_status_history
            WHERE p.status = ' . $iStatus . '
                AND DATE_SUB(CURDATE(), INTERVAL ' . $iDaysInterval . ' DAY) = DATE(psh.added)
                AND psh.numero_relance = ' . $iPreviousReminderIndex
        );

        if ($this->bdd->num_rows($rResult) > 0) {
            while ($aResult = $this->bdd->fetch_assoc($rResult)) {
                $aProjects[] = (int) $aResult['id_project'];
            }
        }

        return $aProjects;
    }

    /**
     * Retrieve the of projects in fast process that still at step 3 after one hour
     * @return array
     */
    public function getFastProcessStep3()
    {
        $aProjects = array();
        $rResult   = $this->bdd->query('
            SELECT *
            FROM projects
            WHERE status = ' . \projects_status::COMPLETUDE_ETAPE_3 . '
                AND DATE_SUB(NOW(), INTERVAL 1 HOUR) > added
                AND process_fast = 1'
        );

        if ($this->bdd->num_rows($rResult) > 0) {
            while ($aResult = $this->bdd->fetch_assoc($rResult)) {
                $aProjects[] = (int) $aResult['id_project'];
            }
        }

        return $aProjects;
    }

    public function getProjectsInDebt()
    {
        $aProjects = array();
        $rResult   = $this->bdd->query('
            SELECT *
            FROM projects
            WHERE status IN (' . implode(', ', [\projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE]) . ')'
        );

        if ($this->bdd->num_rows($rResult) > 0) {
            while ($aResult = $this->bdd->fetch_assoc($rResult)) {
                $aProjects[] = (int) $aResult['id_project'];
            }
        }

        return $aProjects;
    }

    /**
     * @return float
     */
    public function getAverageInterestRate()
    {
        switch ($this->status) {
            case \projects_status::FUNDE:
            case \projects_status::REMBOURSEMENT:
            case \projects_status::REMBOURSE:
            case \projects_status::PROBLEME:
            case \projects_status::PROBLEME_J_X:
            case \projects_status::RECOUVREMENT:
            case \projects_status::REMBOURSEMENT_ANTICIPE:
            case \projects_status::PROCEDURE_SAUVEGARDE:
            case \projects_status::REDRESSEMENT_JUDICIAIRE:
            case \projects_status::LIQUIDATION_JUDICIAIRE:
            case \projects_status::DEFAUT:
                $rResult = $this->bdd->query('
                    SELECT SUM(amount * rate) / SUM(amount) AS avg_rate
                    FROM loans
                    WHERE id_project = ' . $this->id_project
                );
                return round($this->bdd->result($rResult, 0, 0), 2);
            case \projects_status::PRET_REFUSE:
            case \projects_status::EN_FUNDING:
            case \projects_status::AUTO_BID_PLACED:
            case \projects_status::BID_TERMINATED:
            case \projects_status::A_FUNDER:
                $rResult = $this->bdd->query('
                    SELECT SUM(amount * rate) / SUM(amount) AS avg_rate
                    FROM bids
                    WHERE id_project = ' . $this->id_project . '
                    AND status IN (0, 1)'
                );
                return round($this->bdd->result($rResult, 0, 0), 2);
            case \projects_status::FUNDING_KO:
                $rResult = $this->bdd->query('
                    SELECT SUM(amount * rate) / SUM(amount) AS avg_rate
                    FROM bids
                    WHERE id_project = ' . $this->id_project
                );
                return round($this->bdd->result($rResult, 0, 0), 2);
            default:
                trigger_error('Unknown project status : ' . $this->status . ' Could not calculate amounts', E_USER_WARNING);
                break;
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
            LEFT JOIN lenders_accounts la ON l.id_lender = la.id_lender_account
            LEFT JOIN clients c ON la.id_client_owner = c.id_client
            LEFT JOIN companies com ON la.id_company_owner = com.id_company
            WHERE id_project = ' . $iProjectId;

        $result           = $this->bdd->query($sql);
        $aLoansAndLenders = array();

        while ($record = $this->bdd->fetch_assoc($result)) {
            $aLoansAndLenders[] = $record;
        }

        return $aLoansAndLenders;
    }

    public function getDuePaymentsAndLenders($iProjectId = null, $iOrder = null)
    {
        if ($iProjectId === null) {
            $iProjectId = $this->id_project;
        }

        $sOrder = (isset($iOrder)) ? ' AND ordre = ' . $iOrder : null;

        $sql = '
            SELECT
                e.id_lender,
                c.nom,
                c.prenom,
                com.name,
                e.montant,
                e.capital,
                e.interets,
                e.date_echeance_emprunteur_reel as date
            FROM echeanciers e
            LEFT JOIN lenders_accounts la ON e.id_lender = la.id_lender_account
            LEFT JOIN clients c ON la.id_client_owner = c.id_client
            LEFT JOIN companies com ON la.id_company_owner = com.id_company
            WHERE id_project = ' . $iProjectId . $sOrder;

        $result                 = $this->bdd->query($sql);
        $aDuePaymentsAndLenders = array();

        while ($record = $this->bdd->fetch_assoc($result)) {
            $aDuePaymentsAndLenders[] = $record;
        }

        return $aDuePaymentsAndLenders;
    }

    public function getProblematicProjectsWithUpcomingRepayment()
    {
        $aProjects = array();
        $rResult   = $this->bdd->query('
            SELECT p.*
            FROM projects p
            INNER JOIN (SELECT id_project, MIN(date_echeance_emprunteur) AS date_echeance_emprunteur FROM echeanciers_emprunteur WHERE status_emprunteur = 0 GROUP BY id_project) min_unpaid ON min_unpaid.id_project = p.id_project
            INNER JOIN echeanciers_emprunteur prev ON prev.id_project = p.id_project AND prev.date_echeance_emprunteur = min_unpaid.date_echeance_emprunteur
            INNER JOIN echeanciers_emprunteur next ON next.id_project = p.id_project AND next.ordre = prev.ordre + 1 AND next.status_emprunteur = 0
            WHERE p.status = ' . \projects_status::PROBLEME_J_X . '
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
        $wherePublished    = '';
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
            $bind['starting_date']    = $startingDate;
            $type['starting_date']    = \PDO::PARAM_STR;
            $wherePublished = ' AND DATE(p.date_publication_full) >=  :starting_date';
        }

        $sQuery = '
            SELECT AVG(t1.weighted_rate_by_project)
            FROM (
                SELECT SUM(t.amount * t.rate) / SUM(t.amount) as weighted_rate_by_project
                FROM (
                    SELECT l.id_loan, l.amount, l.rate, l.added, p.id_project, p.period
                    FROM loans l
                    INNER JOIN projects p ON p.id_project = l.id_project
                    WHERE p.status >= ' . \projects_status::FUNDE . '
                        AND p.status != ' . \projects_status::FUNDING_KO . $whereRisk . $whereDurationMin . $whereDurationMax . $wherePublished . '
                ) t
                GROUP BY t.id_project
            ) t1';

        try {
            $statement = $this->bdd->executeQuery($sQuery, $bind, $type, new \Doctrine\DBAL\Cache\QueryCacheProfile(1800, md5(__METHOD__)));
            $result = $statement->fetchAll(PDO::FETCH_COLUMN);
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
                          WHERE l.status = 0 AND p.status > ' . \projects_status::EN_FUNDING . '
                          AND p.date_fin BETWEEN "' . $oDateFrom->format('Y-m-d H:i:s') . '" AND "' . $oDateTo->format('Y-m-d H:i:s') . '"
                          GROUP BY l.id_loan
                       ) t
                       GROUP BY t.id_project
                    ) pg
                    INNER JOIN (
                      SELECT count(b.id_bid) as bids_nb, b.id_project
                      FROM bids b
                      WHERE b.status = ' . \bids::STATUS_BID_ACCEPTED . '
                      GROUP BY b.id_project
                    ) pb ON pb.id_project = pg.id_project
                    LEFT JOIN (
                      SELECT t1.id_project, ROUND(SUM(t1.amount) / 100, 2) as amount_total_autobid, SUM(t1.amount * t1.rate) / SUM(t1.amount) as weighted_avg_rate_autobid, ROUND(AVG(t1.amount) / 100, 2) avg_amount_autobid
                      FROM (
                        SELECT id_project, amount, rate
                        FROM bids
                        WHERE status = ' . \bids::STATUS_BID_ACCEPTED . '
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

    public function getPreviousProjectsWithSameSiren($sSiren, $sAdded)
    {
        $sQuery = 'SELECT projects.id_project FROM projects INNER JOIN companies ON projects.id_company = companies.id_company WHERE companies.siren = ' . $sSiren . ' AND projects.added <= "' . $sAdded . '"';

        $aProjects = array();
        $rResult   = $this->bdd->query($sQuery);
        while ($aRecord = $this->bdd->fetch_assoc($rResult)) {
            $aProjects[] = $aRecord;
        }
        return $aProjects;
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
                  WHEN '0000-00-00' then ''
                  ELSE p.date_publication
                END AS 'Date_Publication',
                CASE p.date_retrait
                  WHEN '0000-00-00' then ''
                  ELSE p.date_retrait
                END AS 'Date_Retrait',
                CASE p.added
                  WHEN '0000-00-00 00:00:00' then ''
                  ELSE p.added
                END AS 'Date_Ajout',
                CASE p.updated
                  WHEN '0000-00-00 00:00:00' then ''
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
                REPLACE(co.adresse1,',','') as 'Adresse1',
                REPLACE(co.adresse2,',','') as 'Adresse2',
                REPLACE(co.zip,',','') AS 'CP',
                REPLACE(co.city,',','') AS 'Ville',
                co.id_pays AS 'IdPays',
                REPLACE(co.phone,'\t','') AS 'Telephone',
                co.status_client AS 'Status_Client',
                CASE co.added
                  WHEN '0000-00-00 00:00:00' then ''
                  ELSE co.added
                END AS 'Date_ajout',
                CASE co.updated
                  WHEN '0000-00-00 00:00:00' then ''
                  ELSE co.updated
                END AS 'Date_Mise_A_Jour',
                co.id_client_owner AS 'IDClient'
            FROM projects p
            LEFT JOIN companies co ON (p.id_company = co.id_company)
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
                       date_publication_full,
                       ROUND(TIMESTAMPDIFF(SECOND, date_publication_full, date_funded) / 60) AS DurationFunding -- minutes
                     FROM projects
                     WHERE date_funded != "0000-00-00" AND date_retrait > :date
                    ) AS t ';

        $statement = $this->bdd->executeQuery($query, ['date' => $startingDate->format('Y-m-d')], ['date' => \PDO::PARAM_STR]);
        $dateIntervalInformation  = $statement->fetch(\PDO::FETCH_ASSOC);

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
                WHERE p.status >= ' . \projects_status::REMBOURSEMENT . '
                ORDER BY p.date_fin DESC
                LIMIT :limit
            ) AS last_loans';

        $statement = $this->bdd->executeQuery($query, ['limit' => $limit], ['limit' => \PDO::PARAM_INT]);

        return $statement->fetchColumn(0);
    }

    public function getAverageNumberOfLendersForProject()
    {
        $sQuery = 'SELECT ROUND(AVG(t.lenderCount), 0) FROM (SELECT id_project, COUNT(DISTINCT id_lender) AS lenderCount FROM `loans` WHERE status = 0 GROUP BY id_project) AS t ';
        $oStatement = $this->bdd->executeQuery($sQuery);

        return $oStatement->fetchColumn(0);
    }

    public function getAverageAmount()
    {
        $query = 'SELECT ROUND(AVG(amount), 0)
                    FROM projects
                    WHERE status >= ' . \projects_status::REMBOURSEMENT;
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

    public function countProjectsByRegion()
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
                    FROM (SELECT
                        clients.id_client,
                        companies.zip AS cp
                      FROM projects
                        INNER JOIN companies ON projects.id_company = companies.id_company
                        INNER JOIN clients ON clients.id_client = companies.id_client_owner
                        INNER JOIN projects_status_history ON projects.id_project = projects_status_history.id_project AND projects_status_history.id_project_status = 4) AS client_base
                    GROUP BY insee_region_code';

        $statement = $this->bdd->executeQuery($query);
        $regionsCount  = array();
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

        $statement = $this->bdd->executeQuery($query);
        $categoriesCount  = [];
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
        $sql = '
        SELECT
          companies.sector,
          count(companies.sector) AS count,
          sum(l.amount) / 100 AS loaned_amount,
          avg(l.rate) AS average_rate
        FROM companies
          INNER JOIN projects ON projects.id_company = companies.id_company
          INNER JOIN projects_status_history
            ON projects.id_project = projects_status_history.id_project AND projects_status_history.id_project_status = 4
          INNER JOIN loans l ON l.id_project = projects.id_project
        WHERE l.id_lender = :id_lender AND l.status = 0
        GROUP BY companies.sector
        ';

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
                    WHERE ROUND(TIMESTAMPDIFF(SECOND, date_publication_full, date_funded)/120) <= 24
                    AND date_funded >= :startDate AND status >= ' . \projects_status::FUNDE;

        $statement = $this->bdd->executeQuery($query, $bind, $type);

        return $statement->fetchColumn(0);
    }

    public function countProjectsFundedSince(\DateTime $startDate)
    {
        $bind = ['startDate' => $startDate->format('Y-m-d h:i:s')];
        $type = ['startDate' => \PDO::PARAM_STR];

        $query = 'SELECT count(projects.id_project)
                    FROM projects
                    WHERE date_funded >= :startDate AND status >=' . \projects_status::FUNDE;

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
                    ORDER BY SECOND(TIMEDIFF(date_funded, date_publication_full)) ASC, amount DESC
                    LIMIT 1';

        $statement = $this->bdd->executeQuery($query);
        return $statement->fetchColumn(0);
    }

    public function countFundedProjectsByCohort()
    {
        $query = 'SELECT COUNT(DISTINCT id_project) AS amount,
                    (
                        SELECT
                          CASE LEFT(projects_status_history.added, 4)
                            WHEN 2013 THEN "2013-2014"
                            WHEN 2014 THEN "2013-2014"
                            ELSE LEFT(projects_status_history.added, 4)
                          END AS date_range
                        FROM projects_status_history
                        INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                        WHERE  projects_status.status = '. \projects_status::REMBOURSEMENT .'
                          AND projects.id_project = projects_status_history.id_project
                        ORDER BY id_project_status_history ASC LIMIT 1
                      ) AS cohort
                       FROM projects
                    WHERE projects.status >= ' . \projects_status::REMBOURSEMENT . '
                    GROUP BY cohort';

        $statement = $this->bdd->executeQuery($query);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
