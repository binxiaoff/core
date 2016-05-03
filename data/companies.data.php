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

class companies extends companies_crud
{
    const CLIENT_STATUS_MANAGER             = 1;
    const CLIENT_STATUS_DELEGATION_OF_POWER = 2;
    const CLIENT_STATUS_EXTERNAL_CONSULTANT = 3;

    public function __construct($bdd, $params = '')
    {
        parent::companies($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `companies`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $result = $this->bdd->query('SELECT COUNT(*) FROM `companies` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_company')
    {
        $result = $this->bdd->query('SELECT * FROM `companies` WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_array($result) > 0);
    }

    /**
     * gets all projects for one company with our without status
     * @param null|int $iCompanyId
     * @param null|array $aStatus
     * @return array
     */
    public function getProjectsForCompany($iCompanyId = null, $aStatus = null)
    {
        if (null === $iCompanyId) {
            $iCompanyId = $this->id_company;
        }

        if (isset($aStatus)) {
            $sStatus = (is_array($aStatus))? ' AND ps.status IN ('.implode(',', $aStatus).')': ' AND ps.status IN ('.$aStatus.')';
        } else {
            $sStatus = '';
        }

        $sql = '
            SELECT
                p.*,
                ps.status as project_status
            FROM projects p
            INNER JOIN projects_last_status_history plsh ON plsh.id_project = p.id_project
            INNER JOIN projects_status_history psh ON psh.id_project_status_history = plsh.id_project_status_history
            INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
            WHERE p.id_company = '.$iCompanyId.$sStatus.'
            ORDER BY project_status DESC';

        $resultat  = $this->bdd->query($sql);
        $aProjects = array();
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $aProjects[] = $record;
        }
        return $aProjects;
    }

    /**
     * Retrieve the amount company still needs to pay to Unilend
     * @param int $iCompanyId
     * @return float
     */
    public function getOwedCapitalBySIREN()
    {
        if (empty($this->id_company)) {
            return 0.0;
        }
        return (float) $this->bdd->result($this->bdd->query('
            SELECT IFNULL(SUM(ee.capital) / 100, 0)
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            INNER JOIN companies c ON p.id_company = c.id_company
            WHERE ee.status_emprunteur = 0 AND c.siren = "' . $this->siren . '"'
        ));
    }

    /**
     * @return array
     */
    public function getProjectsBySIREN()
    {
        if (empty($this->id_company)) {
            return array();
        }
        $aProjects = array();
        $rResult   = $this->bdd->query('
            SELECT 1 AS rank, p.id_project, p.slug, p.id_company, p.amount, p.period, p.title, p.added, p.updated, ps.label AS status_label, ps.status, IFNULL(CONCAT(sales_person.firstname, " ", sales_person.name), "") AS sales_person, IFNULL(CONCAT(analysts.firstname, " ", analysts.name), "") AS analyst
            FROM companies current_company
            INNER JOIN companies c ON current_company.siren = c.siren
            INNER JOIN projects p ON c.id_company = p.id_company
            INNER JOIN projects_last_status_history plsh ON plsh.id_project = p.id_project
            INNER JOIN projects_status_history psh ON psh.id_project_status_history = plsh.id_project_status_history
            INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
            LEFT JOIN users sales_person ON p.id_commercial = sales_person.id_user
            LEFT JOIN users analysts ON p.id_analyste = analysts.id_user
            WHERE ps.status >= ' . \projects_status::EN_FUNDING . ' AND current_company.id_company = ' . $this->id_company . '

            UNION

            SELECT 2 AS rank, p.id_project, p.slug, p.id_company, p.amount, p.period, p.title, p.added, p.updated, ps.label AS status_label, ps.status, IFNULL(CONCAT(sales_person.firstname, " ", sales_person.name), "") AS sales_person, IFNULL(CONCAT(analysts.firstname, " ", analysts.name), "") AS analyst
            FROM companies current_company
            INNER JOIN companies c ON current_company.siren = c.siren
            INNER JOIN projects p ON c.id_company = p.id_company
            INNER JOIN projects_last_status_history plsh ON plsh.id_project = p.id_project
            INNER JOIN projects_status_history psh ON psh.id_project_status_history = plsh.id_project_status_history
            INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
            LEFT JOIN users sales_person ON p.id_commercial = sales_person.id_user
            LEFT JOIN users analysts ON p.id_analyste = analysts.id_user
            WHERE ps.status >= ' . \projects_status::EN_ATTENTE_PIECES . ' AND ps.status < ' . \projects_status::EN_FUNDING . ' AND current_company.id_company = ' . $this->id_company . '

            UNION

            SELECT 3 AS rank, p.id_project, p.slug, p.id_company, p.amount, p.period, p.title, p.added, p.updated, ps.label AS status_label, ps.status, IFNULL(CONCAT(sales_person.firstname, " ", sales_person.name), "") AS sales_person, IFNULL(CONCAT(analysts.firstname, " ", analysts.name), "") AS analyst
            FROM companies current_company
            INNER JOIN companies c ON current_company.siren = c.siren
            INNER JOIN projects p ON c.id_company = p.id_company
            INNER JOIN projects_last_status_history plsh ON plsh.id_project = p.id_project
            INNER JOIN projects_status_history psh ON psh.id_project_status_history = plsh.id_project_status_history
            INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
            LEFT JOIN users sales_person ON p.id_commercial = sales_person.id_user
            LEFT JOIN users analysts ON p.id_analyste = analysts.id_user
            WHERE ps.status < ' . \projects_status::EN_ATTENTE_PIECES . ' AND current_company.id_company = ' . $this->id_company . '
            ORDER BY rank ASC, added DESC'
        );
        while ($aRecord = $this->bdd->fetch_assoc($rResult)) {
            $aProjects[] = $aRecord;
        }
        return $aProjects;
    }

    /**
     * @return bool
     */
    public function countProblemsBySIREN()
    {
        if (empty($this->id_company)) {
            return 0;
        }

        $aStatuses = array(
            \projects_status::PROBLEME,
            \projects_status::PROBLEME_J_X,
            \projects_status::RECOUVREMENT,
            \projects_status::PROCEDURE_SAUVEGARDE,
            \projects_status::REDRESSEMENT_JUDICIAIRE,
            \projects_status::LIQUIDATION_JUDICIAIRE,
            \projects_status::DEFAUT
        );
        return (int) $this->bdd->result($this->bdd->query('
            SELECT COUNT(*)
            FROM companies current_company
            INNER JOIN companies c ON current_company.siren = c.siren
            INNER JOIN projects p ON c.id_company = p.id_company
            INNER JOIN projects_status_history psh ON p.id_project = psh.id_project
            INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
            WHERE ps.status IN (' . implode(', ', $aStatuses) . ')
                AND current_company.id_company = ' . $this->id_company
        ));
    }

    public function getCompaniesSalesForce()
    {
        $sQuery = "SELECT
                    co.id_company AS 'IDCompany',
                    REPLACE(REPLACE(co.siren,',',''),'t','') AS 'Siren',
                    CASE REPLACE(co.name,',','')
                      WHEN '' THEN 'A renseigner'
                      ELSE REPLACE(co.name,',','')
                    END AS 'RaisonSociale',
                    REPLACE(co.adresse1,',','') AS 'Adresse1',
                    REPLACE(co.zip,',','') AS 'CP',
                    REPLACE(co.city,',','') AS 'Ville',
                    acountry.fr AS 'Pays',
                    REPLACE(co.email_facture,',','') AS 'EmailFacturation',
                    co.id_client_owner AS 'IDClient',
                    co.forme as 'FormeSociale',
                    '012240000002G4U' as 'Sfcompte'
                  FROM
                    companies co
                  LEFT JOIN
                    pays_v2 acountry ON (co.id_pays = acountry.id_pays)";

        return $this->bdd->executeQuery($sQuery);
    }
}
