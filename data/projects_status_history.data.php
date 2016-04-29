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

class projects_status_history extends projects_status_history_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::projects_status_history($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM projects_status_history' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT COUNT(*) FROM projects_status_history ' . $where;

        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_project_status_history')
    {
        $sql    = 'SELECT * FROM projects_status_history WHERE ' . $field . ' = "' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function addStatus($sUserId, $iStatus, $iProjectId, $sReminderId = 0, $sComment = '')
    {
        $rQuery = $this->bdd->query('SELECT id_project_status FROM projects_status WHERE status = ' . $iStatus);

        if (in_array($iStatus, array(\projects_status::REJETE, \projects_status::REJET_ANALYSTE, \projects_status::REJET_COMITE))) {
            $oCompanies = new \companies($this->bdd);
            $oProjects  = new \projects($this->bdd);

            $oProjects->get($iProjectId);
            $oCompanies->get($oProjects->id_company);

            $oProjectCreationDate = new \datetime($oProjects->added);
            $aCompanies           = $oCompanies->select('siren = ' . $oCompanies->siren);

            foreach ($aCompanies as $aCompany) {
                $aProjects = $oCompanies->getProjectsForCompany($aCompany['id_company']);

                foreach ($aProjects as $aProject) {
                    $oOldProjectCreationDate = new \datetime($aProject['added']);
                    if ($oOldProjectCreationDate->format('Y-m-d H:i:s') < $oProjectCreationDate->format('Y-m-d H:i:s')) {
                        $oProjects->get($aProject['id_project'], 'id_project');
                        $oProjects->stop_relances = '1';
                        $oProjects->update();
                    }
                }
            }
        }

        $this->id_project        = $iProjectId;
        $this->id_project_status = (int) $this->bdd->result($rQuery, 0, 0);
        $this->id_user           = $sUserId;
        $this->numero_relance    = $sReminderId;
        $this->content           = $sComment;
        $this->create();

        $this->statusUpdateTrigger($iStatus, $iProjectId);
    }

    private function statusUpdateTrigger($iStatus, $iProjectId)
    {
        switch ($iStatus) {
            case \projects_status::A_TRAITER:
                $oSettings = new \settings($this->bdd);
                $oSettings->get('Adresse notification inscription emprunteur', 'type');

                $this->sendNotification('notification-depot-de-dossier', $iProjectId, trim($oSettings->value));
                break;
            case \projects_status::ATTENTE_ANALYSTE:
                $this->sendNotification('notification-projet-a-traiter', $iProjectId, \email::EMAIL_ADDRESS_ANALYSTS);
                break;
        }
    }

    private function sendNotification($sNotificationType, $iProjectId, $sRecipient)
    {
        /* @var array $config */
        include __DIR__ . '/../config.php';

        // @todo intl
        $oMailsText = new \mails_text($this->bdd);
        $oMailsText->get($sNotificationType, 'lang = "fr" AND type');

        $oProjects = new \projects($this->bdd);
        $oProjects->get($iProjectId, 'id_project');

        $oCompanies = new \companies($this->bdd);
        $oCompanies->get($oProjects->id_company, 'id_company');

        $aReplacements = array(
            '[SURL]'           => $config['static_url'][ENVIRONMENT],
            '[ID_PROJET]'      => $iProjectId,
            '[MONTANT]'        => $oProjects->amount,
            '[RAISON_SOCIALE]' => utf8_decode($oCompanies->name),
            '[LIEN_REPRISE]'   => $config['url'][ENVIRONMENT]['admin'] . '/depot_de_dossier/reprise/' . $oProjects->hash,
            '[LIEN_BO_PROJET]' => $config['url'][ENVIRONMENT]['admin'] . '/dossiers/edit/' . $iProjectId
        );

        $oEmail = new \email();
        $oEmail->setFrom($oMailsText->exp_email, utf8_decode($oMailsText->exp_name));
        $oEmail->setSubject(utf8_decode($oMailsText->subject));
        $oEmail->setHTMLBody(str_replace(array_keys($aReplacements), array_values($aReplacements), $oMailsText->content));
        $oEmail->addRecipient($sRecipient);

        Mailer::send($oEmail, new \mails_filer($this->bdd), $oMailsText->id_textemail);
    }

    public function getBeforeLastStatus($iProjectId)
    {
        $result = $this->select('id_project=' . $iProjectId, 'added DESC', 1, 1);

        if (isset($result[0]) && false === empty($result[0])) {
            return $result[0]['id_project_status'];
        }

        return false;
    }

    public function addStatusAndReturnID($id_user, $status, $id_project)
    {
        $result                  = $this->bdd->query('SELECT id_project_status FROM `projects_status` WHERE status = ' . $status);
        $this->id_project        = $id_project;
        $this->id_project_status = (int) $this->bdd->result($result, 0, 0);
        $this->id_user           = $id_user;

        return $this->create();
    }

    public function getHistoryDetails($id_project)
    {
        $sql = '
            SELECT
                ps.status,
                psh.added AS added,
                IFNULL(pshd.mail_content, "") AS mail_content,
                IFNULL(pshd.site_content, "") AS site_content,
                IF (ps.status = ' . \projects_status::DEFAUT . ', 1, 0) AS failure
            FROM projects_status_history psh
            LEFT JOIN projects_status_history_details pshd ON psh.id_project_status_history = pshd.id_project_status_history
            INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
            WHERE psh.id_project = ' . $id_project . '
            ORDER BY psh.id_project_status_history DESC';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function getDateProjectStatus($sIdProject, $sIdProjectStatus, $bIsFirstOccurence)
    {
        $sIsFirstOccurence = $bIsFirstOccurence ? "MIN" : "MAX";
        $sql = '
            SELECT '. $sIsFirstOccurence .'(added)
            FROM projects_status_history psh
            INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
            WHERE psh.id_project = ' . $sIdProject . ' AND ps.status = ' . $sIdProjectStatus;
        $result = $this->bdd->query($sql);
        $sResult = $this->bdd->result($result);

        $oResult = new \DateTime($sResult);
        return $oResult;
    }

    public function getStatusByDates($iStatus, \DateTime $oStartDate, \DateTime $oEndDate)
    {
        $sQuery = '
            SELECT psh.*, ps.label, ps.status
            FROM projects_status_history psh
            INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
            WHERE ps.id_project_status = ' . $iStatus . '
                AND psh.added >= "' . $oStartDate->format('Y-m-d') . '"
                AND psh.added <= "' . $oEndDate->format('Y-m-d') . '"
            GROUP BY psh.id_project, ps.status';

        $aResult = array();
        $rResult = $this->bdd->query($sQuery);

        while ($aRow = $this->bdd->fetch_assoc($rResult)) {
            $aResult[] = $aRow;
        }

        return $aResult;
    }

    public function getFollowingStatus(array $aBaseStatusId)
    {
        $sQuery = '
            SELECT IFNULL(next_status.id_project_status_history, base_status.id_project_status_history) AS id_project_status_history,
                IFNULL(ps.label, "none") AS label,
                IFNULL(ps.status, "0") AS status,
                IFNULL(DATEDIFF(next_status.added, base_status.added), "") AS diff_days,
                IFNULL(next_status.added, "") AS added
            FROM projects_status_history base_status
            LEFT JOIN projects_status_history next_status ON next_status.id_project_status_history = (SELECT id_project_status_history FROM projects_status_history WHERE id_project = base_status.id_project AND id_project_status_history > base_status.id_project_status_history AND id_project_status != base_status.id_project_status ORDER BY id_project_status_history ASC LIMIT 1)
            LEFT JOIN projects_status ps ON next_status.id_project_status = ps.id_project_status
            WHERE base_status.id_project_status_history IN (' . implode(', ', $aBaseStatusId) . ')
            GROUP BY id_project_status_history
            ORDER BY status ASC';

        $aResult = array();
        $rResult = $this->bdd->query($sQuery);

        while ($aRow = $this->bdd->fetch_assoc($rResult)) {
            $aResult[] = $aRow;
        }

        return $aResult;
    }

    /**
     * @param string $sDateAdded
     * @param array $aProjectStatus
     * @return array|bool
     */
    public function countProjectStatusChangesOnDate($sDateAdded, $aProjectStatus)
    {
        if (empty($sDateAdded)) {
            return false;
        }

        if (empty($aProjectStatus) || false === is_array($aProjectStatus)) {
            return false;
        }

        $sQuery = 'SELECT
                        COUNT(*),
                        ps.status,
                        ps.label
                    FROM
                        projects_status_history psh
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                    WHERE
                        DATE(psh.added) = ' . $sDateAdded . '
                        AND ps.status IN ' . implode(',', $aProjectStatus);

        $aProjectStatusCount = array();
        $rQuery              = $this->bdd->query($sQuery);
        while ($aRecord = $this->bdd->fetch_array($rQuery)) {
            $aProjectStatusCount[] = $aRecord;
        }

        return $aProjectStatusCount;
    }

    /**
     * @param int $iProjectId
     */
    public function loadLastProjectHistory($iProjectId)
    {
        $sQuery = '
            SELECT MAX(id_project_status_history)
            FROM projects_status_history
            WHERE id_project = ' . $iProjectId;
        $this->get($this->bdd->result($this->bdd->query($sQuery), 0, 0));
    }
}
