<?php

class project_period extends project_period_crud
{
    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `project_period`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `project_period` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_period')
    {
        $sql    = 'SELECT * FROM `project_period` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    public function getDurations($periodId)
    {
        $aDuration = [];

        $sQuery = 'SELECT min, max FROM project_period WHERE id_period = :periodId';
        try {
            $statement = $this->bdd->executeQuery($sQuery, array('periodId' => $periodId), array('periodId' => \PDO::PARAM_INT), new \Doctrine\DBAL\Cache\QueryCacheProfile(300, md5(__METHOD__)));
            $aDurations = $statement->fetchall(PDO::FETCH_ASSOC);
            $statement->closeCursor();
            if (false === empty($aDurations)) {
                $aDuration = array_shift($aDurations);
            }
        } catch (\Doctrine\DBAL\DBALException $exception) {
            $aDuration = array();
        }
        return $aDuration;
    }

    public function getPeriod($iDuration, $iStatus = self::STATUS_ACTIVE)
    {
        $rQuery = $this->bdd->query('SELECT * FROM `project_period` WHERE ' . $iDuration . ' BETWEEN `min` AND `max` AND `status` = ' . $iStatus);
        $period = $this->bdd->fetch_assoc($rQuery);
        if ($period) {
            return $this->get($period['id_period']);
        }

        return false;
    }
}
