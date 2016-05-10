<?php

class tax_type extends tax_type_crud
{
    const TYPE_VAT                                          = 1;
    const TYPE_INCOME_TAX                                   = 2;
    const TYPE_CSG                                          = 3;
    const TYPE_SOCIAL_DEDUCTIONS                            = 4;
    const TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS = 5;
    const TYPE_SOLIDARITY_DEDUCTIONS                        = 6;
    const TYPE_CRDS                                         = 7;
    const TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE                = 8;

    public function __construct($bdd, $params = '')
    {
        parent::tax_type($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM tax_type' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM tax_type ' . $where), 0, 0);
    }

    public function exist($id, $field = 'id_tax_type')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM tax_type WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }
}
