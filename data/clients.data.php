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

class clients extends clients_crud
{
    const OCTROI_FINANCMENT           = 1;
    const VIREMENT                    = 2;
    const COMMISSION_DEBLOCAGE        = 3;
    const PRLV_MENSUALITE             = 4;
    const AFF_MENSUALITE_PRETEURS     = 5;
    const COMMISSION_MENSUELLE        = 6;
    const REMBOURSEMENT_ANTICIPE      = 7;
    const AFFECTATION_RA_PRETEURS     = 8;

    const TYPE_PERSON                 = 1;
    const TYPE_LEGAL_ENTITY           = 2;
    const TYPE_PERSON_FOREIGNER       = 3;
    const TYPE_LEGAL_ENTITY_FOREIGNER = 4;

    const STATUS_OFFLINE = 0;
    const STATUS_ONLINE  = 1;

    public function __construct($bdd, $params = '')
    {
        parent::clients($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `clients`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `clients` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_client')
    {
        $sql    = 'SELECT * FROM `clients` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    //******************************************************************************************//
    //**************************************** AJOUTS ******************************************//
    //******************************************************************************************//

    public $loginPage = '';
    public $connectedPage = '';
    public $userTable = 'clients';
    public $securityKey = 'clients';
    public $userMail = 'email';
    public $userPass = 'password';

    //TODO delete all login and check access functions no longer needed

    /**
     * @param DateTime $dateLogin
     * @param string   $email
     */
    public function saveLogin(\DateTime $dateLogin)
    {
        if (false === empty($this->id_client) && is_numeric($this->id_client)){
            $bind = ['lastLogin' => $dateLogin->format('Y-m-d H:i:s'), 'id_client' => $this->id_client];
            $type = ['lastLogin' => \PDO::PARAM_STR, 'id_client' => \PDO::PARAM_STR];

            $query =  '
            UPDATE clients
            SET lastlogin = :lastLogin
            WHERE id_client = :id_client';
            $this->bdd->executeUpdate($query, $bind, $type);
        }
    }

    public function handleLogout($bRedirect = true)
    {
        unset($_SESSION['auth']);
        unset($_SESSION['token']);
        unset($_SESSION['client']);
        unset($_SESSION['panier']);
        unset($_SESSION['partenaire']);

        if ($bRedirect) {
            header('Location: http://' . $_SERVER['HTTP_HOST'] . '/' . (isset($this->params['lng']) ? $this->params['lng'] : '') . $this->loginPage);
        }
    }

    /**
     * @param string $email
     * @param string $pass
     * @return bool|array
     */
    public function login($email, $pass)
    {
        $email = $this->bdd->escape_string($email);
        $sql   = 'SELECT * FROM ' . $this->userTable . ' WHERE ' . $this->userMail . ' = "' . $email . '" AND status = 1';
        $res   = $this->bdd->query($sql);

        if ($res->rowCount() === 1) {
            $client = $res->fetch(\PDO::FETCH_ASSOC);

            if (md5($pass) === $client['password'] || password_verify($pass, $client['password'])) {
                return $client;
            }
        }
        return false;
    }

    public function changePassword($email, $pass)
    {
        $this->bdd->query('
            UPDATE ' . $this->userTable . '
            SET ' . $this->userPass . ' = "' . password_hash($pass, PASSWORD_DEFAULT) . '"
            WHERE ' . $this->userMail . ' = "' . $email . '"'
        );
    }

    public function existEmail($email)
    {
        $sql = 'SELECT * FROM ' . $this->userTable . ' WHERE ' . $this->userMail . ' = "' . $email . '"';
        $res = $this->bdd->query($sql);

        return ($this->bdd->num_rows($res) >= 1);
    }

    public function checkAccess()
    {
        if (! isset($_SESSION['auth']) || $_SESSION['auth'] != true) {
            return false;
        }

        if (trim($_SESSION['token']) == '') {
            return false;
        }

        $sql = 'SELECT COUNT(*) FROM ' . $this->userTable . ' WHERE id_client = "' . $_SESSION['client']['id_client'] . '" AND password = "' . $_SESSION['client']['password'] . '" AND status = 1';
        $res = $this->bdd->query($sql);

        if ($this->bdd->result($res, 0) != 1) {
            return false;
        } else {
            return true;
        }
    }

    public function getLastStatut($id_client)
    {
        $sql = 'SELECT id_client_status
                FROM `clients_status_history`
                WHERE id_client = ' . $id_client . '
                ORDER BY added DESC
                LIMIT 1';
        $result           = $this->bdd->query($sql);
        $id_client_status = (int) ($this->bdd->result($result, 0, 0));

        if ($id_client_status == 6) {
            return true;
        } else {
            return false;
        }
    }

    public function checkCompteCreate($id_client)
    {
        $sql    = 'SELECT count(*)
                FROM `clients_status_history`
                WHERE id_client = ' . $id_client;
        $result = $this->bdd->query($sql);
        $nb     = (int) ($this->bdd->result($result, 0, 0));

        if ($nb > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function checkAccessLender()
    {
        if ($this->isLender()) {
            if (false === $this->checkCompteCreate($this->id_client)) {
                header('location:' . $this->lurl . '/inscription-preteurs');
                die;
            }
        } else {
            $this->handleLogout();
        }
    }

    public function checkAccessBorrower()
    {
        if (false === $this->isBorrower()) {
            $this->handleLogout();
        }
    }

    public function searchEmprunteurs($ref = '', $nom = '', $email = '', $prenom = '', $societe = '', $siret = '', $status = '', $start = '', $nb = '')
    {
        $where = '1 = 1';

        if ($ref != '') {
            $where .= ' AND c.id_client IN(' . $ref . ')';
        }
        if ($nom != '') {
            $where .= ' AND c.nom LIKE "%' . $nom . '%"';
        }
        if ($email != '') {
            $where .= ' AND c.email LIKE "%' . $email . '%"';
        }
        if ($prenom != '') {
            $where .= ' AND c.prenom LIKE "%' . $prenom . '%"';
        }
        if ($societe != '') {
            $where .= ' AND co.name LIKE "%' . $societe . '%"';
        }
        if ($siret != '') {
            $where .= ' AND co.siren LIKE "%' . $siret . '%"';
        }
        if ($status != '') {
            $where .= ' AND c.status LIKE "%' . $status . '%"';
        }

        $result   = [];
        $resultat = $this->bdd->query('
            SELECT c.*,
                co.*
            FROM clients c
              INNER JOIN companies co ON c.id_client = co.id_client_owner
            WHERE ' . $where . '
              AND c.type NOT IN (' . implode(',', [\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER, \clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER]) .')
            GROUP BY c.id_client
            ORDER BY c.id_client DESC' . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''))
        );

        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function totalmontantEmprunt($id_client)
    {
        // Récupération du totel montant emprunt d'un client
        $sql    = 'SELECT SUM(p.amount) as total FROM clients c,companies co,projects p WHERE c.id_client = co.id_client_owner AND co.id_company = p.id_company AND c.id_client = ' . $id_client;
        $result = $this->bdd->query($sql);

        return $this->bdd->result($result, 0, 0);
    }

    public function searchPreteurs($ref = '', $nom = '', $email = '', $prenom = '', $name = '', $noValide = '', $start = '', $nb = '')
    {
        $where = 'WHERE 1 = 1 ';
        $and   = '';
        if ($ref != '') {
            $and .= ' AND c.id_client IN(' . $ref . ')';
        }
        if ($email != '') {
            $and .= ' AND c.email LIKE "' . $email . '%"';
        }
        if ($prenom != '') {
            $and .= ' AND c.prenom LIKE "' . $prenom . '%"';
        }
        if ($name != '') {
            $and .= ' AND co.name LIKE "' . $name . '%"';
        }

        if ($noValide == '1') {
            $and .= ' AND c.status = 0 AND c.status_inscription_preteur = 1';
        } // inscription non terminée
        elseif ($noValide == '2') {
            $and .= ' AND c.status = 0 AND c.status_inscription_preteur = 0';
        } else {
            $and .= ' AND YEAR(NOW()) - YEAR(c.naissance) >= 18 AND c.status_inscription_preteur = 1';
        }

        // pour le OR on rajoute la condition derriere
        if ($nom != '') {
            $and .= ' AND c.nom LIKE "' . $nom . '%" OR c.nom_usage LIKE "' . $nom . '%" ' . $and;
        }

        $where .= $and;

        $sql = "
            SELECT
                la.id_lender_account as id_lender_account,
                c.id_client as id_client,
                c.status as status,
                c.email as email,
                c.telephone as telephone,
                c.status_inscription_preteur as status_inscription_preteur,
                CASE la.id_company_owner
                    WHEN 0 THEN c.prenom
                    ELSE
                        (SELECT
                            CASE co.status_client
                                WHEN 1 THEN CONCAT(c.prenom,' ',c.nom)
                                ELSE CONCAT(co.prenom_dirigeant,' ',co.nom_dirigeant)
                            END as dirigeant
                         FROM companies co WHERE co.id_company = la.id_company_owner)
                END as prenom_ou_dirigeant,
                CASE la.id_company_owner
                    WHEN 0 THEN c.nom
                    ELSE (SELECT co.name FROM companies co WHERE co.id_company = la.id_company_owner)
                END as nom_ou_societe,
                CASE la.id_company_owner
                    WHEN 0 THEN REPLACE(c.nom_usage,'Nom D\'usage','')
                    ELSE ''
                END as nom_usage
            FROM lenders_accounts la
            LEFT JOIN clients c ON c.id_client = la.id_client_owner
                LEFT JOIN companies co ON co.id_company = la.id_company_owner
            " . $where . "
            GROUP BY la.id_lender_account
            ORDER BY la.id_lender_account DESC " . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();

        $i = 0;
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[$i] = $record;

            if ($record['status'] == '0' && $noValide != '') {
                $result[$i]['novalid'] = 1;
            } else {
                $result[$i]['novalid'] = '0';
            }
            $i++;
        }
        return $result;
    }

    public function selectPreteursByStatus($status = '', $where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        if ($status != '') {
            $status = ' HAVING status_client IN (' . $status . ')';
        }

        $sql = '
            SELECT
                c.*,
                cs.status AS status_client,
                cs.label AS label_status,
                csh.added AS added_status,
                clsh.id_client_status_history,
                l.id_company_owner as id_company,
                l.type_transfert as type_transfert,
                l.motif as motif,
                l.fonds,
                l.id_lender_account as id_lender
            FROM clients c
            INNER JOIN (SELECT id_client, MAX(id_client_status_history) AS id_client_status_history FROM clients_status_history GROUP BY id_client) clsh ON c.id_client = clsh.id_client
            INNER JOIN clients_status_history csh ON clsh.id_client_status_history = csh.id_client_status_history
            INNER JOIN clients_status cs ON csh.id_client_status = cs.id_client_status
            INNER JOIN lenders_accounts l ON c.id_client = l.id_client_owner
            ' . $where . $status . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();

        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    /**
     * @param array $clientStatus list of last status to use
     * @return array
     */
    public function selectLendersByLastStatus(array $clientStatus = array())
    {
        $naturalPerson = [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER];
        $legalEntity   = [\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER];
        $bind          = [
            'naturalPerson' => $naturalPerson,
            'legalEntity'   => $legalEntity
        ];
        $type          = [
            'naturalPerson' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            'legalEntity'   => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
        ];
        $sSql          = '
            SELECT
              c.id_client,
              c.nom,
              c.prenom,
              c.nom_usage,
              c.naissance,
              c.email,
              -- Address
              CASE
                WHEN c.type IN (:naturalPerson) THEN ca.adresse_fiscal
                WHEN c.type IN (:legalEntity) THEN CONCAT(com.adresse1, \' \', IFNULL(com.adresse2, \'\'))
              END AS adresse_fiscal,
              -- City
              CASE
                WHEN c.type IN (:naturalPerson) THEN ca.ville_fiscal
                WHEN c.type IN (:legalEntity) THEN com.city
              END AS ville_fiscal,
              -- Zip code
              CASE
                WHEN c.type IN (:naturalPerson) THEN ca.cp_fiscal
                WHEN c.type IN (:legalEntity) THEN com.zip
              END AS cp_fiscal,
              -- Country ISO
              CASE
                WHEN c.type IN (:naturalPerson) THEN person_country.iso
                WHEN c.type IN (:legalEntity) THEN legal_entity_country.iso
              END AS iso,
              -- Country label
              CASE
                WHEN c.type IN (:naturalPerson) THEN person_country.fr
                WHEN c.type IN (:legalEntity) THEN legal_entity_country.fr
              END AS fr,
              la.id_lender_account,
              la.iban,
              la.bic,
              csh.added,
              cs.status,
              cs.label
            FROM clients_status_history csh
              INNER JOIN clients c ON c.id_client = csh.id_client
              LEFT JOIN clients_adresses ca ON ca.id_client = c.id_client
              LEFT JOIN companies com ON com.id_client_owner = c.id_client
              LEFT JOIN pays_v2 person_country ON person_country.id_pays = ca.id_pays_fiscal
              LEFT JOIN pays_v2 legal_entity_country ON legal_entity_country.id_pays = com.id_pays
              INNER JOIN lenders_accounts la ON la.id_client_owner = csh.id_client
              INNER JOIN clients_status cs ON cs.id_client_status = csh.id_client_status
            WHERE csh.id_client_status_history = (
              SELECT MAX(csh1.id_client_status_history)
              FROM clients_status_history csh1
              WHERE csh1.id_client = csh.id_client
            )';
        if (false === empty($clientStatus)) {
            $bind['clientStatus'] = $clientStatus;
            $type['clientStatus'] = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
            $sSql .= ' AND cs.status IN (:clientStatus) ';
        }
        /** @var \Doctrine\DBAL\Statement $statement */
        $statement = $this->bdd->executeQuery($sSql, $bind, $type);

        $result = array();
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $result[$row['id_client']] = $row;
        }
        $statement->closeCursor();
        return $result;
    }

    public function update_added($date, $id_client)
    {
        $sql = "UPDATE clients SET added = '" . $date . "' WHERE id_client = " . $id_client;
        $this->bdd->query($sql);
    }

    public function searchPrescripteur($iAdvisorId = '', $nom = '', $prenom = '', $email = '', $sCompanyName = '', $sSiren = '', $offset = '', $limit = 100, $sOperation = 'AND')
    {
        $aWhere = array();

        if ('' !== $nom) {
            $nom = $this->bdd->escape_string($nom);
            $aWhere[] = 'c.nom LIKE "%' . $nom . '%"';
        }
        if ('' !== $email) {
            $email = $this->bdd->escape_string($email);
            $aWhere[] = 'c.email LIKE "%' . $email . '%"';
        }
        if ('' !== $prenom) {
            $prenom = $this->bdd->escape_string($prenom);
            $aWhere[] = 'c.prenom LIKE "%' . $prenom . '%"';
        }

        if ('' !== $sCompanyName) {
            $sCompanyName = $this->bdd->escape_string($sCompanyName);
            $aWhere[] = 'com.name LIKE "%' . $sCompanyName . '%"';
        }

        if ('' !== $sSiren) {
            $sSiren = $this->bdd->escape_string($sSiren);
            $aWhere[] = 'com.siren = "' . $sSiren . '"';
        }

        $sWhere = '';
        if ('' !== $iAdvisorId) {
            $iAdvisorId = $this->bdd->escape_string($iAdvisorId);
            $sWhere = ' WHERE p.id_prescripteur = '. $iAdvisorId;
        } elseif (false === empty($aWhere)) {
            $sWhere = ' WHERE ' . implode(' ' . $sOperation.' ', $aWhere);
        }

        if ('' !== $offset) {
            $offset = $this->bdd->escape_string($offset);
            $offset = ' OFFSET '. $offset;
        }

        if ('' !== $limit) {
            $limit = $this->bdd->escape_string($limit);
            $limit = ' LIMIT '. $limit;
        }

        $sql = 'SELECT * FROM clients c
                INNER JOIN prescripteurs p USING (id_client)
                INNER JOIN companies com ON p.id_entite = com.id_company'
                . $sWhere
                . ' ORDER BY c.id_client DESC'
                . $limit
                . $offset;

        $oQuery = $this->bdd->query($sql);
        $result   = array();

        while ($record = $this->bdd->fetch_array($oQuery)) {
            $result[] = $record;
        }
        return $result;
    }

    public function isAdvisor()
    {
        $oAdvisors = new \prescripteurs($this->bdd);
        return $oAdvisors->exist($this->id_client, 'id_client');
    }

    public function isLender()
    {
        $oLendersAccounts = new \lenders_accounts($this->bdd);
        return $oLendersAccounts->exist($this->id_client, 'id_client_owner');
    }

    public function isBorrower()
    {
        $oCompanies = new \companies($this->bdd);
        $oProjects  = new \projects($this->bdd);

        if ($oCompanies->get($this->id_client, 'id_client_owner')){
            return $oProjects->exist($oCompanies->id_company, 'id_company');
        } else {
            return false;
        }
    }

    public function getDataForBorrowerOperations(array $aProjects, DateTime $oStartDate, DateTime $oEndDate, $iOperation = null, $iClientId = null)
    {
        if (null === $iClientId) {
            $iClientId = $this->id_client;
        }

        if ($iOperation == 0) {
            $aOperations = array(
                self::AFF_MENSUALITE_PRETEURS,
                self::AFFECTATION_RA_PRETEURS,
                self::COMMISSION_DEBLOCAGE,
                self::COMMISSION_MENSUELLE,
                self::OCTROI_FINANCMENT,
                self::PRLV_MENSUALITE,
                self::REMBOURSEMENT_ANTICIPE,
                self::VIREMENT
            );
        } else {
            $aOperations = array($iOperation);
        }

        $sStartDate = '"' . $oStartDate->format('Y-m-d') . ' 00:00:00"';
        $sEndDate   = '"' . $oEndDate->format('Y-m-d') . ' 23:59:59"';

        $aDataForBorrowerOperations = array();

        foreach ($aOperations as $iOperation) {
            switch ($iOperation) {
                case self::COMMISSION_DEBLOCAGE:
                    $aDataForBorrowerOperations = array_merge($aDataForBorrowerOperations, $this->getBorrowerOperationCommissionOnFinancing($aProjects, $sStartDate, $sEndDate));
                    break;
                case self::OCTROI_FINANCMENT:
                    $aDataForBorrowerOperations = array_merge($aDataForBorrowerOperations, $this->getBorrowerOperationAllLoans($aProjects, $sStartDate, $sEndDate));
                    break;
                case self::VIREMENT:
                    $aDataForBorrowerOperations = array_merge($aDataForBorrowerOperations, $this->getBorrowerOperationTransferFinancing($iClientId, $aProjects, $sStartDate, $sEndDate));
                    break;
                case self::PRLV_MENSUALITE:
                    if (false === in_array(self::COMMISSION_MENSUELLE, $aOperations)) {
                        $aDataForBorrowerOperations = array_merge($aDataForBorrowerOperations, $this->getBorrowerOperationMonthlyDueAndCommission($aProjects, $sStartDate, $sEndDate, self::PRLV_MENSUALITE));
                    } else {
                        $aDataForBorrowerOperations = array_merge($aDataForBorrowerOperations, $this->getBorrowerOperationMonthlyDueAndCommission($aProjects, $sStartDate, $sEndDate));
                    }
                    break;
                case self::COMMISSION_MENSUELLE:
                    if (false === in_array(self::PRLV_MENSUALITE, $aOperations)) {
                        $aDataForBorrowerOperations = array_merge($aDataForBorrowerOperations, $this->getBorrowerOperationMonthlyDueAndCommission($aProjects, $sStartDate, $sEndDate, self::COMMISSION_MENSUELLE));
                    }
                    break;
                case self::AFF_MENSUALITE_PRETEURS:
                    $aDataForBorrowerOperations = array_merge($aDataForBorrowerOperations, $this->getBorrowerOperationMonthlyDueToLenders($aProjects, $sStartDate, $sEndDate));
                    break;
                case self::REMBOURSEMENT_ANTICIPE:
                    $aDataForBorrowerOperations = array_merge($aDataForBorrowerOperations, $this->getBorrowerOperationEarlyRefunding($aProjects, $sStartDate, $sEndDate));
                    break;
                case self::AFFECTATION_RA_PRETEURS:
                    $aDataForBorrowerOperations = array_merge($aDataForBorrowerOperations, $this->getBorrowerOperationEarlyRefundingToLenders($aProjects, $sStartDate, $sEndDate));
                    break;
            }
        }

        usort($aDataForBorrowerOperations, function ($aFirstArray, $aSecondArray) {
            if ($aFirstArray['date'] === $aSecondArray['date']) {
                if ($aFirstArray['type'] == 'prelevement-mensualite') {
                    return 1;
                } elseif ($aFirstArray['type'] == 'commission-mensuelle') {
                    return -1;
                }
                if ($aFirstArray['type'] == 'commission-deblocage') {
                    if ($aSecondArray['type'] == 'virement'){
                        return 1;
                    } else {
                        return -1;
                    }
                } elseif ($aFirstArray['type'] == 'virement') {
                    return -1;
                } elseif ($aFirstArray['type'] == 'financement') {
                    return 1;
                }
            } else {
                return $aFirstArray['date'] < $aSecondArray['date'];
            }
        });
        return $aDataForBorrowerOperations;
    }

    private function getBorrowerOperationAllLoans($aProjects, $sStartDate, $sEndDate)
    {
        $aDataForBorrowerOperations = array();
        $sql = 'SELECT
                    sum(l.amount)/100 AS montant,
                    DATE(psh.added) AS date,
                    l.id_project,
                    "financement" AS type
                FROM
                    `loans` l
                    INNER JOIN projects_status_history psh ON l.id_project = psh.id_project
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE
                    l.id_project IN (' . implode(',', $aProjects) . ')
                    AND ps.status = ' . \projects_status::REMBOURSEMENT . '
                    AND psh.added BETWEEN ' . $sStartDate . 'AND ' . $sEndDate . '
                GROUP BY
                    id_project';

        $result = $this->bdd->query($sql);

            while ($record = $this->bdd->fetch_assoc($result)) {
                $aDataForBorrowerOperations[] = $record;
            }
        return $aDataForBorrowerOperations;
    }

    private function getBorrowerOperationTransferFinancing($iClientId, $aProjects, $sStartDate, $sEndDate)
    {
        $aDataForBorrowerOperations = array();
        $sql = '
            SELECT
                montant / 100 AS montant,
                DATE(date_transaction) AS date,
                id_project,
                "virement" AS type
            FROM transactions
            WHERE
                id_project IN (' . implode(',', $aProjects) . ')
                AND id_client = ' . $iClientId . '
                AND date_transaction BETWEEN ' . $sStartDate . 'AND ' . $sEndDate . '
                AND type_transaction = ' . \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT . '
            GROUP BY id_project';

        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($result)) {
            $aDataForBorrowerOperations[] = $record;
        }
        return $aDataForBorrowerOperations;
    }

    private function getBorrowerOperationMonthlyDueAndCommission($aProjects, $sStartDate, $sEndDate, $iType = null)
    {
        $aDataForBorrowerOperations = array();
        $sql = '
            SELECT
                id_project,
                SUM(montant + commission + tva) / 100 AS montant,
                -commission / 100 AS commission,
                -tva / 100 AS tva,
                DATE(date_echeance_emprunteur_reel) AS date
            FROM echeanciers_emprunteur
            WHERE
                id_project IN (' . implode(',', $aProjects) . ')
                AND status_emprunteur = 1
                AND DATE(date_echeance_emprunteur_reel) BETWEEN ' . $sStartDate . ' AND ' . $sEndDate . '
                AND status_ra = 0
            GROUP BY id_project, DATE(date_echeance_emprunteur_reel)';

        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($result)) {
            if ($iType === self::PRLV_MENSUALITE || $iType === null ) {
                $aDataForBorrowerOperations[] = array(
                    'id_project' => $record['id_project'],
                    'montant'    => $record['montant'],
                    'date'       => $record['date'],
                    'type'       => 'prelevement-mensualite'
                );
            }
            if ($iType === self::COMMISSION_MENSUELLE || $iType === null) {
                $aDataForBorrowerOperations[] = array(
                    'id_project' => $record['id_project'],
                    'montant'    => $record['commission'] + $record['tva'],
                    'commission' => $record['commission'],
                    'tva'        => $record['tva'],
                    'date'       => $record['date'],
                    'type'       => 'commission-mensuelle'
                );
            }
        }
        return $aDataForBorrowerOperations;
    }

    private function getBorrowerOperationMonthlyDueToLenders($aProjects, $sStartDate, $sEndDate)
    {
        $aDataForBorrowerOperations = array();
        $sql = 'SELECT
                    `id_project`,
                    -SUM(`capital` + `interets`)/100 AS montant,
                    DATE(date_echeance_reel) AS date,
                    `ordre`,
                    "affectation-preteurs" AS type
                FROM
                    `echeanciers`
                WHERE
                    `id_project` IN (' . implode(',', $aProjects) . ')
                    AND DATE(`date_echeance_reel`) BETWEEN ' . $sStartDate . ' AND ' . $sEndDate . '
                    AND `status` = 1
                    AND `status_ra` = 0
                GROUP BY
                    `id_project`,
                    DATE(`date_echeance`)';

        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($result)) {
            $aDataForBorrowerOperations[] = $record;
        }
        return $aDataForBorrowerOperations;
    }

    private function getBorrowerOperationEarlyRefunding($aProjects, $sStartDate, $sEndDate)
    {
        $aDataForBorrowerOperations = array();
        $sql = '
            SELECT id_project,
                montant / 100 AS montant,
                DATE(added) as date,
                "remboursement-anticipe" AS type
            FROM transactions
            WHERE type_transaction = ' . \transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT . '
                AND id_project IN (' . implode(', ', $aProjects) . ')
                AND added BETWEEN ' . $sStartDate . ' AND ' . $sEndDate. '
            GROUP BY id_project';

        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($result)) {
            $aDataForBorrowerOperations[] = $record;
        }

        return $aDataForBorrowerOperations;
    }

    private function getBorrowerOperationEarlyRefundingToLenders($aProjects, $sStartDate, $sEndDate)
    {
        $aDataForBorrowerOperations = array();
        $sql = 'SELECT
                    `id_project`,
                    - SUM(`capital`)/100 AS montant,
                    DATE(date_echeance_reel) AS date,
                    "affectation-ra-preteur" AS type
                FROM
                    `echeanciers`
                WHERE
                    `id_project` IN (' . implode(',', $aProjects) . ')
                    AND `date_echeance_reel` BETWEEN ' . $sStartDate . ' AND ' . $sEndDate. '
                    AND `status` = 1
                    AND `status_ra` = 1
                GROUP BY
                    `date_echeance_reel`';

        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($result)) {
            $aDataForBorrowerOperations[] = $record;
        }

        return $aDataForBorrowerOperations;
    }

    private function getBorrowerOperationCommissionOnFinancing($aProjects, $sStartDate, $sEndDate)
    {
        $aDataForBorrowerOperations = array();

        $sql = 'SELECT
                        id_project,
                        -montant_ttc/100 AS montant,
                        -montant_ht/100 AS commission,
                        -tva/100 AS tva,
                        date,
                        \'commission-deblocage\' AS type
                    FROM
                        `factures`
                    WHERE
                        `id_project` IN (' . implode(',', $aProjects) . ')
                        AND `date` BETWEEN ' . $sStartDate . ' AND ' . $sEndDate. '
                        AND type_commission = ' . \factures::TYPE_COMMISSION_FINANCEMENT;

        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($result)) {
            $aDataForBorrowerOperations[] = $record;
        }

        return $aDataForBorrowerOperations;
    }

