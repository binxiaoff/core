<?php

class tax extends tax_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::tax($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM tax' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM tax ' . $where), 0, 0);
    }

    public function exist($id, $field = 'id_tax')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM tax WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }

    /**
     * @param string $date yyyy-mm-dd date formated
     * @return array of tax sum by tax type
     */
    public function getDailyTax($date)
    {
        $sql = 'SELECT SUM(amount) as daily_amount, id_tax_type
                FROM tax
                WHERE DATE(added) = "' . $date . '" GROUP BY id_tax_type';
        $rQuery = $this->bdd->query($sql);
        $aResult = array();
        while ($aRow = $this->bdd->fetch_assoc($rQuery)) {
            $aResult[$aRow['id_tax_type']] = $aRow['daily_amount'];
        }
        return $aResult;
    }

    /**
     * @param int $iRepaymentId
     * @return int
     */
    public function getAmountByRepaymentId($iRepaymentId)
    {
        $sql = 'SELECT SUM(tax.amount) 
                  FROM tax 
                WHERE tax.id_transaction = 
                  (SELECT t.id_transaction 
                      FROM transactions t 
                   WHERE t.id_echeancier = ' . $iRepaymentId . ' AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . ')';

        $result = $this->bdd->query($sql);
        $return = (int) ($this->bdd->result($result, 0, 0));
        return $return;
    }

    /**
     * @param int $iRepaymentId
     * @return array
     */
    public function getTaxListByRepaymentId($iRepaymentId)
    {
        $sql = 'SELECT * 
                  FROM tax 
                WHERE tax.id_transaction = 
                  (SELECT t.id_transaction 
                      FROM transactions t 
                   WHERE t.id_echeancier = ' . $iRepaymentId . ' AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . ')';

        $result   = array();
        $resultat = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[$record['id_tax_type']] = $record;
        }
        return $result;
    }

    /**
     * @param int $iLenderId
     * @param string $sYear
     * @return array
     */
    public function getTaxByMounth($iLenderId, $sYear)
    {
        $sql = '
        SELECT SUM(tax.amount) as taxAmount,
        LEFT(tax.added, 7) AS date
        FROM tax
        INNER JOIN transactions t on t.id_transaction = tax.id_transaction
        INNER JOIN echeanciers e ON e.id_echeancier = t.id_echeancier AND e.status IN (' . \echeanciers::STATUS_REPAID . ', ' . \echeanciers::STATUS_PARTIALLY_REPAID . ') AND e.id_lender = ' . $iLenderId . '
        WHERE year(tax.added) = ' . $sYear . '
        GROUP BY left(tax.added, 7)';

        $res    = array();
        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_array($result)) {
            $d          = explode('-', $record['date']);
            $res[$d[1]] = bcdiv($record['taxAmount'], 100, 2);
        }
        return $res;
    }

    /**
     * @param int $iLenderId
     * @param string $sBegin
     * @param string $sEnd
     * @return array
     */
    public function getTaxByYear($iLenderId, $sBegin, $sEnd)
    {
        $sql = '
        SELECT SUM(tax.amount) as taxAmount,
        LEFT(tax.added, 7) AS date
        FROM tax
        INNER JOIN transactions t on t.id_transaction = tax.id_transaction
        INNER JOIN echeanciers e ON e.id_echeancier = t.id_echeancier AND e.status IN (' . \echeanciers::STATUS_REPAID . ', ' . \echeanciers::STATUS_PARTIALLY_REPAID . ') AND e.id_lender = ' . $iLenderId . '
        WHERE year(tax.added) >= ' . $sBegin . '
        AND YEAR(tax.added) <= ' . $sEnd . '
        GROUP BY left(tax.added, 7)';
        
        $result = $this->bdd->query($sql);

        $res      = array();
        $resultat = array();
        while ($record = $this->bdd->fetch_array($result)) {
            $res[$record['date']] = $record['taxAmount'];
        }

        for ($i = $sBegin; $i <= $sEnd; $i++) {
            $resultat[$i] = number_format(isset($res[$i]) ? $res[$i] : 0, 2, '.', '');
        }

        return $resultat;
    }
}
