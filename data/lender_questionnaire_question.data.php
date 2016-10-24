<?php

class lender_questionnaire_question extends lender_questionnaire_question_crud
{
    CONST TYPE_AWARE_MONEY_LOSS                    = 'aware-money-loss';
    CONST TYPE_AWARE_PROGRESSIVE_CAPITAL_REPAYMENT = 'aware-progressive-capital-repayment';
    CONST TYPE_AWARE_RISK_RETURN                   = 'aware-risk-return';
    CONST TYPE_AWARE_DIVIDE_INVESTMENTS            = 'aware-divide-investments';
    CONST TYPE_VALUE_TOTAL_ESTATE                  = 'value-total-estate';
    CONST TYPE_VALUE_MONTHLY_SAVINGS               = 'value-monthly-savings';
    CONST TYPE_VALUE_BLOCKING_PERIOD               = 'value-blocking-period';
    CONST TYPE_VALUE_OTHER_FINANCIAL_PRODUCTS_USE  = 'value-other-financial-products-use';

    const VALUE_BOOLEAN_TRUE              = 'true';
    const VALUE_BOOLEAN_FALSE             = 'false';
    const VALUE_ESTATE_THRESHOLD          = 20000;
    const VALUE_MONTHLY_SAVINGS_THRESHOLD = 200;
    const VALUE_BLOCKING_PERIOD_1         = '-12';
    const VALUE_BLOCKING_PERIOD_2         = '-36';
    const VALUE_BLOCKING_PERIOD_3         = '-60';
    const VALUE_BLOCKING_PERIOD_4         = '+60';

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

    /**
     * @param string $type
     * @return bool
     */
    public function isBooleanType($type)
    {
        return in_array($type, [\lender_questionnaire_question::TYPE_AWARE_MONEY_LOSS, \lender_questionnaire_question::TYPE_AWARE_PROGRESSIVE_CAPITAL_REPAYMENT, \lender_questionnaire_question::TYPE_AWARE_RISK_RETURN, \lender_questionnaire_question::TYPE_AWARE_DIVIDE_INVESTMENTS]);
    }
}
