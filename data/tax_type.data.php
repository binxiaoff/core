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

    /**
     * @param string $country
     * @param array $taxTypeId tax type to ignore
     * @return array
     */
    public function getTaxRateByCountry($country = 'fr', array $taxTypeId = [])
    {
        $aTaxRate = [];
        foreach ($this->getTaxDetailsByCountry($country, $taxTypeId) as $aRow) {
            $aTaxRate[$aRow['id_tax_type']] = $aRow['rate'];
        }
        return $aTaxRate;
    }

    /**
     * @param string $country
     * @param array $taxTypeId
     * @return array
     * @throws Exception
     */
    public function getTaxDetailsByCountry($country = 'fr', array $taxTypeId = [])
    {
        $aBind = ['country' => $country];
        $aType = ['country' => \PDO::PARAM_STR];
        $sql   = '
        SELECT 
            id_tax_type,
            rate
        FROM tax_type tt 
        WHERE tt.country = :country';

        if (false === empty($taxTypeId)) {
            $aBind['id_type'] = $taxTypeId;
            $aType['id_type'] = \Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
            $sql .= ' AND tt.id_tax_type NOT IN (:id_type)';
        }
        return $this->bdd->executeQuery($sql, $aBind, $aType)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
