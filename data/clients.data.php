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

    public function handleLogin($button, $email, $pass)
    {
        if (isset($_POST[$button])) {
            $client = $this->login($_POST[$email], $_POST[$pass]);

            if ($client != false) {
                $_SESSION['auth']   = true;
                $_SESSION['token']  = md5(md5(mktime() . $this->securityKey));
                $_SESSION['client'] = $client;

                // Mise à jour pour la derniere connexion du user
                $sql = 'UPDATE ' . $this->userTable . ' SET lastlogin = "' . date('Y-m-d H:i:s') . '" WHERE email = "' . $_POST[$email] . '" AND password = "' . md5($_POST[$pass]) . '"';
                $this->bdd->query($sql);
                return true;
            } else {
                return false;
            }
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
            header('Location: http://' . $_SERVER['HTTP_HOST'] . '/' . $this->params['lng'] . $this->loginPage);
        }
    }

    public function login($email, $pass)
    {
        $email = $this->bdd->escape_string($email);
        $sql   = 'SELECT * FROM ' . $this->userTable . ' WHERE ' . $this->userMail . ' = "' . $email . '" AND ' . $this->userPass . ' = "' . md5($pass) . '" AND status = 1';
        $res   = $this->bdd->query($sql);

        if ($this->bdd->num_rows($res) == 1) {
            return $this->bdd->fetch_array($res);
        } else {
            return false;
        }
    }

    public function loginSuperCB($email, $pass)
    {
        $email = $this->bdd->escape_string($email);

        $sql = 'SELECT * FROM ' . $this->userTable . ' WHERE ' . $this->userMail . ' = "' . $email . '" AND ' . $this->userPass . ' = "' . $pass . '"';
        $res = $this->bdd->query($sql);

        if ($this->bdd->num_rows($res) == 1) {
            return $this->bdd->fetch_array($res);
        } else {
            return false;
        }
    }

    public function loginUpdate()
    {
        $sql = 'SELECT * FROM ' . $this->userTable . ' WHERE id_client = "' . $_SESSION['client']['id_client'] . '" AND hash = "' . $_SESSION['client']['hash'] . '"';
        $res = $this->bdd->query($sql);

        if ($this->bdd->num_rows($res) == 1) {
            return $this->bdd->fetch_array($res);
        } else {
            return false;
        }
    }

    public function changePassword($email, $pass)
    {
        $sql = 'UPDATE ' . $this->userTable . ' SET ' . $this->userPass . ' = "' . md5($pass) . '" WHERE ' . $this->userMail . ' = "' . $email . '"';
        $this->bdd->query($sql);
    }

    public function existEmail($email)
    {
        $sql = 'SELECT * FROM ' . $this->userTable . ' WHERE ' . $this->userMail . ' = "' . $email . '"';
        $res = $this->bdd->query($sql);

        if ($this->bdd->num_rows($res) >= 1) {
            return false;
        } else {
            return true;
        }
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


    public function searchClients($ref = '', $nom = '', $email = '', $prenom = '')
    {
        $where = 'WHERE 1 = 1';

        if ($ref != '') {
            $where .= ' AND t.id_transaction LIKE "%' . $ref . '%"';
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

        $sql      = 'SELECT c.* FROM clients c LEFT JOIN transactions t ON t.id_client = c.id_client ' . $where . ' GROUP BY c.id_client ORDER BY c.added DESC';
        $resultat = $this->bdd->query($sql);
        $result   = array();

        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
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

        $result   = array();
        $resultat = $this->bdd->query('
            SELECT c.*,
                co.*
            FROM clients c
            LEFT JOIN companies co ON c.id_client = co.id_client_owner
            WHERE ' . $where . '
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
                (SELECT cs.status FROM clients_status cs LEFT JOIN clients_status_history csh ON (cs.id_client_status = csh.id_client_status) WHERE csh.id_client = c.id_client ORDER BY csh.added DESC LIMIT 1) as status_client,
                (SELECT cs.label FROM clients_status cs LEFT JOIN clients_status_history csh ON (cs.id_client_status = csh.id_client_status) WHERE csh.id_client = c.id_client ORDER BY csh.added DESC LIMIT 1) as label_status,
                (SELECT csh.added FROM clients_status cs LEFT JOIN clients_status_history csh ON (cs.id_client_status = csh.id_client_status) WHERE csh.id_client = c.id_client ORDER BY csh.added DESC LIMIT 1) as added_status,
                (SELECT csh.id_client_status_history FROM clients_status cs LEFT JOIN clients_status_history csh ON (cs.id_client_status = csh.id_client_status) WHERE csh.id_client = c.id_client ORDER BY csh.added DESC LIMIT 1) as id_client_status_history,
                l.id_company_owner as id_company,
                l.type_transfert as type_transfert,
                l.motif as motif,
                l.fonds,
                l.id_lender_account as id_lender
            FROM clients c
            LEFT JOIN lenders_accounts l ON c.id_client = l.id_client_owner
            ' . $where . $status . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();


        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    // presteurs by status
    public function selectPreteursByStatusSlim($status = '', $where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' AND ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = '
            SELECT
                c.id_client,
                l.id_lender_account as id_lender
            FROM clients c
            LEFT JOIN lenders_accounts l ON c.id_client = l.id_client_owner
            WHERE (SELECT cs.status FROM clients_status cs LEFT JOIN clients_status_history csh ON (cs.id_client_status = csh.id_client_status) WHERE csh.id_client = c.id_client ORDER BY csh.added DESC LIMIT 1) IN (' . $status . ')' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function update_added($date, $id_client)
    {
        $sql = "UPDATE clients SET added = '" . $date . "' WHERE id_client = " . $id_client;
        $this->bdd->query($sql);
    }

    public function get_prospects()
    {
        $sql = '
            SELECT *
            FROM clients c
            LEFT JOIN clients_adresses ca ON (ca.id_client = c.id_client)
            WHERE c.added < "2014-07-31 00:00:00"
                AND c.status = 0
                AND c.telephone = ""
                AND c.mobile = ""
                AND ca.    adresse1 = ""
                AND ca.cp = ""
                AND ca.ville = ""
                AND c.email != ""';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function get_preteurs_restriction($sql)
    {
        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function counter_de_test($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        echo $sql = 'SELECT count(*) FROM `clients` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
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
        $sql = 'SELECT
                    montant/100 AS montant,
                    DATE(date_transaction) AS date,
                    id_project,
                    "virement" AS type
                FROM
                    `transactions`
                WHERE
                    `id_project` IN (' . implode(',', $aProjects) . ')
                    AND id_client = ' . $iClientId . '
                    AND date_transaction BETWEEN ' . $sStartDate . 'AND ' . $sEndDate . '
                    AND `type_transaction` = 9
                GROUP BY
                    id_project';

        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($result)) {
            $aDataForBorrowerOperations[] = $record;
        }
        return $aDataForBorrowerOperations;
    }

    private function getBorrowerOperationMonthlyDueAndCommission($aProjects, $sStartDate, $sEndDate, $iType = null)
    {
        $aDataForBorrowerOperations = array();
        $sql = 'SELECT
                    `id_project`,
                    SUM(montant + commission + tva)/100 AS montant,
                    -`commission`/100 AS commission,
                    -`tva`/100 AS tva,
                    DATE(date_echeance_emprunteur_reel) AS date
                FROM
                    `echeanciers_emprunteur`
                WHERE
                    `id_project` IN (' . implode(',', $aProjects) . ')
                    AND DATE(`date_echeance_emprunteur_reel`) BETWEEN ' . $sStartDate . ' AND ' . $sEndDate . '
                    AND `status_emprunteur` = 1
                    AND `status_ra` = 0
                GROUP BY
                    `id_project`,
                    DATE(`date_echeance_emprunteur_reel`)';

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
        $sql = 'SELECT
                        `id_project`,
                        montant/100 AS montant,
                        DATE(added) as date,
                        "remboursement-anticipe" AS type
                    FROM
                        `receptions`
                    WHERE
                        `remb_anticipe` = 1
                        AND `id_project` IN (' . implode(',', $aProjects) . ')
                        AND added BETWEEN ' . $sStartDate . ' AND ' . $sEndDate. '
                    GROUP BY `id_project`';

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
                    DATE(date_echeance)_reel AS date,
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
                        AND type_commission = 1';

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
     * Retrieve old pattern that lender must use in bank transfer label (with '?' or '' instead of accented characters)
     * @param $sClientId
     * @param $sMatchPattern
     * @return bool
     */
    public function isLenderPattern($sClientId, $sMatchPattern)
    {
        $this->get($sClientId);

        $aStrTrans = array(
            'À' => '?', 'à' => '?', 'Á' => '?', 'á' => '?', 'Â' => '?', 'â' => '?', 'Ã' => '?', 'ã' => '?', 'Ä' => '?',
            'ä' => '?', 'Å' => '?', 'å' => '?', 'Æ' => '?', 'æ' => '?', 'Ç' => '?', 'ç' => '?', 'È' => '?', 'è' => '?',
            'É' => '?', 'é' => '?', 'Ê' => '?', 'ê' => '?', 'Ë' => '?', 'ë' => '?', 'Ì' => '?', 'ì' => '?', 'Í' => '?',
            'í' => '?', 'Î' => '?', 'î' => '?', 'Ï' => '?', 'ï' => '?', 'Ñ' => '?', 'ñ' => '?', 'Ò' => '?', 'ò' => '?',
            'Ó' => '?', 'ó' => '?', 'Ô' => '?', 'ô' => '?', 'Õ' => '?', 'õ' => '?', 'Ö' => '?', 'ö' => '?', 'Ø' => '?',
            'ø' => '?', 'Œ' => '?', 'œ' => '?', 'ß' => '?', 'Ù' => '?', 'ù' => '?', 'Ú' => '?', 'ú' => '?',
            'Û' => '?', 'û' => 'u', 'Ü' => '?', 'ü' => '?', 'Ý' => '?', 'ý' => '?', 'Ÿ' => '?', 'ÿ' => '?'
        );

        $sPattern = str_replace(' ', '',
            str_pad($this->id_client, 6, 0, STR_PAD_LEFT)
            . mb_strtoupper(
                strtr(substr($this->prenom, 0, 1), $aStrTrans)
                . strtr($this->nom, $aStrTrans)
            ));

        if (false !== strpos($sMatchPattern, $sPattern) || false !== strpos($sMatchPattern, str_replace('?', '', $sPattern))) {
            return true;
        } else {
            return false;
        }
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
                    SELECT
                            DATE(csh.added)
                        FROM
                            clients_status_history csh
                            LEFT JOIN clients ON clients.id_client = csh.id_client
                            INNER JOIN clients_status cs ON csh.id_client_status = cs.id_client_status
                        WHERE
                            cs.status = ' . \clients_status::VALIDATED . '
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
                    NOT EXISTS (SELECT * FROM offres_bienvenues_details obd WHERE c.id_client = obd.id_client)
                    AND NOT EXISTS (SELECT * FROM transactions t WHERE t.id_type = ' . \transactions_types::TYPE_WELCOME_OFFER . ')
                    AND DATE(c.added) BETWEEN DATE("' . $sStartDate . '") AND DATE(' . $sEndDate . ') ' . $sWhereID;

        $resultat = $this->bdd->query($sql);

        $aClientsWithoutWelcomeOffer = array();
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $aClientsWithoutWelcomeOffer[] = $record;
        }

        return $aClientsWithoutWelcomeOffer;
    }

    public function getLenders($sWhere = null)
    {
        if (false === is_null($sWhere)) {
            $sWhere = ' WHERE ' . $sWhere;
        }

        $sql = 'SELECT *
                FROM `clients`
                INNER JOIN lenders_accounts la ON clients.id_client = la.id_client_owner'. $sWhere;

        $aClientsLender = array();

        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($result)) {
            $aClientsLender[] = $record;
        }

        return $aClientsLender;
    }

    public function getBorrowers($sWhere = null)
    {
        if (false === is_null($sWhere)) {
            $sWhere = ' WHERE ' . $sWhere;
        }

        $sql = 'SELECT *
                FROM `clients`
                INNER JOIN companies ON companies.id_client_owner = clients.id_client
                INNER JOIN projects ON companies.id_company = projects.id_company' . $sWhere;

        $aClientsBorrower = array();

        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($result)) {
            $aClientsBorrower[] = $record;
        }

        return $aClientsBorrower;
    }

}
