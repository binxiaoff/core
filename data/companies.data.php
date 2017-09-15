<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus;

class companies extends companies_crud
{

    public function __construct($bdd, $params = '')
    {
        parent::companies($bdd, $params);
    }

    public function create($cs = '')
    {
        $this->setSectorAccordingToNaf();

        if (is_numeric($this->name) || 0 === strcasecmp($this->name, 'Monsieur') || 0 === strcasecmp($this->name, 'Madame')) {
            trigger_error('An invalid company name "' . $this->name . '" detected for siren : ' . $this->siren . ' during the creation - trace : ' . serialize(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)), E_USER_WARNING);
        }

        return parent::create($cs);
    }

    public function update($cs = '')
    {
        $this->setSectorAccordingToNaf();

        if (is_numeric($this->name) || 0 === strcasecmp($this->name, 'Monsieur') || 0 === strcasecmp($this->name, 'Madame')) {
            trigger_error('An invalid company name "' . $this->name . '" detected for siren : ' . $this->siren . ' during the updating - trace : ' . serialize(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)), E_USER_WARNING);
        }

        parent::update($cs);
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

        $result = $this->bdd->query('SELECT COUNT(*) FROM `companies` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_company')
    {
        $result = $this->bdd->query('SELECT * FROM `companies` WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_assoc($result) > 0);
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
            $sStatus = ' AND status IN (' . implode(',', $aStatus) . ')';
        } else {
            $sStatus = '';
        }

        $sql = '
            SELECT *
            FROM projects
            WHERE id_company = ' . $iCompanyId . '
            ' . $sStatus . '
            ORDER BY status DESC, date_retrait DESC, added DESC';

        $resultat  = $this->bdd->query($sql);
        $aProjects = array();
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $aProjects[] = $record;
        }
        return $aProjects;
    }

    /**
     * Retrieve the amount company still needs to pay to Unilend
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
     * @param int $siren
     * @return float
     */
    public function getLastYearReleasedFundsBySIREN($siren)
    {
        $query     = '
            SELECT IFNULL(SUM(o.amount), 0)
            FROM operation o
              INNER JOIN wallet w ON w.id = o.id_wallet_creditor
              INNER JOIN companies c ON c.id_client_owner = w.id_client
            WHERE o.added > DATE_SUB(NOW(), INTERVAL 1 YEAR)
                  AND c.siren = :siren
                  AND o.id_type = (SELECT id FROM operation_type WHERE label = \'' . OperationType::LENDER_LOAN . '\')';
        $statement = $this->bdd->executeQuery($query, ['siren' => $siren]);
        return (float) $statement->fetchColumn();
    }

    /**
     * @return array
     */
    public function getProjectsBySIREN()
    {
        if (empty($this->id_company)) {
            return [];
        }
        $projects = [];
        $result   = $this->bdd->query('
            SELECT 1 AS rank, p.id_project, p.slug, p.id_company, p.amount, p.period, p.title, p.added, p.updated, ps.label AS status_label, p.status, IFNULL(CONCAT(sales_person.firstname, " ", sales_person.name), "") AS sales_person, IFNULL(CONCAT(analysts.firstname, " ", analysts.name), "") AS analyst
            FROM companies current_company
            INNER JOIN companies c ON current_company.siren = c.siren
            INNER JOIN projects p ON c.id_company = p.id_company
            INNER JOIN projects_status ps ON ps.status = p.status
            LEFT JOIN users sales_person ON p.id_commercial = sales_person.id_user
            LEFT JOIN users analysts ON p.id_analyste = analysts.id_user
            WHERE p.status >= ' . \projects_status::EN_FUNDING . ' AND current_company.id_company = ' . $this->id_company . '

            UNION

            SELECT 2 AS rank, p.id_project, p.slug, p.id_company, p.amount, p.period, p.title, p.added, p.updated, ps.label AS status_label, p.status, IFNULL(CONCAT(sales_person.firstname, " ", sales_person.name), "") AS sales_person, IFNULL(CONCAT(analysts.firstname, " ", analysts.name), "") AS analyst
            FROM companies current_company
            INNER JOIN companies c ON current_company.siren = c.siren
            INNER JOIN projects p ON c.id_company = p.id_company
            INNER JOIN projects_status ps ON ps.status = p.status
            LEFT JOIN users sales_person ON p.id_commercial = sales_person.id_user
            LEFT JOIN users analysts ON p.id_analyste = analysts.id_user
            WHERE p.status >= ' . \projects_status::COMMERCIAL_REVIEW . ' AND p.status < ' . \projects_status::EN_FUNDING . ' AND current_company.id_company = ' . $this->id_company . '

            UNION

            SELECT 3 AS rank, p.id_project, p.slug, p.id_company, p.amount, p.period, p.title, p.added, p.updated, ps.label AS status_label, p.status, IFNULL(CONCAT(sales_person.firstname, " ", sales_person.name), "") AS sales_person, IFNULL(CONCAT(analysts.firstname, " ", analysts.name), "") AS analyst
            FROM companies current_company
            INNER JOIN companies c ON current_company.siren = c.siren
            INNER JOIN projects p ON c.id_company = p.id_company
            INNER JOIN projects_status ps ON ps.status = p.status
            LEFT JOIN users sales_person ON p.id_commercial = sales_person.id_user
            LEFT JOIN users analysts ON p.id_analyste = analysts.id_user
            WHERE p.status < ' . \projects_status::COMMERCIAL_REVIEW . ' AND current_company.id_company = ' . $this->id_company . '
            ORDER BY rank ASC, added DESC'
        );
        while ($record = $this->bdd->fetch_assoc($result)) {
            $projects[] = $record;
        }
        return $projects;
    }

    /**
     * @param string $sName
     * @return array
     */
    public function searchByName($sName)
    {
        $sQuery = '
            SELECT DISTINCT(name)
            FROM companies
            WHERE name LIKE "%' . $sName . '%"
            ORDER BY name ASC';

        $aNames  = array();
        $rResult = $this->bdd->query($sQuery);

        while ($aRow = $this->bdd->fetch_assoc($rResult)) {
            $aNames[] = $aRow['name'];
        }

        return $aNames;
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

    /**
     * sets the company sector according to the naf_code
     * matching provided in DEV-273
     */
    public function setSectorAccordingToNaf()
    {
        if ($this->code_naf == \Unilend\Bundle\CoreBusinessBundle\Entity\Companies::NAF_CODE_NO_ACTIVITY) {
            return;
        }

        if (in_array(substr($this->code_naf, 0, 2), ['01', '02', '03'])) {
            $this->sector = 1;
        }

        if (in_array(substr($this->code_naf, 0, 2), ['10', '11'])) {
            $this->sector = 2;
        }

        if (in_array(substr($this->code_naf, 0, 2), ['41', '42', '43', '71'])) {
            $this->sector = 3;
        }

        if (in_array(substr($this->code_naf, 0, 2), ['45', '46', '47', '95'])) {
            $this->sector = 4;
        }

        if (in_array(substr($this->code_naf, 0, 2), ['59', '60', '90', '91'])) {
            $this->sector = 6;
        }

        if (in_array(substr($this->code_naf, 0, 2), ['55'])) {
            $this->sector = 7;
        }

        if (in_array(substr($this->code_naf, 0, 2), ['16', '17', '18', '19', '20', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '35', '36'])) {
            $this->sector = 8;
        }

        if (in_array(substr($this->code_naf, 0, 2), ['61', '62', '63'])) {
            $this->sector = 9;
        }

        if (in_array(substr($this->code_naf, 0, 2), ['21', '75', '86'])) {
            $this->sector = 10;
        }

        if (in_array(substr($this->code_naf, 0, 2), ['56'])) {
            $this->sector = 11;
        }

        if (in_array(substr($this->code_naf, 0, 2), ['58', '65', '66', '68', '69', '70', '73', '74', '77', '78', '79', '80', '81', '82', '96', '97'])) {
            $this->sector = 12;
        }

        if (in_array(substr($this->code_naf, 0, 2), ['13', '14', '15'])) {
            $this->sector = 13;
        }

        if (in_array(substr($this->code_naf, 0, 2), ['49', '50', '51', '52', '53'])) {
            $this->sector = 14;
        }

        if (in_array(substr($this->code_naf, 0, 2), ['05', '06', '07', '08', '09', '12', '37', '38', '39', '64', '72', '84', '85', '87', '88', '92', '93', '94', '98', '99'])) {
            $this->sector = 15;
        }
    }

    public function countCompaniesWithProblematicProjectsByCohort()
    {
        $caseSql  = '';
        foreach (range(2015, date('Y')) as $year ) {
            $caseSql .= ' WHEN ' . $year . ' THEN "' . $year . '"';
        }

        $query = '
            SELECT COUNT(DISTINCT projects.id_company) AS amount,
               (
                 SELECT
                   CASE LEFT(projects_status_history.added, 4)
                   WHEN 2013 THEN "2013-2014"
                   WHEN 2014 THEN "2013-2014"
                   ELSE LEFT(projects_status_history.added, 4)
                   END AS date_range
                 FROM projects_status_history
                   INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                 WHERE  projects_status.status = ' . \projects_status::REMBOURSEMENT . '
                        AND projects.id_project = projects_status_history.id_project
                 ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
               ) AS cohort
            FROM projects
               INNER JOIN companies c ON c.id_company = projects.id_company
               INNER JOIN company_status cs ON cs.id = c.id_status 
            WHERE projects.status >= ' . ProjectsStatus::REMBOURSEMENT . '
               AND cs.label IN ("' . implode('", "', [CompanyStatus::STATUS_COMPULSORY_LIQUIDATION, CompanyStatus::STATUS_PRECAUTIONARY_PROCESS, CompanyStatus::STATUS_RECEIVERSHIP]) . '")
               OR
               (projects.status = ' . ProjectsStatus::PROBLEME . ' AND
                DATEDIFF(NOW(),
                         (
                          SELECT psh2.added
                          FROM projects_status_history psh2
                            INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                          WHERE
                            ps2.status = ' . ProjectsStatus::PROBLEME . '
                            AND psh2.id_project = projects.id_project
                          ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                          LIMIT 1
                         )
                ) > 180)
            GROUP BY cohort';

        $statement = $this->bdd->executeQuery($query);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }


    public function countCompaniesFundedByCohort()
    {
        $query = 'SELECT COUNT(DISTINCT id_company) AS amount,
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
                          AND projects.id_project = projects_status_history.id_project
                        ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
                      ) AS cohort
                       FROM projects
                    WHERE projects.status >= ' . \projects_status::REMBOURSEMENT . '
                    GROUP BY cohort';

        $statement = $this->bdd->executeQuery($query);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int $siren
     * @return array
     */
    public function searchCompanyBySIREN($siren)
    {
        $statement = $this->bdd->createQueryBuilder()
            ->select('c.*')
            ->from('companies', 'c')
            ->innerJoin('c', 'projects', 'p', 'c.id_company = p.id_company')
            ->where('c.siren = :siren')
            ->setParameter('siren', $siren)
            ->orderBy('p.status', 'DESC')
            ->orderBy('c.added', 'ASC')
            ->groupBy('c.id_company')
            ->execute();

        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $result;
    }
}
