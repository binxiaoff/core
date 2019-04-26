<?php

use Doctrine\DBAL\Connection;
use Unilend\Entity\{CompanyStatus, ProjectsStatus};

class companies extends companies_crud
{

    public function __construct($bdd, $params = '')
    {
        parent::__construct($bdd, $params);
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
          SELECT CASE
            WHEN p.close_out_netting_date IS NOT NULL AND p.close_out_netting_date != \'0000-00-00\' 
              THEN conp.capital - conp.paid_capital
              ELSE IFNULL(ROUND(SUM(ee.capital - ee.paid_capital) / 100, 2), 0)
            END
          FROM echeanciers_emprunteur ee
          INNER JOIN projects p ON ee.id_project = p.id_project
          INNER JOIN companies c ON p.id_company = c.id_company
          LEFT JOIN close_out_netting_payment conp ON p.id_project = conp.id_project
            WHERE c.siren =  "' . $this->siren . '"'
        ));
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
            SELECT 1 AS `rank`, p.id_project, p.slug, p.id_company, p.amount, p.period, p.title, p.added, p.updated, ps.label AS status_label, p.status, IFNULL(CONCAT(sales_person.firstname, " ", sales_person.name), "") AS sales_person, IFNULL(CONCAT(analysts.firstname, " ", analysts.name), "") AS analyst
            FROM companies current_company
            INNER JOIN companies c ON current_company.siren = c.siren
            INNER JOIN projects p ON c.id_company = p.id_company
            INNER JOIN projects_status ps ON ps.status = p.status
            LEFT JOIN users sales_person ON p.id_commercial = sales_person.id_user
            LEFT JOIN users analysts ON p.id_analyste = analysts.id_user
            WHERE p.status >= ' . ProjectsStatus::STATUS_PUBLISHED . ' AND current_company.id_company = ' . $this->id_company . '

            UNION

            SELECT 2 AS `rank`, p.id_project, p.slug, p.id_company, p.amount, p.period, p.title, p.added, p.updated, ps.label AS status_label, p.status, IFNULL(CONCAT(sales_person.firstname, " ", sales_person.name), "") AS sales_person, IFNULL(CONCAT(analysts.firstname, " ", analysts.name), "") AS analyst
            FROM companies current_company
            INNER JOIN companies c ON current_company.siren = c.siren
            INNER JOIN projects p ON c.id_company = p.id_company
            INNER JOIN projects_status ps ON ps.status = p.status
            LEFT JOIN users sales_person ON p.id_commercial = sales_person.id_user
            LEFT JOIN users analysts ON p.id_analyste = analysts.id_user
            WHERE p.status >= ' . ProjectsStatus::STATUS_REQUESTED . ' AND p.status < ' . ProjectsStatus::STATUS_PUBLISHED . ' AND current_company.id_company = ' . $this->id_company . '

            UNION

            SELECT 3 AS `rank`, p.id_project, p.slug, p.id_company, p.amount, p.period, p.title, p.added, p.updated, ps.label AS status_label, p.status, IFNULL(CONCAT(sales_person.firstname, " ", sales_person.name), "") AS sales_person, IFNULL(CONCAT(analysts.firstname, " ", analysts.name), "") AS analyst
            FROM companies current_company
            INNER JOIN companies c ON current_company.siren = c.siren
            INNER JOIN projects p ON c.id_company = p.id_company
            INNER JOIN projects_status ps ON ps.status = p.status
            LEFT JOIN users sales_person ON p.id_commercial = sales_person.id_user
            LEFT JOIN users analysts ON p.id_analyste = analysts.id_user
            WHERE p.status < ' . ProjectsStatus::STATUS_REQUESTED . ' AND current_company.id_company = ' . $this->id_company . '
            ORDER BY `rank` ASC, added DESC'
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

    /**
     * sets the company sector according to the naf_code
     * matching provided in DEV-273
     */
    public function setSectorAccordingToNaf()
    {
        if ($this->code_naf == \Unilend\Entity\Companies::NAF_CODE_NO_ACTIVITY) {
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

    /**
     * @param bool $groupFirstYears
     *
     * @return mixed
     */
    public function countCompaniesWithProblematicProjectsByCohort($groupFirstYears = true)
    {
        if ($groupFirstYears) {
            $cohortSelect = 'CASE LEFT(projects_status_history.added, 4)
                               WHEN 2013 THEN "2013-2014"
                               WHEN 2014 THEN "2013-2014"
                               ELSE LEFT(projects_status_history.added, 4)
                             END';
        } else {
            $cohortSelect = 'LEFT(projects_status_history.added, 4)';
        }

        $query = '
            SELECT COUNT(DISTINCT projects.id_company) AS amount,
               (
                 SELECT ' . $cohortSelect . ' AS date_range
                 FROM projects_status_history
                   INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                 WHERE  projects_status.status = ' . ProjectsStatus::STATUS_CONTRACTS_SIGNED . '
                        AND projects.id_project = projects_status_history.id_project
                 ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
               ) AS cohort
            FROM projects
               INNER JOIN companies c ON c.id_company = projects.id_company
               INNER JOIN company_status cs ON cs.id = c.id_status 
            WHERE projects.status IN (:projectStatus)
               AND cs.label IN (:companyStatus)
               OR
               (projects.status = ' . ProjectsStatus::STATUS_LOST . ' AND
                DATEDIFF(NOW(),
                         (
                          SELECT psh2.added
                          FROM projects_status_history psh2
                            INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                          WHERE
                            ps2.status = ' . ProjectsStatus::STATUS_LOST . '
                            AND psh2.id_project = projects.id_project
                          ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                          LIMIT 1
                         )
                ) > ' . \Unilend\Entity\UnilendStats::DAYS_AFTER_LAST_PROBLEM_STATUS_FOR_STATISTIC_LOSS . ' )
            GROUP BY cohort';

        $statement = $this->bdd->executeQuery(
            $query,
            [
                'companyStatus' => [CompanyStatus::STATUS_PRECAUTIONARY_PROCESS, CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION],
                'projectStatus' => [ProjectsStatus::STATUS_CONTRACTS_SIGNED, ProjectsStatus::STATUS_LOST]
            ],
            [
                'companyStatus' => Connection::PARAM_STR_ARRAY,
                'projectStatus' => Connection::PARAM_INT_ARRAY
            ]
        );
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * @param bool $groupFirstYears
     *
     * @return mixed
     */
    public function countCompaniesFundedByCohort($groupFirstYears = true)
    {
        if ($groupFirstYears) {
            $cohortSelect = 'CASE LEFT(projects_status_history.added, 4)
                               WHEN 2013 THEN "2013-2014"
                               WHEN 2014 THEN "2013-2014"
                               ELSE LEFT(projects_status_history.added, 4)
                             END';
        } else {
            $cohortSelect = 'LEFT(projects_status_history.added, 4)';
        }

        $query = 'SELECT COUNT(DISTINCT id_company) AS amount,
                    (
                        SELECT ' . $cohortSelect . ' AS date_range
                        FROM projects_status_history
                        INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                        WHERE  projects_status.status = ' . ProjectsStatus::STATUS_CONTRACTS_SIGNED . '
                          AND projects.id_project = projects_status_history.id_project
                        ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
                      ) AS cohort
                       FROM projects
                    WHERE projects.status >= ' . ProjectsStatus::STATUS_CONTRACTS_SIGNED . '
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
