<?php

class lender_questionnaire_question extends lender_questionnaire_question_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::lender_questionnaire_question($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `lender_questionnaire_question`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM `lender_questionnaire_question`' . $where));
    }

    public function exist($id, $field = 'id_lender_questionnaire_question')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM `lender_questionnaire_question` WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }
}
