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
class unilend_stats extends unilend_stats_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::unilend_stats($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `unilend_stats`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $result = $this->bdd->query('SELECT COUNT(*) FROM `unilend_stats` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_unilend_stat')
    {
        $result = $this->bdd->query('SELECT * FROM `unilend_stats` WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_assoc($result) > 0);
    }

    /**
     * @param bool $bUseProjectLastStatusMaterialized
     * @return array
     */
    public function getDataForUnilendIRR($bUseProjectLastStatusMaterialized = false)
    {

        if ($bUseProjectLastStatusMaterialized) {
            $sJoinStatementProjectHistoryTables =   'INNER JOIN projects_last_status_history_materialized plshm ON ee.id_project = plshm.id_project
                                                     INNER JOIN projects_status_history psh ON plshm.id_project_status_history = psh.id_project_status_history ';
        } else {
            $sJoinStatementProjectHistoryTables =   'INNER JOIN projects_last_status_history plsh ON ee.id_project = plsh.id_project
                                                     INNER JOIN projects_status_history psh ON plsh.id_project_status_history = psh.id_project_status_history ';
        }

        $sQuery =   'SELECT
                        montant - `montant_unilend` AS montant,
                        date_transaction AS date
                    FROM
                        `transactions`
                    WHERE
                        `type_transaction` = ' . \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT . '
                    UNION ALL
                    SELECT
                        CASE WHEN ee.status_ra = 1 THEN ee.capital ELSE ee.capital + ee.interets END AS montant,
                        (
                            SELECT
                                CASE WHEN e.status = 1 THEN e.date_echeance_reel ELSE e.date_echeance END
                            FROM
                                echeanciers e
                            WHERE
                                e.ordre = ee.ordre
                                AND ee.id_project = e.id_project
                            LIMIT
                                1
                        ) AS date
                    FROM
                        echeanciers_emprunteur ee
                        ' . $sJoinStatementProjectHistoryTables . '
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                    WHERE
                        (
                            SELECT
                                e2.status
                            FROM
                                echeanciers e2
                            WHERE
                                e2.ordre = ee.ordre
                                AND ee.id_project = e2.id_project
                            LIMIT
                                1
                        ) = 1
                    UNION ALL
                    SELECT
                        ee.capital + ee.interets AS montant,
                        (
                            SELECT
                                e.date_echeance
                            FROM
                                echeanciers e
                            WHERE
                                e.ordre = ee.ordre
                                AND ee.id_project = e.id_project
                            LIMIT
                                1
                        ) AS date
                    FROM
                        echeanciers_emprunteur ee
                        ' . $sJoinStatementProjectHistoryTables . '
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                    WHERE
                        (
                            SELECT
                                e2.status
                            FROM
                                echeanciers e2
                            WHERE
                                e2.ordre = ee.ordre
                                AND ee.id_project = e2.id_project
                            LIMIT
                                1
                        ) = 0
                        AND ps.status = ' . \projects_status::REMBOURSEMENT . '
                        AND ee.id_project > 0
                    UNION ALL
                    SELECT
                        CASE WHEN ee.date_echeance_emprunteur < NOW() THEN "0" ELSE ee.capital + ee.interets END AS montant,
                        (
                            SELECT
                                e.date_echeance
                            FROM
                                echeanciers e
                            WHERE
                                e.ordre = ee.ordre
                                AND ee.id_project = e.id_project
                            LIMIT
                                1
                        ) AS date
                    FROM
                        echeanciers_emprunteur ee
                        ' . $sJoinStatementProjectHistoryTables . '
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                    WHERE
                        (
                            SELECT
                                e2.status
                            FROM
                                echeanciers e2
                            WHERE
                                e2.ordre = ee.ordre
                                AND ee.id_project = e2.id_project
                            LIMIT
                                1
                        ) = 0
                        AND ps.status IN (' . implode(',', array(\projects_status::PROBLEME, \projects_status::PROBLEME_J_X)) . ')
                        AND ee.id_project > 0
                    UNION ALL
                    SELECT
                        CASE WHEN ee.date_echeance_emprunteur < NOW() THEN "0" ELSE CASE WHEN DATEDIFF (
                            NOW(),
                            (
                                SELECT
                                    psh2.added
                                FROM
                                    projects_status_history psh2
                                    INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                                WHERE
                                    ps2.status = '. \projects_status::PROBLEME . '
                                    AND psh2.id_project = ee.id_project
                                ORDER BY
                                    psh2.added DESC
                                LIMIT
                                    1
                            )
                        ) > 180 THEN "0" ELSE ee.capital + ee.interets END END AS montant,
                        (
                            SELECT
                                e.date_echeance
                            FROM
                                echeanciers e
                            WHERE
                                e.ordre = ee.ordre
                                AND ee.id_project = e.id_project
                            LIMIT
                                1
                        ) AS date
                    FROM
                        echeanciers_emprunteur ee
                        ' . $sJoinStatementProjectHistoryTables . '
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                    WHERE
                        (
                            SELECT
                                e2.status
                            FROM
                                echeanciers e2
                            WHERE
                                e2.ordre = ee.ordre
                                AND ee.id_project = e2.id_project
                            LIMIT
                                1
                        ) = 0
                        AND ps.status = ' . \projects_status::RECOUVREMENT . '
                        AND ee.id_project > 0
                    UNION ALL
                    SELECT
                        0 AS montant,
                        (
                            SELECT
                                e.date_echeance
                            FROM
                                echeanciers e
                            WHERE
                                e.ordre = ee.ordre
                                AND ee.id_project = e.id_project
                            LIMIT
                                1
                        ) AS date
                    FROM
                        echeanciers_emprunteur ee
                        ' . $sJoinStatementProjectHistoryTables . '
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                    WHERE
                        (
                            SELECT
                                e2.status
                            FROM
                                echeanciers e2
                            WHERE
                                e2.ordre = ee.ordre
                                AND ee.id_project = e2.id_project
                            LIMIT
                                1
                        ) = 0
                        AND ps.status IN (' . implode(',', array(\projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::DEFAUT)) . ')
                        AND ee.id_project > 0';

        $aValuesIRR = array();
        $oQuery  = $this->bdd->query($sQuery);
        while ($aRecord = $this->bdd->fetch_array($oQuery)) {
            $aValuesIRR[]      = array($aRecord['date'] => $aRecord['montant']);
        }

        return $aValuesIRR;
    }

}
