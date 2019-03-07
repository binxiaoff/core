<?php

use Doctrine\DBAL\Driver\Statement;
use Unilend\Bundle\CoreBusinessBundle\Entity\{AddressType, AttachmentType, Clients as ClientEntity, ClientsStatus, GreenpointAttachment, OperationSubType, Pays, Users, VigilanceRule, WalletType};

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
              SELECT c.id_client,
                CASE 
                  WHEN c.type IN (' . implode(',', [ClientEntity::TYPE_PERSON, ClientEntity::TYPE_PERSON_FOREIGNER]) . ') THEN cliad.zip
                  ELSE coad.zip 
                END AS cp
              FROM clients c
              LEFT JOIN client_address cliad ON c.id_address = cliad.id
              LEFT JOIN companies co ON c.id_client = co.id_client_owner
              LEFT JOIN company_address coad ON co.id_address = coad.id
              INNER JOIN wallet w ON c.id_client = w.id_client
              INNER JOIN wallet_type wt ON w.id_type = wt.id
              INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
              WHERE csh.id_status IN ('. implode(',', ClientsStatus::GRANTED_LOGIN) . ')
                AND (cliad.id_country = ' . Pays::COUNTRY_FRANCE . ' OR coad.id_country = ' . Pays::COUNTRY_FRANCE . ')
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
}
