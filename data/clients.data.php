<?php

use Doctrine\DBAL\Driver\Statement;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, AttachmentType, Clients as ClientEntity, ClientsStatus, GreenpointAttachment, OperationSubType, PaysV2, WalletType
};

class clients extends clients_crud
{
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

    public function changePassword($email, $pass)
    {
        $this->bdd->query('
            UPDATE clients
            SET password = "' . password_hash($pass, PASSWORD_DEFAULT) . '",
            updated = NOW()
            WHERE email = "' . $email . '"'
        );
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function checkAccess(): bool
    {
        if (false === isset($_SESSION['auth']) || true !== $_SESSION['auth']) {
            return false;
        }

        if (false === isset($_SESSION['token']) || empty(trim($_SESSION['token']))) {
            return false;
        }

        $query = '
            SELECT COUNT(*) 
            FROM clients c
            INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id 
            WHERE c.id_client = ' . intval($_SESSION['client']['id_client']) . ' 
              AND c.password = "' . $this->bdd->escape_string($_SESSION['client']['password']) . '" 
              AND csh.id_status IN (' . implode(',', ClientsStatus::GRANTED_LOGIN) . ')';
        $statement = $this->bdd->query($query);

        return 1 != $this->bdd->result($statement, 0);
    }

    /**
     * @param string $searchType
     * @param string $nom
     * @param string $prenom
     * @param string $email
     * @param string $societe
     * @param string $siren
     *
     * @return array
     */
    public function searchEmprunteurs($searchType, $nom = '', $prenom = '', $email = '', $societe = '', $siren = ''): array
    {
        $conditions = [];

        if (false === empty($nom)) {
            $conditions[] = 'c.nom LIKE "%' . $nom . '%"';
        }

        if (false === empty($prenom)) {
            $conditions[] = 'c.prenom LIKE "%' . $prenom . '%"';
        }

        if (false === empty($email)) {
            $conditions[] = 'c.email LIKE "%' . $email . '%"';
        }

        if (false === empty($societe)) {
            $conditions[] = 'co.name LIKE "%' . $societe . '%"';
        }

        if (false === empty($siren)) {
            $conditions[] = 'co.siren LIKE "%' . $siren . '%"';
        }

        $borrowers = [];
        $query     = '
            SELECT 
                c.*,
                co.*, 
                COUNT(p.id_project) AS projets
            FROM clients c
            INNER JOIN companies co ON c.id_client = co.id_client_owner
            INNER JOIN projects p ON co.id_company = p.id_company
            INNER JOIN wallet w ON c.id_client = w.id_client
            INNER JOIN wallet_type wt ON w.id_type = wt.id AND wt.label = "' . WalletType::BORROWER . '"';

            if (false === empty($conditions)) {
                $query .= '
                    WHERE ' . implode(' ' . $searchType . ' ', $conditions);
            }

            $query .= '
                GROUP BY c.id_client
                ORDER BY c.id_client DESC
                LIMIT 100';

        $result = $this->bdd->query($query);
        while ($record = $this->bdd->fetch_assoc($result)) {
            $borrowers[] = $record;
        }
        return $borrowers;
    }

    public function totalmontantEmprunt($id_client)
    {
        $sql    = 'SELECT SUM(p.amount) AS total FROM clients c,companies co,projects p WHERE c.id_client = co.id_client_owner AND co.id_company = p.id_company AND c.id_client = ' . $id_client;
        $result = $this->bdd->query($sql);

        return $this->bdd->result($result, 0, 0);
    }

    /**
     * @param int         $status
     * @param string|null $order
     * @param int|null    $start
     * @param int|null    $nb
     *
     * @return array
     */
    public function selectPreteursByStatus(int $status, ?string $order = null, ?int $start = null, ?int $nb = null): array
    {
        $query = '
            SELECT
              c.*,
              csh.added AS added_status
            FROM clients c
            INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
            INNER JOIN wallet w ON c.id_client = w.id_client
            INNER JOIN wallet_type wt ON w.id_type = wt.id
            WHERE csh.id_status IN (' . $status . ') AND wt.label = "' . WalletType::LENDER . '"';

        if (null !== $order) {
            $query .= ' 
                ORDER BY ' . $order;
        }

        if (null !== $start && null !== $nb) {
            $query .= '
                LIMIT ' . $start . ', ' . $nb;
        } elseif (null !== $nb) {
            $query .= '
                LIMIT ' . $nb;
        }

        $result    = [];
        $statement = $this->bdd->query($query);

        while ($record = $this->bdd->fetch_assoc($statement)) {
            $result[] = $record;
        }
        return $result;
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

    public function isLender()
    {
        $query = 'SELECT COUNT(*) FROM wallet w
                    INNER JOIN wallet_type wt ON w.id_type = wt.id AND wt.label = "' . WalletType::LENDER . '"
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
     * @param int|string $clients
     *
     * @return array
     */
    public function getClientsWithNoWelcomeOffer($clients): array
    {
        if (1 !== preg_match('/^[1-9]+(,[0-9]*)*$/', $clients)) {
            return [];
        }

        $query = '
            SELECT
                c.id_client,
                c.nom,
                c.prenom,
                c.email,
                companies.name,
                DATE(c.added) AS date_creation,
                (
                    SELECT MAX(csh.added)
                    FROM clients_status_history csh
                    INNER JOIN clients ON clients.id_client = csh.id_client
                    WHERE csh.id_status = ' . ClientsStatus::STATUS_VALIDATED . ' AND c.id_client = csh.id_client
                    ORDER BY csh.added DESC
                    LIMIT 1
                ) AS date_validation
                FROM clients c
                INNER JOIN wallet w ON w.id_client = c.id_client
                LEFT JOIN companies ON c.id_client = companies.id_client_owner
                WHERE
                    c.id_client IN (' . $clients . ')
                    AND NOT EXISTS (SELECT obd.id_client FROM offres_bienvenues_details obd WHERE c.id_client = obd.id_client)
                    AND NOT EXISTS (SELECT o.id FROM operation o WHERE o.id_sub_type = (SELECT id FROM operation_sub_type WHERE label = "' . OperationSubType::UNILEND_PROMOTIONAL_OPERATION_WELCOME_OFFER . '") AND o.id_wallet_creditor = w.id)';

        $result = $this->bdd->query($query);

        $clientsWithoutWelcomeOffer = [];
        while ($record = $this->bdd->fetch_assoc($result)) {
            $clientsWithoutWelcomeOffer[] = $record;
        }

        return $clientsWithoutWelcomeOffer;
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

        $sQuery = '
            SELECT c.source
            FROM clients c
            INNER JOIN companies com on c.id_client = com.id_client_owner
            WHERE com.siren = ' . $sSiren . '
              AND DATE(c.added) BETWEEN "'. $oStartDate->format('Y-m-d') . '" AND "'. $oEndDate->format('Y-m-d') . '"
            ORDER BY c.added ASC 
            LIMIT 1';

        $rQuery = $this->bdd->query($sQuery);
        return ($this->bdd->result($rQuery, 0));
    }

    public function getLastSourceForSiren($sSiren, \DateTime $oStartDate = null, \DateTime $oEndDate = null)
    {
        if (false === is_null($oStartDate) && false === is_null($oEndDate)) {
            $oStartDate = new \DateTime('2013-01-01');
            $oEndDate = new \DateTime('NOW');
        }

        $sQuery = '
            SELECT c.source
            FROM clients c
            INNER JOIN companies com on c.id_client = com.id_client_owner
            WHERE com.siren = ' . $sSiren . '
              AND DATE(c.added) BETWEEN "'. $oStartDate->format('Y-m-d') . '" AND "'. $oEndDate->format('Y-m-d') . '"
            ORDER BY c.added DESC 
            LIMIT 1';

        $rQuery = $this->bdd->query($sQuery);
        return ($this->bdd->result($rQuery, 0));
    }

    /**
     * @return Statement
     * @throws Exception
     */
    public function getBorrowersSalesForce(): Statement
    {
        $query = "
            SELECT
              c.id_client AS 'IDClient',
              c.id_client AS 'IDClient_2',
              c.id_langue AS 'Langue',
              REPLACE(c.civilite,',','') AS 'Civilite',
              REPLACE(c.nom,',','') AS 'Nom',
              REPLACE(c.nom_usage,',','') AS 'Nom_usage',
              REPLACE(c.prenom,',','') AS 'Prenom',
              CONVERT(REPLACE(c.fonction,',','') USING utf8) AS 'Fonction',
              CASE c.naissance
                  WHEN '0000-00-00' then '2001-01-01'
                  ELSE
                    CASE SUBSTRING(c.naissance,1,1)
                        WHEN '0' then '2001-01-01'
                        ELSE c.naissance
                    END
              END AS 'DateNaissance',
              REPLACE(ville_naissance,',','') AS 'VilleNaissance',
              ccountry.fr AS 'PaysNaissance',
              nv2.fr_f AS 'Nationalite',
              REPLACE(c.telephone,'\t','') AS 'Telephone',
              c.mobile AS 'Mobile',
              REPLACE(c.email,',','') AS 'Email',
              c.etape_inscription_preteur AS 'EtapeInscriptionPreteur',
              CASE c.type
                WHEN 1 THEN 'Physique'
                WHEN 2 THEN 'Morale'
                WHEN 3 THEN 'Physique'
                ELSE 'Morale'
              END AS 'TypeContact',
              CASE csh.id_status
                WHEN " . ClientsStatus::STATUS_VALIDATED . " THEN 'oui'
                ELSE 'non'
              END AS 'Valide',
              CASE c.added
                WHEN '0000-00-00 00:00:00' then ''
                ELSE c.added
              END AS 'date_inscription',
              CASE c.updated
                WHEN '0000-00-00 00:00:00' then ''
                ELSE c.updated
              END AS 'DateMiseJour',
              CASE c.lastlogin
                WHEN '0000-00-00 00:00:00' then ''
                ELSE c.lastlogin
              END AS 'DateDernierLogin',
              REPLACE(ca.address,',','') AS 'Adresse1',
              '' AS 'Adresse2',
              '' AS 'Adresse3',
              REPLACE(ca.zip,',','') AS 'CP',
              REPLACE(ca.city,',','') AS 'Ville',
              acountry.fr AS 'Pays',
              '012240000002G4e' as 'Sfcompte'
            FROM clients c
              INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
              INNER JOIN companies co on c.id_client = co.id_client_owner
              INNER JOIN projects p ON p.id_company = co.id_company
              LEFT JOIN company_address ca ON co.id_company = ca.id_company AND id_type = (SELECT id FROM address_type WHERE label = '" . AddressType::TYPE_MAIN_ADDRESS . "') 
              LEFT JOIN pays_v2 ccountry on c.id_pays_naissance = ccountry.id_pays
              LEFT JOIN pays_v2 acountry on ca.id_country = acountry.id_pays
              LEFT JOIN nationalites_v2 nv2 on c.id_nationalite = nv2.id_nationalite
            GROUP BY c.id_client";

        return $this->bdd->executeQuery($query);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function countClientsByRegion(): array
    {
        $query = '
            SELECT
              CASE
                WHEN LEFT(client_base.cp, 2) IN (08, 10, 51, 52, 54, 55, 57, 67, 68, 88) THEN "44"
                WHEN LEFT(client_base.cp, 2) IN (16, 17, 19, 23, 24, 33, 40, 47, 64, 79, 86, 87) THEN "75"
                WHEN LEFT(client_base.cp, 2) IN (01, 03, 07, 15, 26, 38, 42, 43, 63, 69, 73, 74) THEN "84"
                WHEN LEFT(client_base.cp, 2) IN (21, 25, 39, 58, 70, 71, 89, 90) THEN "27"
                WHEN LEFT(client_base.cp, 2) IN (22, 29, 35, 56) THEN "53"
                WHEN LEFT(client_base.cp, 2) IN (18, 28, 36, 37, 41, 45) THEN "24"
                WHEN LEFT(client_base.cp, 2) IN (20) THEN "94"
                WHEN LEFT(client_base.cp, 3) IN (971) THEN "01"
                WHEN LEFT(client_base.cp, 3) IN (973) THEN "03"
                WHEN LEFT(client_base.cp, 2) IN (75, 77, 78, 91, 92, 93, 94, 95) THEN "11"
                WHEN LEFT(client_base.cp, 3) IN (974) THEN "04"
                WHEN LEFT(client_base.cp, 2) IN (09, 11, 12, 30, 31, 32, 34, 46, 48, 65, 66, 81, 82) THEN "76"
                WHEN LEFT(client_base.cp, 3) IN (972) THEN "02"
                WHEN LEFT(client_base.cp, 3) IN (976) THEN "06"
                WHEN LEFT(client_base.cp, 2) IN (02, 59, 60, 62, 80) THEN "32"
                WHEN LEFT(client_base.cp, 2) IN (14, 27, 50, 61, 76) THEN "28"
                WHEN LEFT(client_base.cp, 2) IN (44, 49, 53, 72, 85) THEN "52"
                WHEN LEFT(client_base.cp, 2) IN (04, 05, 06, 13, 83, 84) THEN "93"
                ELSE "0"
              END AS insee_region_code,
              COUNT(*) AS count
            FROM (
              SELECT clients.id_client,
                CASE 
                  WHEN clients.type IN (' . implode(',', [ClientEntity::TYPE_PERSON, ClientEntity::TYPE_PERSON_FOREIGNER]) . ') THEN clients_adresses.cp_fiscal
                  ELSE companies.zip 
                END AS cp
              FROM clients
              LEFT JOIN clients_adresses USING (id_client)
              LEFT JOIN companies ON clients.id_client = companies.id_client_owner
              INNER JOIN wallet w ON clients.id_client = w.id_client
              INNER JOIN wallet_type wt ON w.id_type = wt.id
              INNER JOIN clients_status_history csh ON clients.id_client_status_history = csh.id
              WHERE csh.id_status IN ('. implode(',', ClientsStatus::GRANTED_LOGIN) . ')
                AND (clients_adresses.id_pays_fiscal = ' . PaysV2::COUNTRY_FRANCE . ' OR companies.id_pays = ' . PaysV2::COUNTRY_FRANCE . ')
            ) AS client_base
            GROUP BY insee_region_code
            HAVING insee_region_code != "0"';

        $statement = $this->bdd->executeQuery($query);
        $regionsCount = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $regionsCount[] = $row;
        }

        return $regionsCount;
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
            'statusValid'            => GreenpointAttachment::STATUS_VALIDATION_VALID,
            'clientStatus'           => $clientStatus,
            'attachmentTypeIdentity' => AttachmentType::CNI_PASSPORTE,
            'attachmentTypeAddress'  => AttachmentType::JUSTIFICATIF_DOMICILE,
            'attachmentTypeRib'      => AttachmentType::RIB,
            'vigilanceStatus'        => $vigilanceStatusExcluded,
            'lenderWallet'           => WalletType::LENDER
        ];
        $type = [
            'statusValid'            => PDO::PARAM_INT,
            'clientStatus'           => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            'attachmentTypeIdentity' => PDO::PARAM_INT,
            'attachmentTypeAddress'  => PDO::PARAM_INT,
            'attachmentTypeRib'      => PDO::PARAM_INT,
            'vigilanceStatus'        => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            'lenderWallet'           => PDO::PARAM_STR
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
            FROM clients c 
            INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
            INNER JOIN (SELECT a.id_client, a.id, ga.validation_status from greenpoint_attachment ga INNER JOIN attachment a ON a.id = ga.id_attachment AND ga.validation_status = :statusValid AND a.id_type = :attachmentTypeIdentity AND a.archived IS NULL) ga_identity ON ga_identity.id_client = csh.id_client
            INNER JOIN (SELECT a.id_client, a.id, ga.validation_status from greenpoint_attachment ga INNER JOIN attachment a ON a.id = ga.id_attachment AND ga.validation_status = :statusValid AND a.id_type = :attachmentTypeAddress AND a.archived IS NULL) ga_address ON ga_address.id_client = csh.id_client
            INNER JOIN (SELECT a.id_client, a.id, ga.validation_status from greenpoint_attachment ga INNER JOIN attachment a ON a.id = ga.id_attachment AND ga.validation_status = :statusValid AND a.id_type = :attachmentTypeRib AND a.archived IS NULL) ga_rib ON ga_rib.id_client = csh.id_client
            INNER JOIN clients_adresses ca ON ca.id_client = c.id_client AND ca.id_pays_fiscal = " . PaysV2::COUNTRY_FRANCE . "
            INNER JOIN wallet w ON c.id_client = w.id_client
            INNER JOIN wallet_type wt ON w.id_type = wt.id AND wt.label = :lenderWallet
            LEFT JOIN (
              SELECT * 
              FROM client_vigilance_status_history cvsh
              WHERE cvsh.id = (
                SELECT cvsh_max.id
                FROM client_vigilance_status_history cvsh_max
                WHERE cvsh.id_client = cvsh_max.id_client
                ORDER BY cvsh_max.added DESC, cvsh_max.id DESC LIMIT 1
              )
            ) last_cvsh ON c.id_client = last_cvsh.id_client AND last_cvsh.vigilance_status IN (:vigilanceStatus)
            WHERE csh.id_status IN (:clientStatus)
              AND TIMESTAMPDIFF(YEAR, c.naissance, CURDATE()) < 80
              AND last_cvsh.id_client IS NULL";

        /** @var \Doctrine\DBAL\Statement $statement */
        $statement = $this->bdd->executeQuery($sql, $bind, $type);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