    /**
     * Retrieve pattern that lender must use in bank transfer label
     * @param int $iClientId
     * @return string
     */
    public function getLenderPattern($iClientId)
    {
        $this->get($iClientId);

        $oToolkit = new \ficelle();

        return mb_strtoupper(
            str_pad($this->id_client, 6, 0, STR_PAD_LEFT) .
            substr($oToolkit->stripAccents($this->prenom), 0, 1) .
            $oToolkit->stripAccents($this->nom)
        );
    }

    /**
     * Check whether given pattern corresponds to actual lender pattern
     * @param int    $clientId
     * @param string $pattern
     * @return bool
     */
    public function isLenderPattern($clientId, $pattern)
    {
        $pattern       = str_replace(' ', '', $pattern);
        $lenderPattern = str_replace(' ', '', $this->getLenderPattern($clientId));

        return (false !== strpos($pattern, $lenderPattern));
    }

    public function getDuplicates($sLastName, $sFirstName, $sBirthdate)
    {
        $aCharactersToReplace = array(' ', '-', '_', '*', ',', '^', '`', ':', ';', ',', '.', '!', '&', '"', '\'', '<', '>', '(', ')', '@');

        $sFirstName     = str_replace($aCharactersToReplace, '', htmlspecialchars_decode($sFirstName));
        $sLastName      = str_replace($aCharactersToReplace, '', htmlspecialchars_decode($sLastName));

        $sReplaceCharacters = '';
        foreach ($aCharactersToReplace as $sCharacter) {
            $sReplaceCharacters .= ',\'' . addslashes($sCharacter) . '\', \'\')';
        }

        $sql = 'SELECT *
                FROM clients c
                WHERE ' . str_repeat('REPLACE(', count($aCharactersToReplace)) . '`nom`' . $sReplaceCharacters . ' LIKE "%' . $sLastName. '%"
                            AND ' . str_repeat('REPLACE(', count($aCharactersToReplace)) . '`prenom`' . $sReplaceCharacters . ' LIKE "%' . $sFirstName . '%"
                            AND naissance = "' . $sBirthdate . '"
                            AND status = 1
                            AND
                                (SELECT cs.status
                                FROM clients_status cs
                                    LEFT JOIN clients_status_history csh ON (cs.id_client_status = csh.id_client_status)
                                WHERE csh.id_client = c.id_client
                                    ORDER BY csh.added DESC LIMIT 1) IN (' . \clients_status::VALIDATED . ')';

        $rQuery = $this->bdd->query($sql);
        $result = array();

        while ($record = $this->bdd->fetch_array($rQuery)) {
            $result[] = $record;
        }

        return $result;
    }

    public function getClientsWithNoWelcomeOffer($iClientId = null, $sStartDate = null, $sEndDate = null)
    {
        if (null === $sStartDate) {
            $sStartDate = '2013-01-01';
        }

        if (null === $sEndDate) {
            $sEndDate = 'NOW()';
        } else {
            $sEndDate = str_pad($sEndDate,12,'"', STR_PAD_BOTH);
        }

        if (false === is_null($iClientId)) {
            $sWhereID = 'AND c.id_client IN (' . $iClientId . ')';
        } else {
            $sWhereID = '';
        }

        $sql = 'SELECT
                    c.id_client,
                    c.nom,
                    c.prenom,
                    c.email,
                    companies.name,
                    DATE(c.added) AS date_creation,
                    (
                        SELECT MAX(csh.added)
                        FROM
                            clients_status_history csh
                            INNER JOIN clients ON clients.id_client = csh.id_client
                            INNER JOIN clients_status cs ON csh.id_client_status = cs.id_client_status
                        WHERE
                            cs.status = '. \clients_status::VALIDATED . '
                            AND c.id_client = csh.id_client
                        ORDER BY
                            csh.added DESC
                        LIMIT
                            1
                    ) AS date_validation
                FROM
                    clients c
                    LEFT JOIN companies ON c.id_client = companies.id_client_owner
                WHERE
                    DATE(c.added) BETWEEN "' . $sStartDate . '" AND ' . $sEndDate . '
                    AND NOT EXISTS (SELECT obd.id_client FROM offres_bienvenues_details obd WHERE c.id_client = obd.id_client)
                    AND NOT EXISTS (SELECT t.id_transaction FROM transactions t WHERE t.type_transaction = ' . \transactions_types::TYPE_WELCOME_OFFER . ' AND t.id_client = c.id_client)
                    ' . $sWhereID;

        $resultat = $this->bdd->query($sql);

        $aClientsWithoutWelcomeOffer = array();
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $aClientsWithoutWelcomeOffer[] = $record;
        }

        return $aClientsWithoutWelcomeOffer;
    }

    /**
     * PLEASE NOTE :
     * If $bGroupBySiren = true, the result does not necessary provide the most recent
     * value for any fields other than siren and count of siren.
     * The only reliable information with this option is Siren and Count(Siren).
     *
     * @param DateTime $oStartDate
     * @param DateTime $oEndDate
     * @param bool $bGroupBySiren
     *
     * @return array
     */
    public function getBorrowersContactDetailsAndSource(\DateTime $oStartDate, \DateTime $oEndDate, $bGroupBySiren)
    {
        $sGroupBy    = $bGroupBySiren ? 'GROUP BY com.siren ' : '';
        $sCountSiren = $bGroupBySiren ? 'COUNT(com.siren) AS countSiren, ' : '';

        $sQuery = '
            SELECT
                p.id_project,'
                . $sCountSiren . '
                com.siren,
                c.nom,
                c.prenom,
                c.email,
                c.mobile,
                c.telephone,
                c.source,
                c.source2,
                c.added,
                ps.label
            FROM projects p
            INNER JOIN companies com ON p.id_company = com.id_company
            INNER JOIN clients c ON com.id_client_owner = c.id_client
            INNER JOIN projects_status ps ON p.status = ps.status
            WHERE DATE(p.added) BETWEEN "'. $oStartDate->format('Y-m-d') . '" AND "'. $oEndDate->format('Y-m-d') . '" '
            . $sGroupBy . '
            ORDER BY com.siren DESC, c.added DESC';

        $rQuery = $this->bdd->query($sQuery);
        $aResult = array();
        while ($record = $this->bdd->fetch_assoc($rQuery)) {
            $aResult[] = $record;
        }

        return $aResult;
    }

    public function getFirstSourceForSiren($sSiren, \DateTime $oStartDate = null, \DateTime $oEndDate = null)
    {
        if (false === is_null($oStartDate) && false === is_null($oEndDate)) {
            $oStartDate = new \DateTime('2013-01-01');
            $oEndDate = new \DateTime('NOW');
        }

        $sQuery = 'SELECT
                        c.source
                    FROM
                        clients c
                        INNER JOIN companies com on c.id_client = com.id_client_owner
                    WHERE
                    com.siren = ' . $sSiren . '
                    AND DATE(c.added) BETWEEN "'. $oStartDate->format('Y-m-d') . '" AND "'. $oEndDate->format('Y-m-d') . '"
                    ORDER BY c.added ASC LIMIT 1';

        $rQuery = $this->bdd->query($sQuery);
        return ($this->bdd->result($rQuery, 0));
    }

    public function getLastSourceForSiren($sSiren, \DateTime $oStartDate = null, \DateTime $oEndDate = null)
    {
        if (false === is_null($oStartDate) && false === is_null($oEndDate)) {
            $oStartDate = new \DateTime('2013-01-01');
            $oEndDate = new \DateTime('NOW');
        }

        $sQuery = 'SELECT
                        c.source
                    FROM
                        clients c
                        INNER JOIN companies com on c.id_client = com.id_client_owner
                    WHERE
                    com.siren = ' . $sSiren . '
                    AND DATE(c.added) BETWEEN "'. $oStartDate->format('Y-m-d') . '" AND "'. $oEndDate->format('Y-m-d') . '"
                    ORDER BY c.added DESC LIMIT 1';

        $rQuery = $this->bdd->query($sQuery);
        return ($this->bdd->result($rQuery, 0));
    }

    public function getBorrowersSalesForce()
    {
        $sQuery = "SELECT
                      c.id_client as 'IDClient',
                      c.id_client as 'IDClient_2',
                      c.id_langue as 'Langue',
                      REPLACE(c.civilite,',','') as 'Civilite',
                      REPLACE(c.nom,',','') as 'Nom',
                      REPLACE(c.nom_usage,',','') as 'Nom_usage',
                      REPLACE(c.prenom,',','') as 'Prenom',
                      CONVERT(REPLACE(c.fonction,',','') USING utf8) as 'Fonction',
                      CASE c.naissance
                      WHEN '0000-00-00' then '2001-01-01'
                      ELSE
                        CASE SUBSTRING(c.naissance,1,1)
                        WHEN '0' then '2001-01-01'
                        ELSE c.naissance
                        END
                      END as 'DateNaissance',
                      REPLACE(ville_naissance,',','') as 'VilleNaissance',
                      ccountry.fr as 'PaysNaissance',
                      nv2.fr_f as 'Nationalite',
                      REPLACE(c.telephone,'\t','') as 'Telephone',
                      c.mobile as 'Mobile',
                      REPLACE(c.email,',','') as 'Email',
                      c.etape_inscription_preteur as 'EtapeInscriptionPreteur',
                      CASE c.type
                      WHEN 1 THEN 'Physique'
                      WHEN 2 THEN 'Morale'
                      WHEN 3 THEN 'Physique'
                      ELSE 'Morale'
                      END as 'TypeContact',
                      CASE c.status
                      WHEN 1 THEN 'oui'
                      ELSE 'non'
                      END as 'Valide',
                      CASE c.added
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.added
                      END as 'date_inscription',
                      CASE c.updated
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.updated
                      END as 'DateMiseJour',
                      CASE c.lastlogin
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.lastlogin
                      END as 'DateDernierLogin',
                      REPLACE(ca.adresse1,',','') as 'Adresse1',
                      REPLACE(ca.adresse2,',','') as 'Adresse2',
                      REPLACE(ca.adresse3,',','') as 'Adresse3',
                      REPLACE(ca.cp,',','') as 'CP',
                      REPLACE(ca.ville,',','') as 'Ville',
                      acountry.fr as 'Pays',
                      '012240000002G4e' as 'Sfcompte'
                    FROM
                      clients c
                      INNER JOIN companies co on c.id_client = co.id_client_owner
                      INNER JOIN projects p ON p.id_company = co.id_company
                      LEFT JOIN clients_adresses ca on c.id_client = ca.id_client
                      LEFT JOIN pays_v2 ccountry on c.id_pays_naissance = ccountry.id_pays
                      LEFT JOIN pays_v2 acountry on ca.id_pays = acountry.id_pays
                      LEFT JOIN nationalites_v2 nv2 on c.id_nationalite = nv2.id_nationalite
                    group by
                      c.id_client";

        return $this->bdd->executeQuery($sQuery);
    }


    public function countClientsByRegion()
    {
        $query = 'SELECT
                      CASE
                      WHEN LEFT(client_base.cp, 2) IN (08, 10, 51, 52, 54, 55, 57, 67, 68, 88)
                        THEN "44"
                      WHEN LEFT(client_base.cp, 2) IN (16, 17, 19, 23, 24, 33, 40, 47, 64, 79, 86, 87)
                        THEN "75"
                      WHEN LEFT(client_base.cp, 2) IN (01, 03, 07, 15, 26, 38, 42, 43, 63, 69, 73, 74)
                        THEN "84"
                      WHEN LEFT(client_base.cp, 2) IN (21, 25, 39, 58, 70, 71, 89, 90)
                        THEN "27"
                      WHEN LEFT(client_base.cp, 2) IN (22, 29, 35, 56)
                        THEN "53"
                      WHEN LEFT(client_base.cp, 2) IN (18, 28, 36, 37, 41, 45)
                        THEN "24"
                      WHEN LEFT(client_base.cp, 2) IN (20)
                        THEN "94"
                      WHEN LEFT(client_base.cp, 3) IN (971)
                        THEN "01"
                      WHEN LEFT(client_base.cp, 3) IN (973)
                        THEN "03"
                      WHEN LEFT(client_base.cp, 2) IN (75, 77, 78, 91, 92, 93, 94, 95)
                        THEN "11"
                      WHEN LEFT(client_base.cp, 3) IN (974)
                        THEN "04"
                      WHEN LEFT(client_base.cp, 2) IN (09, 11, 12, 30, 31, 32, 34, 46, 48, 65, 66, 81, 82)
                        THEN "76"
                      WHEN LEFT(client_base.cp, 3) IN (972)
                        THEN "02"
                      WHEN LEFT(client_base.cp, 3) IN (976)
                        THEN "06"
                      WHEN LEFT(client_base.cp, 2) IN (02, 59, 60, 62, 80)
                        THEN "32"
                      WHEN LEFT(client_base.cp, 2) IN (14, 27, 50, 61, 76)
                        THEN "28"
                      WHEN LEFT(client_base.cp, 2) IN (44, 49, 53, 72, 85)
                        THEN "52"
                      WHEN LEFT(client_base.cp, 2) IN (04, 05, 06, 13, 83, 84)
                        THEN "93"
                      ELSE "0"
                      END AS insee_region_code,
                      COUNT(*) AS count
                    FROM (SELECT id_client,
                         CASE WHEN clients.type IN (' . implode(',', [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER]) . ') THEN clients_adresses.cp_fiscal ELSE companies.zip END AS cp
                         FROM clients
                             LEFT JOIN clients_adresses USING (id_client)
                             LEFT JOIN companies ON clients.id_client = companies.id_client_owner
                             INNER JOIN lenders_accounts ON clients.id_client = lenders_accounts.id_client_owner
                         WHERE clients.status = '. \clients::STATUS_ONLINE .' AND lenders_accounts.status = 1
                         AND (clients_adresses.id_pays_fiscal = ' . \pays_v2::COUNTRY_FRANCE . ' OR companies.id_pays = ' . \pays_v2::COUNTRY_FRANCE . ')) AS client_base
                    GROUP BY insee_region_code';

        $statement = $this->bdd->executeQuery($query);
        $regionsCount  = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $regionsCount[$row['insee_region_code']] = $row['count'];
        }

        return $regionsCount;
    }

