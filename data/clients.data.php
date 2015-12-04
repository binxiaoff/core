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

use Unilend\librairies\tnmp;

class clients extends clients_crud
{

    const OCTROI_FINANCMENT = 1;
    const VIREMENT = 2;
    const COMMISSION_DEBLOCAGE = 3;
    const PRLV_MENSUALITE = 4;
    const AFF_MENSUALITE_PRETEURS = 5;
    const COMMISSION_MENSUELLE = 6;
    const REMBOURSEMENT_ANTICIPE = 7;
    const AFFECTATION_RA_PRETEURS = 8;


    public function __construct($bdd, $params = '')
    {
        parent::clients($bdd, $params);
    }

    public function get($id, $field = 'id_client')
    {
        return parent::get($id, $field);
    }

    public function delete($id, $field = 'id_client')
    {
        parent::delete($id, $field);
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
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
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
                LIMIT 1
                ';
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

        //die;
        if ($nb > 0) {
            return true;
        } else {
            return false;
        }
    }

    // permet de respecter les droits emprunteur et preteur
    // $statut = 1 : preteur | 2 : emprunteur 3 | : les deux
    // $restriction = preteur | empreunteur
    // $option = permet de restreindre le contenu emprunteur
    // $slug = chemin pour rediriger l'emprunteur sur une page
    public function checkStatusPreEmp($statut = '1', $restriction = 'preteur', $id_client = '', $option = '', $slug = '')
    {
        $reponse = false;

        if ($restriction == 'preteur') {
            if ($statut == 1 || $statut == 3) {
                $reponse = true;
                // on check si statut preteur valide
                if ($id_client != '' && ! $this->checkCompteCreate($id_client)) {
                    header('location:' . $this->lurl . '/inscription-preteurs');
                    die;
                }
            } else {
                $reponse = false;
            }
        }

        if ($restriction == 'emprunteur') {
            if ($statut == 2 || $statut == 3) {
                $reponse = true;
            } else {
                $reponse = false;
            }

            if ($option == 1) {
                $reponse = true;
                header('location:' . $this->lurl . '/' . $slug);
                die;
            }
        }

        if ($reponse == false) {
            $this->handleLogout();
        } else {
            return true;
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

    public function searchPreteurs($ref = '', $nom = '', $email = '', $prenom = '', $name = '', $noValide = '', $emprunteur = '', $start = '', $nb = '')
    {
        $where = 'WHERE 1 = 1 ';

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
        if ($name != '') {
            $where .= ' AND co.name LIKE "%' . $name . '%"';
        }

        if ($emprunteur != '') {
            $where .= ' AND c.status_pre_emp IN (2,3)';
        } else {
            if ($noValide != '') {
                $where .= ' AND c.status_pre_emp NOT IN (2,3)';
            } else {
                $where .= ' AND YEAR(NOW()) - YEAR(c.naissance) >= 18 AND c.status_pre_emp IN (1,3) AND status_inscription_preteur = 1';
            }
        }

        $sql = 'SELECT l.*,c.*,co.*
        FROM lenders_accounts l
        LEFT JOIN clients c ON c.id_client = l.id_client_owner
        LEFT JOIN companies co ON co.id_company = l.id_company_owner
        ' . $where . '
        GROUP BY l.id_lender_account
        ORDER BY l.id_lender_account DESC' . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

    public function searchPreteursV2($ref = '', $nom = '', $email = '', $prenom = '', $name = '', $noValide = '', $emprunteur = '', $start = '', $nb = '')
    {
        $where = 'WHERE 1 = 1 ';
        $and   = '';
        if ($ref != '') {
            $and .= ' AND c.id_client IN(' . $ref . ')';
        }
        if ($email != '') {
            $and .= ' AND c.email LIKE "%' . $email . '%"';
        }
        if ($prenom != '') {
            $and .= ' AND c.prenom LIKE "%' . $prenom . '%"';
        }
        if ($name != '') {
            $and .= ' AND co.name LIKE "%' . $name . '%"';
        }

        if ($emprunteur != '') {
            $and .= ' AND c.status_pre_emp IN (2,3)';
        } else {
            // inscription terminée
            if ($noValide == '1') {
                $and .= ' AND c.status_pre_emp NOT IN (2,3) AND c.status = 0 AND c.status_inscription_preteur = 1';
            } // inscription non terminée
            elseif ($noValide == '2') {
                $and .= ' AND c.status_pre_emp NOT IN (2,3) AND c.status = 0 AND c.status_inscription_preteur = 0';
            } else {
                $and .= ' AND YEAR(NOW()) - YEAR(c.naissance) >= 18 AND c.status_pre_emp IN (1,3) AND c.status_inscription_preteur = 1';
            }
        }

        // pour le OR on rajoute la condition derriere
        if ($nom != '') {
            $and .= ' AND c.nom LIKE "%' . $nom . '%" OR c.nom_usage LIKE "%' . $nom . '%" ' . $and;
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
            (SELECT ROUND(SUM(t.montant/100),2) FROM transactions t WHERE t.etat = 1 AND t.status = 1 AND t.id_client = c.id_client AND t.type_transaction NOT IN (9,6)) as solde,
            (SELECT COUNT(amount) FROM loans l WHERE l.id_lender = la.id_lender_account) as bids_valides,
            (SELECT COUNT(amount) FROM bids b WHERE b.id_lender_account = la.id_lender_account AND b.status = 0) as bids_encours,

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

    public function selectPreteurs($dateMoins1Mois)
    {
        $sql = '
        SELECT
            c.id_client,
           la.id_lender_account,
           c.type,
           la.exonere,
           la.debut_exoneration,
           la.fin_exoneration,
           e.id_project,
           e.id_loan,
           e.ordre,
           e.montant,
           e.capital,
           e.interets,
           e.prelevements_obligatoires,
           e.retenues_source,
           e.csg,
           e.prelevements_sociaux,
           e.contributions_additionnelles,
           e.prelevements_solidarite,
           e.crds,
           e.date_echeance,
           e.date_echeance_reel,
           e.status,
           e.date_echeance_emprunteur,
           e.date_echeance_emprunteur_reel
        FROM echeanciers e
        LEFT JOIN lenders_accounts la  ON la.id_lender_account = e.id_lender
        LEFT JOIN clients c ON c.id_client = la.id_client_owner
        WHERE LEFT(e.date_echeance_reel,7) = "' . $dateMoins1Mois . '" AND e.status = 1 ORDER BY e.date_echeance ASC';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    // presteurs by status
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

    public function isPrescripteur(prescripteurs $oPrescripteurs, $iClientId = null)
    {
        if (null === $iClientId) {
            $iClientId = $this->id_client;
        }

        return $oPrescripteurs->exist($iClientId, 'id_client');
    }

    public function isLender(lenders_accounts $oLendersAccounts, $iClientId = null)
    {
        if (null === $iClientId) {
            $iClientId = $this->id_client;
        }

        return $oLendersAccounts->exist($iClientId, 'id_client_owner');

    }

    public function isBorrower(projects $oProjects, $iClientId = null)
    {
        if (null === $iClientId) {
            $iClientId = $this->id_client;
        }

        $oCompanies = new \companies($this->bdd);
        $oCompanies->get($iClientId, 'id_client_owner');

        return $oProjects->exist($oCompanies->id_company, 'id_company');

    }


    public function generateTemporaryLink($iClientId = null)
    {
        if (null === $iClientId) {
            $iClientId = $this->id_client;
        }

        $oTemporaryLink = new \temporary_links_login($this->bdd);
        $sToken = md5($iClientId).md5(time());

        $oDateTime =  new \datetime('NOW + 1 week');
        $sExpiryDateTime = $oDateTime->format('Y-m-d H:i:s');

        $oTemporaryLink->id_client = $iClientId;
        $oTemporaryLink->token = $sToken;
        $oTemporaryLink->expires = $sExpiryDateTime;
        $oTemporaryLink->create();

        return $sToken;

    }

    public function sendEmailBorrower($iClientId = null, $sTypeEmail)
    {
        /* @var array $config */
        include __DIR__ . '/../config.php';

        if (null === $iClientId) {
            $iClientId = $this->id_client;
        }
        $this->get($iClientId);

        // @todo intl
        $oMailsText = new \mails_text($this->bdd);
        $oMailsText->get($sTypeEmail, 'lang = "fr" AND type');

        $oSettings = new \settings($this->bdd);
        $oSettings->get('Facebook', 'type');
        $sFacebookURL = $this->settings->value;
        $oSettings->get('Twitter', 'type');
        $sTwitterURL = $this->settings->value;

        $sTemporaryLink = $config['static_url'][$config['env']].'/espace_emprunteur/securite/'.$this->generateTemporaryLink($iClientId);


        $aVariables = array(
            'surl'                   => $config['static_url'][ $config['env'] ],
            'url'                    => $config['url'][ $config['env'] ]['default'],
            'link_compte_emprunteur' => $sTemporaryLink,
            'lien_fb'                => $sFacebookURL,
            'lien_tw'                => $sTwitterURL,
            'prenom'                 => $this->prenom
        );

        $sRecipient = $this->clients->email;
        $oTnmp = new \tnmp(array(new \nmp($this->bdd), new \nmp_desabo($this->bdd), $config['env']));


        $oEmail = new \email();
        $oEmail->setFrom($oMailsText->exp_email, utf8_decode($oMailsText->exp_name));
        $oEmail->setSubject(stripslashes(utf8_decode($oMailsText->subject)));
        $oEmail->setHTMLBody(stripslashes(strtr(utf8_decode($oMailsText->content), $oTnmp->constructionVariablesServeur($aVariables))));

        if ($config['env'] == 'prod') {
            Mailer::sendNMP(
                $oEmail,
                new \mails_filer($this->bdd),
                $oMailsText->id_textemail,
                $sRecipient,
                $aNMPResponse);
            $oTnmp->sendMailNMP(
                $aNMPResponse,
                $aVariables,
                $oMailsText->nmp_secure,
                $oMailsText->id_nmp,
                $oMailsText->nmp_unique,
                $oMailsText->mode);
        } else {
            $oEmail->addRecipient($sRecipient);
            Mailer::send($oEmail, new \mails_filer($this->bdd), $oMailsText->id_textemail);
        }
    }

    public function getDataForBorrowerOperations($iClientId = null, array $aProjects, $sStartDate = '"2013-01-01 00:00:00"', $sEndDate = 'NOW()', $iOperation = 0)
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

        $aDataForBorrowerOperations = array();

        foreach ($aOperations as $iOperation) {
            switch ($iOperation) {
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
                    return -1;
                } elseif ($aFirstArray['type'] == 'commission-mensuelle') {
                    return 1;
                }

                if ($aFirstArray['type'] == 'financement') {
                    return -1;
                } elseif ($aFirstArray['type'] == 'virement') {
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
                    psh.added AS date,
                    l.id_project
                FROM
                    `loans` l
                    INNER JOIN projects_status_history psh ON l.id_project = psh.id_project
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE
                    l.id_project IN (' . implode(',', $aProjects) . ')
                    AND ps.status = ' . \projects_status::REMBOURSEMENT . '
                    AND psh.added >= ' . $sStartDate . '
                    AND psh.added <= ' . $sEndDate . '
                GROUP BY
                    id_project';

        $result = $this->bdd->query($sql);

            while ($record = $this->bdd->fetch_assoc($result)) {
                $record['type']               = 'financement';
                $aDataForBorrowerOperations[] = $record;
            }
        return $aDataForBorrowerOperations;
    }

    private function getBorrowerOperationTransferFinancing($iClientId, $aProjects, $sStartDate, $sEndDate)
    {
        $aDataForBorrowerOperations = array();
        $sql = 'SELECT
                    montant/100 AS montant,
                    `date_transaction` AS date,
                    id_project
                FROM
                    `transactions`
                WHERE
                    `id_project` IN (' . implode(',', $aProjects) . ')
                    AND id_client = ' . $iClientId . '
                    AND date_transaction >= ' . $sStartDate . '
                    AND date_transaction <= ' . $sEndDate . '
                    AND `type_transaction` = 9
                GROUP BY
                    id_project';

        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($result)) {
            $record['type']               = 'virement';
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
                    `date_echeance_emprunteur_reel` AS date
                FROM
                    `echeanciers_emprunteur`
                WHERE
                    `id_project` IN (' . implode(',', $aProjects) . ')
                    AND DATE(`date_echeance_emprunteur_reel`) >= ' . $sStartDate . '
                    AND DATE(`date_echeance_emprunteur_reel`) <= ' . $sEndDate . '
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
                    `date_echeance_reel` AS date,
                    `ordre`
                FROM
                    `echeanciers`
                WHERE
                    `id_project` IN (' . implode(',', $aProjects) . ')
                    AND DATE(`date_echeance_reel`) >= ' . $sStartDate . '
                    AND DATE(`date_echeance_reel`) <= ' . $sEndDate . '
                    AND `status` = 1
                    AND `status_ra` = 0
                GROUP BY
                    `id_project`,
                    DATE(`date_echeance`)';

        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($result)) {
            $record['type']               = 'affectation-preteurs';
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
                        added as date
                    FROM
                        `receptions`
                    WHERE
                        `remb_anticipe` = 1
                        AND `id_project` IN (' . implode(',', $aProjects) . ')
                        AND added >= ' . $sStartDate . '
                        AND added <= ' . $sEndDate. '
                    GROUP BY `id_project`';

        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($result)) {
            $record['type']               = 'remboursement-anticipe';
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
                    date_echeance_reel AS date
                FROM
                    `echeanciers`
                WHERE
                    `id_project` IN (' . implode(',', $aProjects) . ')
                    AND `date_echeance_reel` >= ' . $sStartDate . '
                    AND `date_echeance_reel` <= ' . $sEndDate. '
                    AND `status` = 1
                    AND `status_ra` = 1
                GROUP BY
                    `date_echeance_reel`';

        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($result)) {
            $record['type']               = 'affectation-ra-preteur';
            $aDataForBorrowerOperations[] = $record;
        }

        return $aDataForBorrowerOperations;

    }


}
