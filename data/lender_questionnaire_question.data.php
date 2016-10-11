<?php

class lender_questionnaire_question extends lender_questionnaire_question_crud
{
    const TYPE_BOOLEAN            = 'bool';
    const TYPE_ESTATE             = 'estate';
    const TYPE_MONTHLY_SAVINGS    = 'monthly_savings';
    const TYPE_BLOCKING_PERIOD    = 'blocking_period';
    const TYPE_FINANCIAL_PRODUCTS = 'financial_products';

    const VALUE_BOOLEAN_TRUE                = 'true';
    const VALUE_BOOLEAN_FALSE               = 'false';
    const VALUE_ESTATE_THRESHOLD            = 20000;
    const VALUE_MONTHLY_SAVINGS_THRESHOLD   = 200;
    const VALUE_BLOCKING_PERIOD_THRESHOLD_1 = 12;
    const VALUE_BLOCKING_PERIOD_THRESHOLD_2 = 36;
    const VALUE_BLOCKING_PERIOD_THRESHOLD_3 = 60;

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