    public function getBorrowersByCategory()
    {
        $sQuery = 'SELECT
                      count(DISTINCT projects.id_project), company_sector.sector
                    FROM
                      `companies`
                      INNER JOIN projects ON companies.id_company = projects.id_company
                      INNER JOIN projects_status_history ON projects.id_project = projects_status_history.id_project
                      INNER JOIN projects_status ON (projects_status_history.id_project_status = projects_status.id_project_status AND projects_status.status = 80)
                      INNER JOIN company_sector ON companies.sector = company_sector.id_company_sector

                      GROUP BY companies.sector';

        $oStatement = $this->bdd->executeQuery($sQuery);
        $aCountByCategories = array();

        while ($aRow = $oStatement->fetch(\PDO::FETCH_ASSOC)) {
            $aCountByCategories[$aRow['id_sector']] = $aRow['count'];
        }

        return $aCountByCategories;
    }

    /**
     * @param array $clientStatus
     * @param array $attachmentTypes
     * @return array
     */
    public function getClientsToAutoValidate(array $clientStatus, array $attachmentTypes)
    {
        $bind = ['client_status_id' => $clientStatus, 'attachment_type_id' => $attachmentTypes];
        $type = ['client_status_id' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY, 'attachment_type_id' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY];

        $sql = "
        SELECT
          c.id_client,
          gpa.final_status,
          gpa.revalidate,
          gpa.id_attachment,
          a.id_type        
        FROM clients_status_history csh
          INNER JOIN greenpoint_kyc kyc ON kyc.id_client = csh.id_client
          INNER JOIN greenpoint_attachment gpa ON gpa.id_client = csh.id_client
          INNER JOIN attachment a ON a.id = gpa.id_attachment AND a.id_type IN (:attachment_type_id)
          INNER JOIN clients c ON c.id_client = csh.id_client
          INNER JOIN clients_adresses ca ON ca.id_client = c.id_client AND ca.id_pays_fiscal = 1
          INNER JOIN clients_status cs ON cs.id_client_status = csh.id_client_status
        WHERE csh.id_client_status_history = (SELECT MAX(csh1.id_client_status_history)
                                              FROM clients_status_history csh1
                                              WHERE csh1.id_client = csh.id_client
                                              LIMIT 1)
              AND cs.status IN (:client_status_id) AND kyc.status = 999";
        /** @var \Doctrine\DBAL\Statement $statement */
        $statement = $this->bdd->executeQuery($sql, $bind, $type);
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
