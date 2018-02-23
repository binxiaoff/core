<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;

class projects_status_history extends projects_status_history_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::__construct($bdd, $params);
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

    public function getHistoryDetails($id_project)
    {
        $sql = '
            SELECT
                ps.status,
                psh.added AS added,
                IFNULL(pshd.mail_content, "") AS mail_content,
                IFNULL(pshd.site_content, "") AS site_content,
                IF (ps.status = ' . ProjectsStatus::LOSS . ', 1, 0) AS failure
            FROM projects_status_history psh
            LEFT JOIN projects_status_history_details pshd ON psh.id_project_status_history = pshd.id_project_status_history
            INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
            WHERE psh.id_project = ' . $id_project . '
            ORDER BY psh.added DESC, psh.id_project_status_history DESC';

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

    public function getFollowingStatus(array $aBaseStatusId)
    {
        $sQuery = '
            SELECT IFNULL(next_status.id_project_status_history, base_status.id_project_status_history) AS id_project_status_history,
                IFNULL(ps.label, "none") AS label,
                IFNULL(ps.status, "0") AS status,
                IFNULL(DATEDIFF(next_status.added, base_status.added), 0) AS diff_days,
                IFNULL(next_status.added, "") AS added
            FROM projects_status_history base_status
            LEFT JOIN projects_status_history next_status ON next_status.id_project_status_history = (SELECT id_project_status_history FROM projects_status_history WHERE id_project = base_status.id_project AND (added > base_status.added OR (added = base_status.added AND id_project_status_history > base_status.id_project_status_history)) AND id_project_status != base_status.id_project_status ORDER BY added ASC, id_project_status_history ASC LIMIT 1)
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
     * @param int $iProjectId
     * @return bool
     */
    public function loadLastProjectHistory($iProjectId)
    {
        $sQuery = '
            SELECT id_project_status_history
            FROM projects_status_history
            WHERE id_project = ' . $iProjectId . '
            ORDER BY added DESC, id_project_status_history DESC LIMIT 1';
        return $this->get($this->bdd->result($this->bdd->query($sQuery), 0, 0));
    }


    public function countProjectsHavingHadStatus(array $aProjectStatus)
    {
        $aBind = array('status' => $aProjectStatus);
        $aType = array('status' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        $sQuery = 'SELECT
                      COUNT(DISTINCT psh.id_project) AS amount,
                      ps.status
                    FROM projects_status_history psh
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                    WHERE ps.status IN (:status)
                    GROUP BY ps.status';

        $oStatement = $this->bdd->executeQuery($sQuery, $aBind, $aType);
        $aStatusAmounts  = array();
        while ($aRow = $oStatement->fetch(\PDO::FETCH_ASSOC)) {
            $aStatusAmounts[$aRow['status']] = (int)$aRow['amount'];
        }
        return $aStatusAmounts;
    }

    /**
     * @param int $projectId
     * @param int $status
     * @return bool
     */
    public function projectHasHadStatus($projectId, $status)
    {
        $query = 'SELECT COUNT(*)
                    FROM projects_status_history psh
                  INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                  WHERE ps.status  = :status
                  AND psh.id_project = :project_id';

        $statement = $this->bdd->executeQuery($query, ['status' => $status, 'project_id' => $projectId]);
        return $statement->fetchColumn() > 0;
    }
}
