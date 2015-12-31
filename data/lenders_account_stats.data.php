<?php

class lenders_account_stats extends lenders_account_stats_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::lenders_account_stats($bdd, $params);
    }

    public function getLastIRRForLender($iLenderId)
    {
        $sql = '
            SELECT *
            FROM lenders_account_stats
            WHERE id_lender_account = ' . $iLenderId . '
            ORDER BY tri_date DESC
            LIMIT 1';

        return $this->bdd->fetch_assoc($this->bdd->query($sql));
    }
}
