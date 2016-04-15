<?php

class company_rating extends company_rating_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::company_rating($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM company_rating' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $result   = array();
        $resultat = $this->bdd->query($sql);
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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM company_rating' . $where), 0, 0);
    }

    public function exist($id, $field = 'id_company_rating')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM company_rating WHERE ' . $field . ' = "' . $id . '"'), 0, 0) > 0;
    }

    public function getHistoryRatingsByType($iCompanyRatingHistoryId)
    {
        $aRatings = array();
        foreach ($this->select('id_company_rating_history = ' . $iCompanyRatingHistoryId) as $aRating) {
            $aRatings[$aRating['type']] = $aRating['value'];
        }
        return $aRatings;
    }
}
