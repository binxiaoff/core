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

use Unilend\librairies\Cache;

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
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
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

    public function selectProjectsByStatus($status, $where = '', $order = '', $start = '', $nb = '')
    {
        $sWhereClause = 'projects_status.status IN (' . $status . ')';

        if ('' !== trim($where)) {
            $sWhereClause .= ' ' . $where . ' ';
        }

        if ($order == '') {
            $order = 'lestatut ASC, p.date_retrait DESC';
        }

        $sql = '
          SELECT p.*,
              projects_status.status,
              CASE WHEN projects_status.status = ' . \projects_status::EN_FUNDING . '
                THEN "1"
                ELSE "2"
              END AS lestatut
            FROM projects p
            INNER JOIN projects_last_status_history USING (id_project)
            INNER JOIN projects_status_history USING (id_project_status_history)
            INNER JOIN projects_status USING (id_project_status)
            WHERE ' . $sWhereClause . '
            ORDER BY ' . $order .
            ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $result        = array();
        $resultat      = $this->bdd->query($sql);
        $positionStart = $start + $nb;

        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
            // on récupere la derniere position pour demarrer une autre requete au meme niveau
            $result[0]['positionStart'] = $positionStart;
        }
        return $result;
    }

    // version slim
    public function selectProjectsByStatusSlim($status, $where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' ' . $where . ' ';
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = '
            SELECT
            p.id_project,
            p.date_publication_full
            FROM projects p
            WHERE (SELECT ps.status FROM projects_status ps LEFT JOIN projects_status_history psh ON (ps.id_project_status = psh.id_project_status) WHERE psh.id_project = p.id_project ORDER BY psh.added DESC LIMIT 1)  IN (' . $status . ')' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();

        $positionStart = $start + $nb;

        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function countSelectProjectsByStatus($status, $where = '')
    {
        $sql = '
            SELECT COUNT(*)
            FROM projects p
            LEFT JOIN projects_last_status_history ON p.id_project = projects_last_status_history.id_project
            LEFT JOIN projects_status_history ON projects_last_status_history.id_project_status_history = projects_status_history.id_project_status_history
            LEFT JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
            WHERE projects_status.status IN (' . $status . ')' . $where;

        return current($this->bdd->fetch_assoc($this->bdd->query($sql)));
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

    public function positionProject($id_project, $status = '', $order = '')
    {
        if ($status == '') {
            $status = implode(', ', array(\projects_status::EN_FUNDING, \projects_status::FUNDE, \projects_status::REMBOURSEMENT));
        }

        // On recupere les en funding et les fundé
        $lProjets = $this->selectProjectsByStatus($status, ' AND p.display = 0 and p.status = 0', ($order != '' ? $order : 'p.date_publication DESC'));

        foreach ($lProjets as $k => $p) {
            if ($p['id_project'] == $id_project) {
                $previous = $lProjets[$k - 1]['slug'];
                $next     = $lProjets[$k + 1]['slug'];
                break;
            }
        }

        return array('previous' => $previous, 'next' => $next);
    }

    // liste les projets favoris dont la date de retrait est dans j-2
    public function getDerniersFav($id_client)
    {
        $sql = 'SELECT * FROM `favoris` WHERE id_client = ' . $id_client;

        $resultat = $this->bdd->query($sql);
        $lesfav   = '';
        $i        = 0;
        while ($f = $this->bdd->fetch_array($resultat)) {
            $lesfav .= ($i > 0 ? ',' : '') . $f['id_project'];
            $i++;
        }

        $sql = 'SELECT *,DATEDIFF(date_retrait,CURRENT_DATE) as datediff FROM projects WHERE id_project IN (' . $lesfav . ') AND DATEDIFF(date_retrait,CURRENT_DATE)<=2 AND DATEDIFF(date_retrait,CURRENT_DATE)>=0 AND date_fin = "0000-00-00 00:00:00" ORDER BY datediff';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
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
                    psh.added DESC LIMIT 1) IN (' . $statusString . ')';

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
                        ORDER BY psh.added DESC LIMIT 1
                    ) IN (' . $statusString . ')';
        $result = $this->bdd->query($sql);
        $record = $this->bdd->result($result);

        return $record;
    }

    public function getProjectsStatusAndCount($sListStatus, $sTabOrderProject, $iStart, $iLimit)
    {
        $oCache    = Cache::getInstance();
        $sKey      = $oCache->makeKey(Cache::LIST_PROJECTS, $sListStatus);
        $aElements = $oCache->get($sKey);

        if (false === $aElements) {
            $alProjetsFunding = $this->selectProjectsByStatus($sListStatus, ' AND p.status = 0 AND p.display = 0', $sTabOrderProject, $iStart, $iLimit);
            $anbProjects      = $this->countSelectProjectsByStatus($sListStatus . ', ' . \projects_status::PRET_REFUSE, ' AND p.status = 0 AND p.display = 0');
            $aElements = array(
                'lProjectsFunding' => $alProjetsFunding,
                'nbProjects'       => $anbProjects
            );

            $oCache->set($sKey, $aElements);
        }

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
            WHERE p.stop_relances = 0
                AND ps.status = ' . $iStatus . '
                AND DATE_SUB(CURDATE(), INTERVAL ' . $iDaysInterval . ' DAY) = DATE_FORMAT(psh.added, "%Y-%m-%d")
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

    public function calculateAvgInterestRate(bids $oBids, loans $oLoans, $iProjectId = null, $iProjectStatus = null)
    {
        if ($iProjectId === null) {
            $iProjectId = $this->id_project;
        }

        if ($iProjectStatus === null) {
            $oProject_status = new \projects_status($this->bdd);
            $oProject_status->getLastStatut($iProjectId);
            $iProjectStatus = $oProject_status->status;
        }

        $iUpperValue = 0;
        $iLowerValue = 0;

        switch ((int) $iProjectStatus) {
            case \projects_status::FUNDE:
            case \projects_status::REMBOURSEMENT:
            case \projects_status::REMBOURSE:
            case \projects_status::PROBLEME:
            case \projects_status::RECOUVREMENT:
            case \projects_status::REMBOURSEMENT_ANTICIPE:
                foreach ($oLoans->select('id_project = ' . $iProjectId) as $aLoan) {
                    $iUpperValue += ($aLoan['rate'] * ($aLoan['amount']));
                    $iLowerValue += ($aLoan['amount']);
                }
                break;
            case \projects_status::EN_FUNDING:
                foreach ($oBids->select('id_project = ' . $iProjectId . ' AND status = 0') as $aBid) {
                    $iUpperValue += ($aBid['rate'] * ($aBid['amount']));
                    $iLowerValue += ($aBid['amount']);
                }
                break;
            case \projects_status::FUNDING_KO:
            foreach ($oBids->select('id_project = ' . $iProjectId) as $aBid) {
                $iUpperValue += ($aBid['rate'] * ($aBid['amount']));
                $iLowerValue += ($aBid['amount']);
            }
            break;
            case \projects_status::PRET_REFUSE:
            case \projects_status::DEFAUT:
            foreach ($oBids->select('id_project = ' . $iProjectId . ' AND status = 1') as $aBid) {
                $iUpperValue += ($aBid['rate'] * ($aBid['amount']));
                $iLowerValue += ($aBid['amount']);
            }
            break;
            default:
                trigger_error('Unknown project status. Could not calculate amounts', E_USER_WARNING);
                break;
        }

        return $iUpperValue > 0 && $iLowerValue > 0 ? round(($iUpperValue / $iLowerValue), 2) : 0;
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
}
