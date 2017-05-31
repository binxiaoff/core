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

use Unilend\Bundle\CoreBusinessBundle\Entity\Clients AS clientEntity;
use Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;

class clients extends clients_crud
{
    //Type, Status, Subscription Step & Title constants moved to Entity

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
     */
    public function saveLogin(\DateTime $dateLogin)
    {
        if (false === empty($this->id_client) && is_numeric($this->id_client)){
            $bind = ['lastLogin' => $dateLogin->format('Y-m-d H:i:s'), 'id_client' => $this->id_client];
            $type = ['lastLogin' => \PDO::PARAM_STR, 'id_client' => \PDO::PARAM_STR];

            $query =  '
            UPDATE clients
            SET lastlogin = :lastLogin,
            updated = NOW()
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

    public function changePassword($email, $pass)
    {
        $this->bdd->query('
            UPDATE ' . $this->userTable . '
            SET ' . $this->userPass . ' = "' . password_hash($pass, PASSWORD_DEFAULT) . '",
            updated = NOW()
            WHERE ' . $this->userMail . ' = "' . $email . '"'
        );
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

    public function searchEmprunteurs($searchType, $nom, $prenom, $email = '', $societe = '', $siren = '')
    {
        $conditions = [
            'c.nom LIKE "%' . $nom . '%"',
            'c.prenom LIKE "%' . $prenom . '%"',
        ];

        if ($email != '') {
            $conditions[] = 'c.email LIKE "%' . $email . '%"';
        }

        if ($societe != '') {
            $conditions[] = 'co.name LIKE "%' . $societe . '%"';
        }

        if ($siren != '') {
            $conditions[] = 'co.siren LIKE "%' . $siren . '%"';
        }

        $result   = [];
        $query    = '
            SELECT 
                c.*,
                co.*
            FROM clients c
            INNER JOIN companies co ON c.id_client = co.id_client_owner
            WHERE ' . implode(' ' . $searchType . ' ', $conditions) . '
                AND (c.type IS NULL OR c.type NOT IN (' . implode(',', [ClientEntity::TYPE_PERSON, ClientEntity::TYPE_PERSON_FOREIGNER, ClientEntity::TYPE_LEGAL_ENTITY, ClientEntity::TYPE_LEGAL_ENTITY_FOREIGNER]) .'))
            GROUP BY c.id_client
            ORDER BY c.id_client DESC
           LIMIT 100';
        $resultat = $this->bdd->query($query);

        while ($record = $this->bdd->fetch_assoc($resultat)) {
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

        $sql = '
                SELECT
                  c.id_client AS id_client,
                  c.status AS status,
                  c.email AS email,
                  c.telephone AS telephone,
                  c.status_inscription_preteur AS status_inscription_preteur,
                  CASE c.type
                    WHEN ' . ClientEntity::TYPE_PERSON . ' OR ' . ClientEntity::TYPE_PERSON_FOREIGNER . ' THEN c.prenom
                    ELSE
                    (SELECT
                       CASE co.status_client
                       WHEN 1 THEN CONCAT(c.prenom," ",c.nom)
                       ELSE CONCAT(co.prenom_dirigeant," ",co.nom_dirigeant)
                       END as dirigeant
                     FROM companies co WHERE co.id_client_owner = c.id_client)
                  END AS prenom_ou_dirigeant,
                  CASE c.type
                    WHEN ' . ClientEntity::TYPE_PERSON . ' OR ' . ClientEntity::TYPE_PERSON_FOREIGNER . ' THEN c.nom
                  ELSE (SELECT co.name FROM companies co WHERE co.id_client_owner = c.id_client)
                  END AS nom_ou_societe,
                  CASE c.type
                    WHEN ' . ClientEntity::TYPE_PERSON . ' OR ' . ClientEntity::TYPE_PERSON_FOREIGNER . ' THEN REPLACE(c.nom_usage,"Nom D\'usage","")
                    ELSE ""
                  END AS nom_usage
                FROM clients c
                  INNER JOIN wallet w ON c.id_client = w.id_client AND w.id_type = (SELECT id FROM wallet_type WHERE label = "' . WalletType::LENDER . '")
                  LEFT JOIN companies co ON co.id_client_owner = c.id_client
            ' . $where . '
            GROUP BY c.id_client
            ORDER BY c.id_client DESC ' . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = [];

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
                com.id_company as id_company,
                w.wire_transfer_pattern as motif,
                w.available_balance as balance
            FROM clients c
            INNER JOIN (SELECT id_client, MAX(id_client_status_history) AS id_client_status_history FROM clients_status_history GROUP BY id_client) clsh ON c.id_client = clsh.id_client
            INNER JOIN clients_status_history csh ON clsh.id_client_status_history = csh.id_client_status_history
            INNER JOIN clients_status cs ON csh.id_client_status = cs.id_client_status
            INNER JOIN wallet w ON c.id_client = w.id_client
            LEFT JOIN companies com ON c.id_client = com.id_client_owner
            ' . $where . $status . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();

        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
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
        $query = 'SELECT COUNT(*) FROM wallet w
                    INNER JOIN wallet_type wt ON w.id_type = wt.id AND wt.label = ' . WalletType::LENDER . '
                  WHERE w.id_client = :idClient';

        $statement =  $this->bdd->executeQuery($query, ['idClient' => $this->id_client]);

        return ($statement->fetchColumn(0) == 1);
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

    /**
     * Retrieve pattern that lender must use in bank transfer label
     * @param int $idClient
     * @return string
     */
    public function getLenderPattern($idClient)
    {
        $query = 'SELECT wire_transfer_pattern FROM wallet WHERE id_client = :idClient';
        /** @var \Doctrine\DBAL\Statement $statement */
        $statement = $this->bdd->executeQuery($query, ['idClient' => $idClient]);

        return $statement->fetchColumn(0);
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
                    INNER JOIN wallet w ON w.id_client = c.id_client
                    LEFT JOIN companies ON c.id_client = companies.id_client_owner
                WHERE
                    DATE(c.added) BETWEEN "' . $sStartDate . '" AND ' . $sEndDate . '
                    AND NOT EXISTS (SELECT obd.id_client FROM offres_bienvenues_details obd WHERE c.id_client = obd.id_client)
                    AND NOT EXISTS (SELECT o.id FROM operation o WHERE o.id_type = (SELECT id FROM operation_type WHERE label = \'' . OperationType::UNILEND_PROMOTIONAL_OPERATION . '\') AND o.id_wallet_creditor = w.id)
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
                         CASE WHEN clients.type IN (' . implode(',', [ClientEntity::TYPE_PERSON, ClientEntity::TYPE_PERSON_FOREIGNER]) . ') THEN clients_adresses.cp_fiscal ELSE companies.zip END AS cp
                         FROM clients
                             LEFT JOIN clients_adresses USING (id_client)
                             LEFT JOIN companies ON clients.id_client = companies.id_client_owner
                             INNER JOIN wallet w ON clients.id_client = w.id_client
                             INNER JOIN wallet_type wt ON w.id_type = wt.id
                         WHERE clients.status = '. ClientEntity::STATUS_ONLINE .'
                         AND (clients_adresses.id_pays_fiscal = ' . PaysV2::COUNTRY_FRANCE . ' OR companies.id_pays = ' . PaysV2::COUNTRY_FRANCE . ')) AS client_base
                    GROUP BY insee_region_code
                    HAVING insee_region_code != "0"';

        $statement = $this->bdd->executeQuery($query);
        $regionsCount  = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $regionsCount[] = $row;
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
     * @param array $vigilanceStatusExcluded
     *
     * @return array
     */
    public function getClientsToAutoValidate(array $clientStatus, array $vigilanceStatusExcluded)
    {
        $bind = [
            'statusValid'            => \Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment::STATUS_VALIDATION_VALID,
            'clientStatus'           => $clientStatus,
            'attachmentTypeIdentity' => \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType::CNI_PASSPORTE,
            'attachmentTypeAddress'  => \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType::JUSTIFICATIF_DOMICILE,
            'attachmentTypeRib'      => \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType::RIB,
            'vigilanceStatus'        => $vigilanceStatusExcluded
        ];
        $type = [
            'statusValid'            => PDO::PARAM_INT,
            'clientStatus'           => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            'attachmentTypeIdentity' => PDO::PARAM_INT,
            'attachmentTypeAddress'  => PDO::PARAM_INT,
            'attachmentTypeRib'      => PDO::PARAM_INT,
            'vigilanceStatus'        => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY
        ];

        $sql = "
        SELECT
          c.id_client,
          ga_identity.id AS identity_attachment_id,
          ga_identity.validation_status identity_attachment_status,
          ga_address.id AS address_attachment_id,
          ga_address.validation_status address_attachment_status,
          ga_rib.id AS rib_attachment_id,
          ga_rib.validation_status rib_attachment_status
        
        FROM clients_status_history csh
          INNER JOIN (SELECT a.id_client, a.id, ga.validation_status from greenpoint_attachment ga INNER JOIN attachment a ON a.id = ga.id_attachment AND ga.validation_status = :statusValid AND a.id_type = :attachmentTypeIdentity AND a.archived IS NULL) ga_identity ON ga_identity.id_client = csh.id_client
          INNER JOIN (SELECT a.id_client, a.id, ga.validation_status from greenpoint_attachment ga INNER JOIN attachment a ON a.id = ga.id_attachment AND ga.validation_status = :statusValid AND a.id_type = :attachmentTypeAddress AND a.archived IS NULL) ga_address ON ga_address.id_client = csh.id_client
          INNER JOIN (SELECT a.id_client, a.id, ga.validation_status from greenpoint_attachment ga INNER JOIN attachment a ON a.id = ga.id_attachment AND ga.validation_status = :statusValid AND a.id_type = :attachmentTypeRib AND a.archived IS NULL) ga_rib ON ga_rib.id_client = csh.id_client
          INNER JOIN clients c ON c.id_client = csh.id_client
          INNER JOIN clients_adresses ca ON ca.id_client = c.id_client AND ca.id_pays_fiscal = 1
          INNER JOIN clients_status cs ON cs.id_client_status = csh.id_client_status
          LEFT JOIN (SELECT * FROM client_vigilance_status_history cvsh
                     WHERE cvsh.id = (SELECT cvsh_max.id
                                      FROM client_vigilance_status_history cvsh_max
                                      WHERE cvsh.id_client = cvsh_max.id_client
                                      ORDER BY cvsh_max.added DESC, cvsh_max.id DESC LIMIT 1)) last_cvsh ON c.id_client = last_cvsh.id_client AND last_cvsh.vigilance_status IN (:vigilanceStatus)
        WHERE csh.id_client_status_history = (SELECT csh_max.id_client_status_history
                                              FROM clients_status_history csh_max
                                              WHERE csh_max.id_client = csh.id_client
                                              ORDER BY csh_max.added DESC, csh_max.id_client_status_history DESC LIMIT 1)
          AND cs.status IN (:clientStatus)
          AND TIMESTAMPDIFF(YEAR, naissance, CURDATE()) < 80
          AND last_cvsh.id_client IS NULL";

        /** @var \Doctrine\DBAL\Statement $statement */
        $statement = $this->bdd->executeQuery($sql, $bind, $type);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
