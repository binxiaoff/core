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
        while ($record = $this->bdd->fetch_array($resultat)) {
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
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function searchDossiers($date1 = '', $date2 = '', $montant = '', $duree = '', $status = '', $analyste = '', $siren = '', $id = '', $raison_sociale = '', $iAdvisorId = '', $iSalesPersonId = '', $start = '', $nb = '')
    {
        $where = '';

        if (false === empty($date1)) {
            $where .= ' AND p.added >= "' . $date1 . ' 00:00:00"';
        }
        if (false === empty($date2)) {
            $where .= ' AND p.added <= "' . $date2 . ' 23:59:59"';
        }
        if (false === empty($montant)) {
            $where .= ' AND p.amount = "' . $montant . '"';
        }
        if (false === empty($duree)) {
            $where .= ' AND p.period = "' . $duree . '"';
        }
        if (false === empty($status)) {
            $where .= ' AND ps.status IN (' . $status . ')';
        }
        if (false === empty($analyste)) {
            $where .= ' AND p.id_analyste = "' . $analyste . '"';
        }
        if (false === empty($siren)) {
            $where .= ' AND co.siren LIKE "%' . $siren . '%"';
        }
        if (false === empty($id)) {
            $where .= ' AND p.id_project = "' . $id . '"';
        }
        if (false === empty($raison_sociale)) {
            $where .= ' AND co.name LIKE "%' . $raison_sociale . '%"';
        }
        if (false === empty($iAdvisorId)) {
            $where .= ' AND p.id_prescripteur = ' . $iAdvisorId;
        }
        if (false === empty($iSalesPersonId)) {
            $where .= ' AND p.id_commercial = ' . $iSalesPersonId;
        }

        $sSqlCount = 'SELECT
                            COUNT(*)
                        FROM
                            projects p
                            LEFT JOIN companies co ON p.id_company = co.id_company
                            LEFT JOIN projects_last_status_history plsh on plsh.id_project = p.id_project
                            LEFT JOIN projects_status_history psh on psh.id_project_status_history = plsh.id_project_status_history
                            LEFT JOIN projects_status ps on ps.id_project_status = psh.id_project_status
                        WHERE
                            (ps.label != "" or ps.label is not null)
                        ' . $where;

        $rResult        = $this->bdd->query($sSqlCount);
        $iCountProjects = (int) $this->bdd->result($rResult, 0, 0);

        $sql = 'SELECT
                    p.*,
                    p.status as statusProject,
                    co.siren,
                    co.name,
                    ps.label,
                    ps.status
                FROM
                    projects p
                    LEFT JOIN companies co ON (p.id_company = co.id_company)
                    LEFT JOIN projects_last_status_history plsh on plsh.id_project = p.id_project
                    LEFT JOIN projects_status_history psh on psh.id_project_status_history = plsh.id_project_status_history
                    LEFT JOIN projects_status ps on ps.id_project_status = psh.id_project_status
                WHERE
                    (ps.label != "" or ps.label is not null)
                ' . $where . '
                ORDER BY p.added DESC
                ' . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);

        $result    = array();
        $result[0] = $iCountProjects;

        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    /**
     * @param string $status
     * @param string $where
     * @param string $order
     * @param array $aRateRange
     * @param string $start
     * @param string $nb
     * @param bool $bUseCache
     * @return array
     */
    public function selectProjectsByStatus($status, $where = '', $order = '', $aRateRange = array(), $start = '', $nb = '', $bUseCache = true)
    {
        $aBind = array('iFunding' => \projects_status::EN_FUNDING, 'status' => explode(',', $status));

        if ($bUseCache) {
            $QCProfile = new \Doctrine\DBAL\Cache\QueryCacheProfile(60, md5(__METHOD__));
        } else {
            $QCProfile = null;
        }
        $sWhereClause = 'projects_status.status IN (:status)';

        if ('' !== trim($where)) {
            $sWhereClause .= ' ' . $where . ' ';
        }

        if ($order == '') {
            $order = ' lestatut ASC, p.date_retrait DESC ';
        }
        $sql = 'SELECT p.*,
              projects_status.status,
              DATEDIFF(p.date_retrait_full, NOW()) AS daysLeft,
              CASE WHEN projects_status.status = :iFunding
                THEN "1"
                ELSE "2"
              END AS lestatut ';

        if (2 === count($aRateRange)) {
            $sql .= ', ROUND(SUM(b.amount * b.rate) / SUM(b.amount), 1) AS avg_rate';
        }
        $sql .= " FROM projects p
            INNER JOIN projects_last_status_history USING (id_project)
            INNER JOIN projects_status_history USING (id_project_status_history)
            INNER JOIN projects_status USING (id_project_status) ";

        if (2 === count($aRateRange)) {
            $sql .= "LEFT JOIN bids b ON b.id_project = p.id_project AND b.status IN (0 ,1) ";
        }
        $sql .= 'WHERE ' . $sWhereClause;

        if (2 === count($aRateRange)) {
            $aBind['minRateRange'] = $aRateRange[0];
            $aBind['maxRateRange'] = $aRateRange[1];
            $sql .= ' GROUP BY p.id_project';
            $sql .= ' HAVING avg_rate >= :minRateRange AND';
            if ($aRateRange[1] == 10) {
                $sql .= ' avg_rate <= :maxRateRange';
            } else {
                $sql .= ' avg_rate < :maxRateRange';
            }
        }
        $sql .= ' ORDER BY ' . $order;

        if (is_numeric($nb)) {
            $aBind['number'] = $nb;
            $sql .= ' LIMIT :number ';

            if (is_numeric($start)) {
                $aBind['start'] = $start;
                $sql .= ' OFFSET :start';
            }
        }

        try {
            $aTypes = array(
                'status'       => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                'iFunding'     => \PDO::PARAM_INT,
                'minRateRange' => \PDO::PARAM_INT,
                'maxRateRange' => \PDO::PARAM_INT,
                'number'       => \PDO::PARAM_INT,
                'start'        => \PDO::PARAM_INT
            );
            $result = $this->bdd->executeQuery($sql, $aBind, $aTypes, $QCProfile)->fetchAll(\PDO::FETCH_ASSOC);
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
            FROM projects p
            LEFT JOIN projects_last_status_history ON p.id_project = projects_last_status_history.id_project
            LEFT JOIN projects_status_history ON projects_last_status_history.id_project_status_history = projects_status_history.id_project_status_history
            LEFT JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
            WHERE projects_status.status IN (:status)' . $where;

        try {
            return $this->bdd->executeQuery($sQuery, $aBind, $aType, $oQCProfile)->fetchColumn(0);
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
            INNER JOIN projects_last_status_history plsh ON (p.id_project = plsh.id_project)
            INNER JOIN projects_status_history psh ON (plsh.id_project_status_history = psh.id_project_status_history)
            INNER JOIN projects_status ps ON (psh.id_project_status = ps.id_project_status)
            LEFT JOIN companies co ON (p.id_company = co.id_company)
            LEFT JOIN clients c ON (co.id_client_owner = c.id_client)
            WHERE ps.status IN (' . implode(', ', $aStatus) . ')
            ' . $where . '
            ORDER BY p.added DESC
            ' . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''))
        );

        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function positionProject($id_project, $status, $order)
    {
        $aProjects = $this->selectProjectsByStatus($status, ' AND p.display = 0 and p.status = 0', $order);
        $previous = '';
        $next = '';

        foreach ($aProjects as $k => $p) {
            if ($p['id_project'] == $id_project) {
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
            while ($f = $this->bdd->fetch_array($resultat)) {
                $lesfav .= ($i > 0 ? ',' : '') . $f['id_project'];
                $i++;
            }

            $sql = 'SELECT *,DATEDIFF(date_retrait,CURRENT_DATE) as datediff FROM projects WHERE id_project IN (' . $lesfav . ') AND DATEDIFF(date_retrait,CURRENT_DATE)<=2 AND DATEDIFF(date_retrait,CURRENT_DATE)>=0 AND date_fin = "0000-00-00 00:00:00" ORDER BY datediff';

            $resultat = $this->bdd->query($sql);

            while ($record = $this->bdd->fetch_array($resultat)) {
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

        $sql = 'SELECT COUNT(DISTINCT l.id_project)
                FROM loans l
                INNER JOIN projects p ON l.id_project = p.id_project
                WHERE id_lender = ' . $lender . '
                AND l.status = 0
                AND
                    ( SELECT ps.status FROM projects_status ps
                    LEFT JOIN projects_status_history psh ON ( ps.id_project_status = psh.id_project_status )
                    WHERE psh.id_project = p.id_project ORDER BY
                    psh.id_project_status_history DESC LIMIT 1) IN (' . $statusString . ')';

        $result = $this->bdd->query($sql);
        $record = $this->bdd->result($result);

        return $record;
    }

    public function countProjectsSinceLendersubscription($client, $status)
    {
        if (is_array($status)) {
            $statusString = implode(",", $status);
        }

        $sql    = 'SELECT COUNT(*)
                FROM projects p
                WHERE date_publication_full >= (SELECT added FROM clients WHERE id_client = ' . $client . ')
                    AND (
                        SELECT ps.status
                        FROM projects_status ps
                        LEFT JOIN projects_status_history psh ON (ps.id_project_status = psh.id_project_status)
                        WHERE psh.id_project = p.id_project
                        ORDER BY psh.id_project_status_history DESC LIMIT 1
                    ) IN (' . $statusString . ')';
        $result = $this->bdd->query($sql);
        $record = $this->bdd->result($result);

        return $record;
    }

    public function getProjectsStatusAndCount($sListStatus, $sTabOrderProject, $iStart, $iLimit)
    {
        $aProjects   = $this->selectProjectsByStatus($sListStatus, ' AND p.status = 0 AND p.display = 0', $sTabOrderProject, array(), $iStart, $iLimit);
        $anbProjects = $this->countSelectProjectsByStatus($sListStatus . ', ' . \projects_status::PRET_REFUSE, ' AND p.status = 0 AND p.display = 0', true);
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
        while ($record = $this->bdd->fetch_array($result)) {
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
            INNER JOIN projects_last_status_history plsh ON plsh.id_project = p.id_project
            INNER JOIN projects_status_history psh ON psh.id_project_status_history = plsh.id_project_status_history
            INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
            WHERE ps.status = ' . $iStatus . '
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
            SELECT p.*
            FROM projects p
            INNER JOIN projects_last_status_history plsh ON plsh.id_project = p.id_project
            INNER JOIN projects_status_history psh ON psh.id_project_status_history = plsh.id_project_status_history
            INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
            WHERE ps.status = ' . \projects_status::COMPLETUDE_ETAPE_3 . '
                AND DATE_SUB(NOW(), INTERVAL 1 HOUR) > p.added
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
            SELECT p.*
            FROM projects p
            INNER JOIN projects_last_status_history plsh ON plsh.id_project = p.id_project
            INNER JOIN projects_status_history psh ON psh.id_project_status_history = plsh.id_project_status_history
            INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
            WHERE ps.status IN (' . implode(', ', array(\projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE)) . ')'
        );

        if ($this->bdd->num_rows($rResult) > 0) {
            while ($aResult = $this->bdd->fetch_assoc($rResult)) {
                $aProjects[] = (int) $aResult['id_project'];
            }
        }

        return $aProjects;
    }

    /**
     * @param int|null $iProjectId
     * @param int|null $iProjectStatus
     * @return float
     */
    public function getAverageInterestRate($iProjectId = null, $iProjectStatus = null)
    {
        if ($iProjectId === null) {
            $iProjectId = $this->id_project;
        }

        if ($iProjectStatus === null) {
            $oProject_status = new \projects_status($this->bdd);
            $oProject_status->getLastStatut($iProjectId);
            $iProjectStatus = (int) $oProject_status->status;
        }

        switch ($iProjectStatus) {
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
                    WHERE id_project = ' . $iProjectId
                );
                return round($this->bdd->result($rResult, 0, 0), 2);
            case \projects_status::PRET_REFUSE:
            case \projects_status::EN_FUNDING:
            case \projects_status::AUTO_BID_PLACED:
            case \projects_status::A_FUNDER:
                $rResult = $this->bdd->query('
                    SELECT SUM(amount * rate) / SUM(amount) AS avg_rate
                    FROM bids
                    WHERE id_project = ' . $iProjectId . '
                    AND status IN (0, 1)'
                );
                return round($this->bdd->result($rResult, 0, 0), 2);
            case \projects_status::FUNDING_KO:
                $rResult = $this->bdd->query('
                    SELECT SUM(amount * rate) / SUM(amount) AS avg_rate
                    FROM bids
                    WHERE id_project = ' . $iProjectId
                );
                return round($this->bdd->result($rResult, 0, 0), 2);
            default:
                trigger_error('Unknown project status : ' . $iProjectStatus . ' Could not calculate amounts', E_USER_WARNING);
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
            INNER JOIN projects_last_status_history plsh ON plsh.id_project = p.id_project
            INNER JOIN projects_status_history psh ON psh.id_project_status_history = plsh.id_project_status_history
            INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
            INNER JOIN (SELECT id_project, MIN(date_echeance_emprunteur) AS date_echeance_emprunteur FROM echeanciers_emprunteur WHERE status_emprunteur = 0 GROUP BY id_project) min_unpaid ON min_unpaid.id_project = p.id_project
            INNER JOIN echeanciers_emprunteur prev ON prev.id_project = p.id_project AND prev.date_echeance_emprunteur = min_unpaid.date_echeance_emprunteur
            INNER JOIN echeanciers_emprunteur next ON next.id_project = p.id_project AND next.ordre = prev.ordre + 1 AND next.status_emprunteur = 0
            WHERE ps.status = ' . \projects_status::PROBLEME_J_X . '
                AND DATE(next.date_echeance_emprunteur) = DATE(ADDDATE(NOW(), INTERVAL 7 DAY))'
        );
        while ($aRecord = $this->bdd->fetch_assoc($rResult)) {
            $aProjects[] = $aRecord;
        }
        return $aProjects;
    }

    public function getAvgRate($sRisk = null, $sDurationMin = null, $sDurationMax = null)
    {
        $sWhereRisk        = '';
        $sWhereDurationMin = '';
        $sWhereDurationMax = '';
        $aBind             = array();
        $aType             = array();

        if (null !== $sRisk) {
            $sWhereRisk    = ' AND p.risk = :risk ';
            $aBind['risk'] = $sRisk;
            $aType['risk'] = \PDO::PARAM_STR;
        }

        if (null !== $sDurationMin) {
            $aBind['p_min']    = $sDurationMin;
            $aType['p_min']    = \PDO::PARAM_INT;
            $sWhereDurationMin = ' AND p.period >= :p_min';
        }

        if (null !== $sDurationMax) {
            $aBind['p_max']    = $sDurationMax;
            $aType['p_max']    = \PDO::PARAM_INT;
            $sWhereDurationMax = ' AND p.period <= :p_max';
        }
        $sQuery = 'SELECT avg(t1.weighted_rate_by_project)
                        FROM (
                          SELECT SUM(t.amount * t.rate) / SUM(t.amount) as weighted_rate_by_project
                          FROM (
                            SELECT l.id_loan, l.amount, l.rate, l.added, p.id_project, p.period
                            FROM loans l
                              INNER JOIN projects p ON p.id_project = l.id_project
                              INNER JOIN projects_last_status_history plsh ON plsh.id_project = p.id_project
                              INNER JOIN projects_status_history psh ON psh.id_project_status_history = plsh.id_project_status_history
                              INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
                            WHERE ps.status >= ' . \projects_status::FUNDE . '
                            AND ps.status != ' . \projects_status::FUNDING_KO . $sWhereRisk . $sWhereDurationMin . $sWhereDurationMax . '
                          ) t
                          GROUP BY t.id_project
                        ) t1
                        ';
        try {
            return $this->bdd->executeQuery($sQuery, $aBind, $aType, new \Doctrine\DBAL\Cache\QueryCacheProfile(1800, md5(__METHOD__)))->fetchColumn(0);
        } catch (\Doctrine\DBAL\DBALException $ex) {
            return false;
        }
    }

    public function getAutoBidProjectStatistic(\DateTime $oDateFrom, \DateTime $oDateTo)
    {
        $sQuery = 'SELECT pg.id_project, pg.period, pg.risk, pg.date_fin, pg.status_lable, pg.amount_total, pg.weighted_avg_rate, pg.avg_amount,
                      pb.bids_nb, pa.amount_total_autobid, pa.avg_amount_autobid, pa.weighted_avg_rate_autobid
                    FROM (
                       SELECT t.id_project, t.period, t.risk, t.date_fin, t.status_lable, ROUND(SUM(t.amount) / 100, 2) AS amount_total, SUM(t.amount * t.rate) / SUM(t.amount) as weighted_avg_rate, ROUND(AVG(t.amount) / 100, 2) as avg_amount
                       FROM (
                          SELECT l.id_loan, l.amount, l.rate, p.date_fin, p.id_project, p.period, p.risk, ps.label as status_lable
                          FROM loans l
                          INNER JOIN projects p ON p.id_project = l.id_project
                          INNER JOIN projects_last_status_history plsh ON plsh.id_project = p.id_project
                          INNER JOIN projects_status_history psh ON psh.id_project_status_history = plsh.id_project_status_history
                          INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
                          WHERE l.status = 0 AND ps.status > ' . \projects_status::EN_FUNDING . '
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
        while ($aRecord = $this->bdd->fetch_array($rResult)) {
            $aProjects[] = $aRecord;
        }
        return $aProjects;
    }

    public function getProjectsSalesForce()
    {
        $sQuery = "SELECT
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
                    FROM
                        projects p
                    LEFT JOIN
                        companies co ON (p.id_company = co.id_company)
                    LEFT JOIN
                        clients cl ON (cl.id_client = co.id_client_owner)
                    LEFT JOIN
                        projects_notes pn ON (p.id_project = pn.id_project)
                    LEFT JOIN
                      projects_last_status_history pslh ON pslh.id_project = p.id_project
                    LEFT JOIN
                      projects_status_history psh ON psh.id_project_status_history = pslh.id_project_status_history
                    LEFT JOIN
                      projects_status ps ON ps.id_project_status = psh.id_project_status";

        return $this->bdd->executeQuery($sQuery);
    }

    public function getAverageFundingTime()
    {
        $sQuery = 'SELECT
                      FLOOR(HOUR(AVG(t.DurationFunding)) / 24) AS "days",
                      MOD(HOUR(AVG(t.DurationFunding)), 24) AS "hours",
                      MINUTE(AVG(t.DurationFunding)) AS "minutes",
                      AVG(t.DurationFunding) AS "unixtime"
                      FROM
                      (SELECT
                        p.id_project,
                        TIMEDIFF(CASE WHEN p.date_funded > "0000-00-00 00:00:00" THEN p.date_funded ELSE DATE_FORMAT(MIN(b.updated),"%Y-%m-%d %H:%i") END, p.date_publication_full) AS DurationFunding
                    FROM
                      `bids` b
                    INNER JOIN projects p ON (b.id_project = p.id_project)
                    INNER JOIN projects_status_history psh ON p.id_project = psh.id_project
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                    WHERE b.status = ' . \bids::STATUS_BID_REJECTED . ' AND ps.status = ' . \projects_status::FUNDE . ' AND p.date_retrait > "2014-04-01" ) AS t';


        $oStatement = $this->bdd->executeQuery($sQuery);
        $aDateIntervalInformation  = $oStatement->fetch(\PDO::FETCH_ASSOC);

        return $aDateIntervalInformation;
    }

    public function getGlobalAverageRateOfFundedProjects($iLimit)
    {
        $aBind = array('projectStatus' => \projects_status::REMBOURSEMENT, 'limit' => $iLimit);
        $aType = array('projectStatus' => \PDO::PARAM_INT, 'limit' => \PDO::PARAM_INT);

        $sQuery = 'SELECT
                      SUM(amount * rate) / SUM(amount)
                    FROM
                        (SELECT
                        loans.rate,
                        loans.amount
                      FROM projects
                      INNER JOIN loans ON projects.id_project = loans.id_project
                      INNER JOIN projects_status_history psh ON loans.id_project = psh.id_project
                      INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                      WHERE
                        ps.status = :projectStatus
                      ORDER BY
                        projects.date_fin
                      DESC
                    LIMIT :limit
                    ) AS last_loans';

        $oStatement = $this->bdd->executeQuery($sQuery, $aBind, $aType);

        return $oStatement->fetchColumn(0);
    }

    public function getAverageNumberOfLendersForProject()
    {
        $sQuery = 'SELECT ROUND(AVG(t.lenderCount), 0) FROM (SELECT id_project, COUNT(DISTINCT id_lender) AS lenderCount FROM `loans` GROUP BY id_project) AS t ';
        $oStatement = $this->bdd->executeQuery($sQuery);

        return $oStatement->fetchColumn(0);
    }

    public function getAverageAmount()
    {
        $sQuery = 'SELECT ROUND(AVG(amount), 0)
                    FROM `projects`
                    INNER JOIN projects_status_history ON projects.id_project = projects_status_history.id_project
                    INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                    WHERE projects_status.status = ' . \projects_status::FUNDE;
        $oStatement = $this->bdd->executeQuery($sQuery);

        return $oStatement->fetchColumn(0);
    }

}
