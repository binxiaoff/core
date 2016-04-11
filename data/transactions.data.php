<?php
// **************************************************************************************************** //
// ***************************************    ASPARTAM    ********************************************* //
// **************************************************************************************************** //
//
// Copyright (c) 2008-2011, equinoa
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
// associated documentation files (the "Software"), to deal in the Software without restriction,
// including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
// subject to the following conditions:
// The above copyright notice and this permission notice shall be included in all copies
// or substantial portions of the Software.
// The Software is provided "as is", without warranty of any kind, express or implied, including but
// not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement.
// In no event shall the authors or copyright holders equinoa be liable for any claim,
// damages or other liability, whether in an action of contract, tort or otherwise, arising from,
// out of or in connection with the software or the use or other dealings in the Software.
// Except as contained in this notice, the name of equinoa shall not be used in advertising
// or otherwise to promote the sale, use or other dealings in this Software without
// prior written authorization from equinoa.
//
//  Version : 2.4.0
//  Date : 21/03/2011
//  Coupable : CM
//
// **************************************************************************************************** //

class transactions extends transactions_crud
{
    const PAYMENT_TYPE_VISA       = 0;
    const PAYMENT_TYPE_MASTERCARD = 3;
    const PAYMENT_TYPE_AUTO       = 1;
    const PAYMENT_TYPE_AMEX       = 2;

    const PAYMENT_STATUS__NOK = 0;
    const PAYMENT_STATUS_OK   = 1;

    const STATUS_PENDING  = 0;
    const STATUS_VALID    = 1;
    const STATUS_CANCELED = 3;

    const PHYSICAL = 1;
    const VIRTUAL  = 2;

    const DISPLAY_IN_FO = 0;
    const HIDE_IN_FO = 1;

