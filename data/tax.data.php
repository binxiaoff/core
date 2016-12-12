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
     * @param string $startDate yyyy-mm-dd H:i:s date formated
     * @param string $endDate yyyy-mm-dd H:i:s date formated
     * @param array $taxType
     * @return array of tax sum by tax type
     */
    public function getDailyTax($startDate, $endDate, array $taxType = [])
    {
        if (false === empty($taxType)) {
            $taxTypeWhere = ' AND id_tax_type IN (:tax_type) ';
        } else {
            $taxTypeWhere = null;
        }
        $sql = 'SELECT SUM(amount) as daily_amount, id_tax_type
                FROM tax
                WHERE added BETWEEN :start_date AND :end_date' . $taxTypeWhere . '
                GROUP BY id_tax_type';

        $statement = $this->bdd->executeQuery(
            $sql,
            array('start_date' => $startDate, 'end_date' => $endDate, 'tax_type' => $taxType),
            array('start_date' => \PDO::PARAM_STR, 'end_date' => \PDO::PARAM_STR, 'tax_type' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY),
            new \Doctrine\DBAL\Cache\QueryCacheProfile(\Unilend\librairies\CacheKeys::LONG_TIME, md5(__METHOD__)));
        $aResult = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        $aDailyTax = [];
        foreach ($aResult as $aRow) {
            $aDailyTax[$aRow['id_tax_type']] = $aRow['daily_amount'];
        }
        return $aDailyTax;
    }

    /**
     * @param int $iRepaymentId
     * @return int
     */
    public function getAmountByRepaymentId($iRepaymentId)
    {
        $sql = 'SELECT IFNULL(SUM(tax.amount), 0) AS taxAmount
                  FROM tax 
                WHERE tax.id_transaction = 
                  (SELECT t.id_transaction 
                      FROM transactions t 
                   WHERE t.id_echeancier = ' . $iRepaymentId . ' AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . ')';

        $result = $this->bdd->query($sql);
        $return = $this->bdd->result($result, 0, 0);
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
     * @param int $iBegin
     * @param int $iEnd
     * @return array
     */
    public function getTaxByYear($iLenderId, $iBegin, $iEnd)
    {
        $sql = '
        SELECT SUM(NULLIF(tax.amount, 0)) as taxAmount,
        YEAR(tax.added) AS date
        FROM tax
        INNER JOIN transactions t on t.id_transaction = tax.id_transaction
        INNER JOIN echeanciers e ON e.id_echeancier = t.id_echeancier AND e.status IN (' . \echeanciers::STATUS_REPAID . ', ' . \echeanciers::STATUS_PARTIALLY_REPAID . ') AND e.id_lender = ' . $iLenderId . '
        WHERE year(tax.added) >= ' . $iBegin . '
        AND YEAR(tax.added) <= ' . $iEnd . '
        GROUP BY year(tax.added)';

        $result = $this->bdd->query($sql);
        $res      = array();

        while ($record = $this->bdd->fetch_array($result)) {
            $res[$record['date']] = $record['taxAmount'];
        }

        return $res;
    }

    /**
     * @param $iLenderId
     * @return int
     */
    public function getTotalAmountForLender($iLenderId)
    {
        $iTotalAmount = 0;
        foreach ($this->getTotalAmountByType($iLenderId) as $aRow) {
            $iTotalAmount += $aRow['total_tax_amount'];
        }
        return $iTotalAmount;
    }

    /**
     * @param $iLenderId
     * @return array
     */
    public function getTotalAmountByType($iLenderId)
    {
        $sql = '
        SELECT
          SUM(amount) AS total_tax_amount,
          id_tax_type
        FROM
          tax
          INNER JOIN transactions t ON t.id_transaction = tax.id_transaction
          INNER JOIN echeanciers e ON e.id_echeancier = t.id_echeancier AND e.status IN (:repayment_status_list)
        WHERE e.id_lender = :id_lender AND e.status_ra = 0
        GROUP BY tax.id_tax_type';

        try {
            return $this->bdd->executeQuery($sql,
                array('id_lender' => $iLenderId, 'repayment_status_list' => array(\echeanciers::STATUS_PARTIALLY_REPAID, \echeanciers::STATUS_REPAID)),
                array('id_lender' => \PDO::PARAM_INT, 'repayment_status_list' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY))
                ->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $exception) {
            return array();
        }
    }
}
