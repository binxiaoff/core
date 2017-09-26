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

class echeanciers_emprunteur extends echeanciers_emprunteur_crud
{
    const STATUS_NO_EARLY_REFUND   = 0;
    const STATUS_EARLY_REFUND_DONE = 1;

    public function __construct($bdd, $params = '')
    {
        parent::echeanciers_emprunteur($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `echeanciers_emprunteur`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `echeanciers_emprunteur` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_echeancier_emprunteur')
    {
        $sql    = 'SELECT * FROM `echeanciers_emprunteur` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function sum($sum, $where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT sum(' . $sum . ') as sum FROM `echeanciers_emprunteur` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function onMetAjourTVA($taux)
    {
        $sql = 'UPDATE echeanciers_emprunteur SET tva = ROUND(commission * ' . $taux . ') WHERE status_emprunteur = 0';
        $this->bdd->query($sql);
    }

    public function onMetAjourLesDatesEcheancesE($id_project, $ordre, $date_echeance_emprunteur)
    {
        $sql = 'UPDATE echeanciers_emprunteur SET date_echeance_emprunteur = "' . $date_echeance_emprunteur . '", updated = "' . date('Y-m-d H:i:s') . '" WHERE id_project = ' . $id_project . ' AND status_emprunteur = 0 AND ordre = "' . $ordre . '" ';
        $this->bdd->query($sql);
    }

    // retourne le montant restant à payer pour le projet
    public function get_restant_du($id_project, $date_debut)
    {
        $sql = '
            SELECT SUM(montant) AS montant
            FROM echeanciers_emprunteur
            WHERE id_project = ' . $id_project . '
                AND status_emprunteur = 0
                AND DATE(date_echeance_emprunteur) > "' . $date_debut . '"';
        $result  = $this->bdd->query($sql);
        return $this->bdd->result($result, 0, 0);
    }

    // retourne le montant restant à payer pour le projet
    public function get_capital_restant_du($id_project, $date_debut)
    {
        $sql = '
            SELECT SUM(capital) AS montant
            FROM echeanciers_emprunteur
            WHERE id_project = ' . $id_project . '
                AND status_emprunteur = 0
                AND DATE(date_echeance_emprunteur) > "' . $date_debut . '"';
        $result  = $this->bdd->query($sql);
        return $this->bdd->result($result, 0, 0);
    }

    /**
     * @param \projects $project
     * @return mixed
     */
    public function getDetailedProjectRepaymentSchedule(\projects $project) {
        $sql = 'SELECT ee.*, e.date_echeance AS date_echeance_preteur,
				CASE e.status
					WHEN 0 THEN "En cours"
					WHEN 1 THEN "Remboursé"
				END AS "statut_preteur"
                FROM echeanciers_emprunteur ee
                INNER JOIN echeanciers e ON e.id_project = ee.id_project
                WHERE ee.id_project = :idProject
                AND ee.ordre = e.ordre
                AND ee.status_ra = :earlyRefundStatus
                GROUP BY ee.id_project, ee.ordre
                ORDER BY ee.ordre ASC';

        $paramValues = array('idProject' => $project->id_project, 'earlyRefundStatus' => \echeanciers_emprunteur::STATUS_NO_EARLY_REFUND);
        $paramTypes  = array('idProject' => \PDO::PARAM_INT, 'earlyRefundStatus' => \PDO::PARAM_INT);

        $statement = $this->bdd->executeQuery($sql, $paramValues, $paramTypes);
        $result    = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }


    public function getRepaidCapitalByCohort()
    {
        $query = 'SELECT
                  SUM(echeanciers_emprunteur.capital)/100 AS amount,
                  (
                    SELECT
                      CASE LEFT(projects_status_history.added, 4)
                        WHEN 2013 THEN "2013-2014"
                        WHEN 2014 THEN "2013-2014"
                        ELSE LEFT(projects_status_history.added, 4)
                      END AS date_range
                    FROM projects_status_history
                    INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                    WHERE  projects_status.status = '. \projects_status::REMBOURSEMENT .'
                      AND echeanciers_emprunteur.id_project = projects_status_history.id_project
                    ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
                  ) AS cohort
                FROM echeanciers_emprunteur
                WHERE (
                        SELECT e2.status
                        FROM echeanciers e2
                        WHERE e2.ordre = echeanciers_emprunteur.ordre
                          AND echeanciers_emprunteur.id_project = e2.id_project
                        LIMIT 1) = 1
              GROUP BY cohort';

        $statement = $this->bdd->executeQuery($query);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRepaidCapital()
    {
        $query = 'SELECT
                  ROUND(SUM(echeanciers_emprunteur.capital)/100)
                FROM echeanciers_emprunteur
                WHERE (
                        SELECT e2.status
                        FROM
                          echeanciers e2
                        WHERE
                          e2.ordre = echeanciers_emprunteur.ordre
                          AND echeanciers_emprunteur.id_project = e2.id_project
                        LIMIT 1
                      ) = 1';

        $statement = $this->bdd->executeQuery($query);
        return $statement->fetchColumn(0);
    }

    public function getInterestPaymentsOfHealthyProjectsByCohort()
    {
        $query = 'SELECT
                      SUM(echeanciers_emprunteur.interets)/100 AS amount,
                      (
                        SELECT
                          CASE LEFT(projects_status_history.added, 4)
                            WHEN 2013 THEN "2013-2014"
                            WHEN 2014 THEN "2013-2014"
                            ELSE LEFT(projects_status_history.added, 4)
                          END AS date_range
                        FROM projects_status_history
                          INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                        WHERE  projects_status.status = '. \projects_status::REMBOURSEMENT .'
                          AND echeanciers_emprunteur.id_project = projects_status_history.id_project
                        ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
                      ) AS cohort
                    FROM echeanciers_emprunteur
                      INNER JOIN projects ON echeanciers_emprunteur.id_project = projects.id_project AND projects.status >= '. \projects_status::REMBOURSEMENT .'
                    WHERE
                        echeanciers_emprunteur.status_ra = 0
                        AND
                            (
                            SELECT e2.status
                            FROM echeanciers e2
                            WHERE e2.ordre = echeanciers_emprunteur.ordre
                              AND echeanciers_emprunteur.id_project = e2.id_project
                            LIMIT 1 ) = 0
                        AND IF(
                                (projects.status IN ('. implode(',', [\projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::DEFAUT]).')
                                OR (projects.status >= '. \projects_status::PROBLEME . '
                                    AND DATEDIFF(NOW(), (
                                                          SELECT psh2.added
                                                          FROM projects_status_history psh2
                                                            INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                                                          WHERE ps2.status = '. \projects_status::PROBLEME . '
                                                            AND psh2.id_project = echeanciers_emprunteur.id_project
                                                          ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                                                          LIMIT 1
                                                          )) > 180)), TRUE, FALSE) = FALSE
                        GROUP BY cohort';

        $statement = $this->bdd->executeQuery($query);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getFutureCapitalPaymentsOfHealthyProjectsByCohort()
    {
        $query = 'SELECT
                      SUM(echeanciers_emprunteur.capital)/100 AS amount,
                      (
                        SELECT
                          CASE LEFT(projects_status_history.added, 4)
                            WHEN 2013 THEN "2013-2014"
                            WHEN 2014 THEN "2013-2014"
                            ELSE LEFT(projects_status_history.added, 4)
                          END AS date_range
                        FROM projects_status_history
                        INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                        WHERE  projects_status.status = '. \projects_status::REMBOURSEMENT .'
                          AND echeanciers_emprunteur.id_project = projects_status_history.id_project
                        ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
                      ) AS cohort
                    FROM echeanciers_emprunteur
                      INNER JOIN projects ON echeanciers_emprunteur.id_project = projects.id_project AND projects.status >= '. \projects_status::REMBOURSEMENT .'
                    WHERE
                        (
                        SELECT e2.status
                        FROM echeanciers e2
                        WHERE e2.ordre = echeanciers_emprunteur.ordre
                          AND echeanciers_emprunteur.id_project = e2.id_project
                        LIMIT 1
                        ) = 0
                    AND
                        (
                        SELECT e1.date_echeance
                        FROM echeanciers e1
                        WHERE e1.ordre = echeanciers_emprunteur.ordre
                         AND echeanciers_emprunteur.id_project = e1.id_project
                       LIMIT 1
                      ) >= NOW()
                    AND IF(
                            (projects.status IN ('. implode(',', [\projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::DEFAUT]).')
                            OR (projects.status >= '. \projects_status::PROBLEME . '
                                AND DATEDIFF(NOW(), (
                                                      SELECT psh2.added
                                                      FROM projects_status_history psh2
                                                        INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                                                      WHERE ps2.status = '. \projects_status::PROBLEME . '
                                                        AND psh2.id_project = echeanciers_emprunteur.id_project
                                                      ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                                                      LIMIT 1
                                                      )) > 180)), TRUE, FALSE) = FALSE
                        GROUP BY cohort';

        $statement = $this->bdd->executeQuery($query);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getFutureOwedCapitalOfProblematicProjectsByCohort()
    {
        $query = 'SELECT
                      SUM(echeanciers_emprunteur.capital)/100 AS amount,
                      (
                        SELECT
                            CASE LEFT(projects_status_history.added, 4)
                                WHEN 2013 THEN "2013-2014"
                                WHEN 2014 THEN "2013-2014"
                                ELSE LEFT(projects_status_history.added, 4)
                            END AS date_range
                        FROM projects_status_history
                            INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                        WHERE projects_status.status = ' . \projects_status::REMBOURSEMENT . '
                            AND echeanciers_emprunteur.id_project = projects_status_history.id_project
                        ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
                      ) AS cohort
                    FROM echeanciers_emprunteur
                      INNER JOIN projects ON echeanciers_emprunteur.id_project = projects.id_project AND projects.status >= 80
                    WHERE
                        (
                        SELECT lender_payment_status.status
                        FROM echeanciers lender_payment_status
                        WHERE lender_payment_status.ordre = echeanciers_emprunteur.ordre
                          AND echeanciers_emprunteur.id_project = lender_payment_status.id_project
                        LIMIT 1) = 0
                    AND (
                        SELECT lender_payment_date.date_echeance
                        FROM echeanciers lender_payment_date
                        WHERE lender_payment_date.ordre = echeanciers_emprunteur.ordre
                          AND echeanciers_emprunteur.id_project = lender_payment_date.id_project
                        LIMIT 1) >= NOW()
                    AND IF(
                            projects.status IN ('. implode(',', [\projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::DEFAUT]).')
                            OR (projects.status IN ('. implode(',', [\projects_status::PROBLEME, \projects_status::PROBLEME_J_X, \projects_status::RECOUVREMENT]).')
                                AND DATEDIFF(NOW(),(
                                        SELECT psh2.added
                                        FROM projects_status_history psh2
                                          INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                                        WHERE ps2.status = ' . \projects_status::PROBLEME . '
                                          AND psh2.id_project = echeanciers_emprunteur.id_project
                                        ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                                        LIMIT 1)) > 180), TRUE, FALSE) = TRUE
                    GROUP BY cohort';

        $statement = $this->bdd->executeQuery($query);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLateCapitalRepaymentsProblematicProjects()
    {
        $query = 'SELECT
                      SUM(echeanciers_emprunteur.capital)/100 AS amount,
                      (
                        SELECT
                          CASE LEFT(projects_status_history.added, 4)
                            WHEN 2013 THEN "2013-2014"
                            WHEN 2014 THEN "2013-2014"
                            ELSE LEFT(projects_status_history.added, 4)
                          END AS date_range
                        FROM projects_status_history
                        INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                        WHERE  projects_status.status = '. \projects_status::REMBOURSEMENT .'
                          AND echeanciers_emprunteur.id_project = projects_status_history.id_project
                        ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
                      ) AS cohort
                    FROM echeanciers_emprunteur
                      INNER JOIN projects ON echeanciers_emprunteur.id_project = projects.id_project AND projects.status >= '. \projects_status::REMBOURSEMENT .'
                    WHERE
                        (
                        SELECT lender_payment_status.status
                        FROM echeanciers lender_payment_status
                        WHERE lender_payment_status.ordre = echeanciers_emprunteur.ordre
                          AND echeanciers_emprunteur.id_project = lender_payment_status.id_project
                        LIMIT 1) = 0
                    AND
                        (
                        SELECT lender_payment_date.date_echeance
                        FROM echeanciers lender_payment_date
                        WHERE lender_payment_date.ordre = echeanciers_emprunteur.ordre
                          AND echeanciers_emprunteur.id_project = lender_payment_date.id_project
                        LIMIT 1) < NOW()
                    AND
                        IF(
                           (projects.status IN ('. implode(',', [\projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::DEFAUT]).')
                                OR (projects.status IN ('. implode(',', [\projects_status::PROBLEME, \projects_status::PROBLEME_J_X, \projects_status::RECOUVREMENT]).')
                                    AND DATEDIFF(NOW(),(
                                                        SELECT psh2.added
                                                        FROM projects_status_history psh2
                                                          INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                                                        WHERE ps2.status = ' . \projects_status::PROBLEME . '
                                                          AND psh2.id_project = echeanciers_emprunteur.id_project
                                                        ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                                                        LIMIT 1)) > 180)), TRUE, FALSE) = TRUE
                    GROUP BY cohort';

        $statement = $this->bdd->executeQuery($query);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLateCapitalRepaymentsHealthyProjects()
    {
        $query = 'SELECT
                      SUM(echeanciers_emprunteur.capital)/100 AS amount,
                      (
                        SELECT
                          CASE LEFT(projects_status_history.added, 4)
                            WHEN 2013 THEN "2013-2014"
                            WHEN 2014 THEN "2013-2014"
                            ELSE LEFT(projects_status_history.added, 4)
                          END AS date_range
                        FROM projects_status_history
                        INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                        WHERE  projects_status.status = '. \projects_status::REMBOURSEMENT .'
                          AND echeanciers_emprunteur.id_project = projects_status_history.id_project
                        ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
                      ) AS cohort
                    FROM echeanciers_emprunteur
                      INNER JOIN projects ON echeanciers_emprunteur.id_project = projects.id_project AND projects.status >= '. \projects_status::REMBOURSEMENT .'
                    WHERE
                        (
                        SELECT lender_payment_status.status
                        FROM echeanciers lender_payment_status
                        WHERE lender_payment_status.ordre = echeanciers_emprunteur.ordre
                          AND echeanciers_emprunteur.id_project = lender_payment_status.id_project
                        LIMIT 1) = 0
                      AND
                        (
                        SELECT lender_payment_date.date_echeance
                        FROM echeanciers lender_payment_date
                        WHERE lender_payment_date.ordre = echeanciers_emprunteur.ordre
                          AND echeanciers_emprunteur.id_project = lender_payment_date.id_project
                        LIMIT 1) < NOW()
                      AND IF(
                            projects.status IN ('. implode(',', [\projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::DEFAUT]).')
                            OR (projects.status IN ('. implode(',', [\projects_status::PROBLEME, \projects_status::PROBLEME_J_X, \projects_status::RECOUVREMENT]).')
                                AND DATEDIFF(NOW(), (
                                                    SELECT psh2.added
                                                    FROM projects_status_history psh2
                                                      INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                                                    WHERE ps2.status = ' . \projects_status::PROBLEME . '
                                                      AND psh2.id_project = echeanciers_emprunteur.id_project
                                                    ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                                                    LIMIT 1)) > 180), TRUE, FALSE) = FALSE
                    GROUP BY cohort';

        $statement = $this->bdd->executeQuery($query);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