    public function __construct($bdd, $params = '')
    {
        parent::transactions($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `transactions`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $result = $this->bdd->query('SELECT COUNT(*) FROM `transactions` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_transaction')
    {
        $result = $this->bdd->query('SELECT * FROM `transactions` WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function getSumDepotByMonths($id_client, $year)
    {
        $sql = '
            SELECT SUM(montant / 100) AS montant,
                LEFT(date_transaction, 7) AS date
            FROM transactions
            WHERE status = 1
                AND etat = 1
                AND YEAR(date_transaction) = ' . $year . '
                AND type_transaction IN (1, 3, 4)
                AND display = 0
                AND id_client = ' . $id_client . '
            GROUP BY LEFT(date_transaction, 7)';

        $req = $this->bdd->query($sql);
        $res = array();
        while ($rec = $this->bdd->fetch_array($req)) {
            $d          = explode('-', $rec['date']);
            $res[$d[1]] = $rec['montant'];
        }
        return $res;
    }

    public function sum($where = '', $champ)
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT SUM(' . $champ . ') FROM `transactions` ' . $where;

        $result = $this->bdd->query($sql);
        $return = (int) ($this->bdd->result($result, 0, 0));

        return $return;
    }

    public function getSolde($id_client)
    {
        $sql = '
            SELECT SUM(montant) AS solde
            FROM transactions
            WHERE etat = 1
                AND status = 1
                AND id_client = ' . $id_client;

        $result = $this->bdd->query($sql);
        $solde  = $this->bdd->result($result, 0, 'solde');
        if ($solde == '') {
            $solde = 0;
        } else {
            $solde = ($solde / 100);
        }
        return $solde;
    }

    // solde jusqu'a une certaine date (solde a une date precise)
    public function getSoldeDateLimite($id_client, $dateLimite)
    {
        $sql = '
            SELECT SUM(montant) AS solde
            FROM transactions
            WHERE etat = 1
                AND status = 1
                AND id_client = ' . $id_client . '
                AND type_transaction NOT IN (9, 6, 15)
                AND DATE(added) <= "' . $dateLimite . '"';

        $result = $this->bdd->query($sql);
        $solde  = $this->bdd->result($result, 0, 'solde');
        if ($solde == '') {
            $solde = 0;
        } else {
            $solde = ($solde / 100);
        }
        return $solde;
    }

    public function sumByMonthByPreteur($id_client, $type_transaction, $month, $year)
    {
        $sql = '
            SELECT SUM(montant) AS montant
            FROM transactions
            WHERE MONTH(added) = ' . $month . '
                AND YEAR(added) = ' . $year . '
                AND etat = 1
                AND status = 1
                AND type_transaction IN(' . $type_transaction . ')
                AND id_client = ' . $id_client;

        $result  = $this->bdd->query($sql);
        $montant = $this->bdd->result($result, 0, 'montant');
        if ($montant == '') {
            $montant = 0;
        } else {
            $montant = ($montant / 100);
        }
        return $montant;
    }

    public function sumByday($type_transaction, $month, $year)
    {
        // On recup le nombre de jour dans le mois
        $mois    = mktime(0, 0, 0, $month, 1, $year);
        $nbJours = date("t", $mois);

        $listDates = array();
        for ($i = 1; $i <= $nbJours; $i++) {
            $listDates[$i] = $year . '-' . $month . '-' . (strlen($i) < 2 ? '0' : '') . $i;
        }

        $result = array();

        if ($type_transaction == 3) { // si cb on recup les inscription par cb
            $sql = '
                SELECT
                    SUM(ROUND(t.montant / 100, 2)) AS montant,
                    SUM(ROUND(montant_unilend / 100, 2)) AS montant_unilend,
                    SUM(ROUND(montant_etat / 100, 2)) AS montant_etat,
                    DATE(t.date_transaction) AS jour
                FROM transactions t,lenders_accounts l
                WHERE t.id_client = l.id_client_owner
                    AND MONTH(t.added) = ' . $month . '
                    AND YEAR(t.added) = ' . $year . '
                    AND t.etat = 1
                    AND t.status = 1
                    AND t.type_transaction = 1
                    AND l.type_transfert = 2
                GROUP BY DATE(t.date_transaction)';

            $resultat = $this->bdd->query($sql);

            while ($record = $this->bdd->fetch_array($resultat)) {
                $result[$record['jour']]['montant']         = $record['montant'];
                $result[$record['jour']]['montant_unilend'] = $record['montant_unilend'];
                $result[$record['jour']]['montant_etat']    = $record['montant_etat'];
            }
        }

        $sql = '
            SELECT
                SUM(ROUND(montant / 100, 2)) AS montant,
                SUM(ROUND(montant_unilend / 100, 2)) AS montant_unilend,
                SUM(ROUND(montant_etat / 100, 2)) AS montant_etat,
                DATE(date_transaction) AS jour
            FROM transactions
            WHERE MONTH(added) = ' . $month . '
                AND YEAR(added) = ' . $year . '
                AND etat = 1
                AND status = 1
                AND type_transaction IN(' . $type_transaction . ')
            GROUP BY DATE(date_transaction)';

        $resultat = $this->bdd->query($sql);

        while ($record = $this->bdd->fetch_array($resultat)) {
            if (false === isset($result[$record['jour']])) {
                $result[$record['jour']] = array(
                    'montant'         => 0,
                    'montant_unilend' => 0,
                    'montant_etat'    => 0
                );
            }
            $result[$record['jour']]['montant'] += $record['montant'];
            $result[$record['jour']]['montant_unilend'] += $record['montant_unilend'];
            $result[$record['jour']]['montant_etat'] = $record['montant_etat'];
        }

        // on affiche chaque jours du mois
        foreach ($listDates as $d) {
            $lresult[$d]['montant']         = empty($result[$d]['montant']) ? '0' : $result[$d]['montant'];
            $lresult[$d]['montant_unilend'] = empty($result[$d]['montant_unilend']) ? '0' : $result[$d]['montant_unilend'];
            $lresult[$d]['montant_etat']    = empty($result[$d]['montant_etat']) ? '0' : $result[$d]['montant_etat'];

        }

        return $lresult;
    }

    // solde d'une journée
    public function getSoldeReelDay($date)
    {
        $sql = '
            SELECT SUM(montant) AS solde
            FROM transactions
            WHERE etat = 1
                AND status = 1
                AND transaction = 1
                AND type_transaction <> 9
                AND type_transaction <> 11
                AND type_transaction <> 12
                AND type_transaction <> 14
                AND DATE(date_transaction) = "' . $date . '"
            GROUP BY DATE(date_transaction)';

        $result = $this->bdd->query($sql);
        $solde  = $this->bdd->result($result, 0, 'solde');
        if ($solde == '') {
            $solde = 0;
        } else {
            $solde = ($solde / 100);
        }
        return $solde;
    }

    // solde d'une journée
    public function getSoldeReelUnilendDay($date)
    {
        $sql = '
            SELECT SUM(montant - montant_unilend) AS solde
            FROM transactions
            WHERE etat = 1
                AND status = 1
                AND transaction = 1
                AND type_transaction = 9
                AND DATE(date_transaction) = "' . $date . '"
            GROUP BY DATE(date_transaction)';

        $result = $this->bdd->query($sql);
        $solde  = $this->bdd->result($result, 0, 'solde');
        if ($solde == '') {
            $solde = 0;
        } else {
            $solde = ($solde / 100);
        }
        return $solde;
    }

    public function getSoldeReelEtatDay($date)
    {
        $sql = '
            SELECT SUM(montant_etat) AS solde
            FROM transactions
            WHERE etat = 1
                AND status = 1
                AND transaction = 2
                AND type_transaction = 10
                AND DATE(date_transaction) = "' . $date . '"
            GROUP BY DATE(date_transaction)';

        $result = $this->bdd->query($sql);
        $solde  = $this->bdd->result($result, 0, 'solde');
        if ($solde == '') {
            $solde = 0;
        } else {
            $solde = ($solde / 100);
        }
        return $solde;
    }

    // total soldes d'un mois
    public function getSoldePreteur($id_client, $month, $year)
    {
        $sql = '
            SELECT SUM(montant) AS solde
            FROM transactions
            WHERE etat = 1
                AND status = 1
                AND LEFT(added, 7) <= "' . $year . '-' . $month . '"
                AND id_client = ' . $id_client;

        $result = $this->bdd->query($sql);
        $solde  = $this->bdd->result($result, 0, 'solde');
        if ($solde == '') {
            $solde = 0;
        } else {
            $solde = ($solde / 100);
        }
        return $solde;
    }

    public function selectTransactionsOp($array_type_transactions, $where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' AND ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = '
        ( SELECT t.*,

            CASE ';

        foreach ($array_type_transactions as $key => $t) {
            if ($key == 2) {
                foreach ($t as $key_offre => $offre) {
                    // offre en cours
                    if ($key_offre == 1) {
                        $sql .= ' WHEN t.type_transaction = ' . $key . ' AND t.montant <= 0 THEN "' . $offre . '"';
                    } // offre rejeté
                    elseif ($key_offre == 2) {
                        $sql .= ' WHEN t.type_transaction = ' . $key . ' AND t.montant > 0 THEN "' . $offre . '"';
                    } // offre acceptée
                    else {
                        $sql .= ' WHEN t.type_transaction = ' . $key . ' AND t.montant <= 0 THEN "' . $t[1] . '"';
                    }
                }
            } elseif ($key == 5) {
                foreach ($t as $key_remb => $remb) {
                    // remb
                    if ($key_remb == 1) {
                        $sql .= ' WHEN t.type_transaction = ' . $key . ' AND t.recouvrement = 0 THEN "' . $remb . '"';
                    } // recouvrement
                    else {
                        $sql .= ' WHEN t.type_transaction = ' . $key . ' AND t.recouvrement = 1 THEN "' . $remb . '"';
                    }
                }
            } else {
                $sql .= '
                    WHEN t.type_transaction = ' . $key . ' THEN "' . $t . '"';
            }
        }
        $sql .= '
                ELSE ""
            END as type_transaction_alpha,

            CASE
                WHEN t.type_transaction = 5 THEN (SELECT ech.id_project FROM echeanciers ech WHERE ech.id_echeancier = t.id_echeancier)
                WHEN b.id_project IS NULL THEN b2.id_project
                ELSE b.id_project
            END as le_id_project,

            date_transaction as date_tri,

            (SELECT ROUND(SUM(t2.montant/100),2) as solde FROM transactions t2 WHERE t2.etat = 1 AND t2.status = 1 AND t2.id_client = t.id_client AND t2.type_transaction NOT IN (9,6,15) AND t2.id_transaction <= t.id_transaction ) as solde,

            CASE t.type_transaction
                WHEN 2 THEN (SELECT p.title FROM projects p WHERE p.id_project = le_id_project)
                WHEN 5 THEN (SELECT p2.title FROM projects p2 LEFT JOIN echeanciers e ON p2.id_project = e.id_project WHERE e.id_echeancier = t.id_echeancier)
                WHEN 23 THEN (SELECT p2.title FROM projects p2 WHERE p2.id_project = t.id_project)
                WHEN 26 THEN (SELECT p2.title FROM projects p2 WHERE p2.id_project = t.id_project)
                ELSE ""
            END as title,

            CASE t.type_transaction
                WHEN 2 THEN 0
                WHEN 5 THEN (SELECT e.id_loan FROM echeanciers e WHERE e.id_echeancier = t.id_echeancier)
                WHEN 23 THEN (SELECT e.id_loan FROM echeanciers e WHERE e.id_project = t.id_project AND w.id_lender = e.id_lender LIMIT 1)
                ELSE ""
            END as bdc,

            t.montant as amount_operation

            FROM transactions t
            LEFT JOIN wallets_lines w ON t.id_transaction = w.id_transaction
            LEFT JOIN bids b ON w.id_wallet_line = b.id_lender_wallet_line
            LEFT JOIN bids b2 ON t.id_bid_remb = b2.id_bid
            WHERE 1=1
            ' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : '')) . '
        )

        UNION ALL

        (
            SELECT
              t.*,
              "' . $array_type_transactions[2][3] . '" as type_transaction_alpha,
              lo.id_project as le_id_project,
              psh.added as date_tri,
              (SELECT ROUND(SUM(t2.montant/100),2) as solde FROM transactions t2 WHERE t2.etat = 1 AND t2.status = 1 AND t2.id_client = t.id_client AND t2.type_transaction NOT IN (9,6,15) AND t2.date_transaction < date_tri ) as solde,
              p.title as title,
              lo.id_loan as bdc,
              lo.amount as amount_operation
            FROM loans lo
              INNER JOIN accepted_bids ab ON ab.id_loan = lo.id_loan
              INNER JOIN bids b ON ab.id_bid = b.id_bid
              INNER JOIN wallets_lines w ON w.id_wallet_line = b.id_lender_wallet_line
              INNER JOIN transactions t ON t.id_transaction = w.id_transaction
              INNER JOIN projects p ON p.id_project = lo.id_project
              INNER JOIN projects_status_history psh ON psh.id_project = lo.id_project
            WHERE lo.status = 0
            AND psh.id_project_status_history = (
              SELECT MIN(id_project_status_history) FROM projects_status_history psh1 WHERE psh1.id_project = lo.id_project AND psh1.id_project_status = 8
            )
            ' . $where . '
            ' . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : '')) . '
        )
        ' . $order . '
        ';
        $this->bdd->query("SET SQL_BIG_SELECTS=1");  //Set it before your main query

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }
}
