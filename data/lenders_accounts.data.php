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

use Unilend\librairies\CacheKeys;

class lenders_accounts extends lenders_accounts_crud
{
    const LENDER_STATUS_ONLINE  = 1;
    const LENDER_STATUS_OFFLINE = 0;

    const MONEY_TRANSFER_TYPE_BANK_TRANSFER = 1;
    const MONEY_TRANSFER_TYPE_CARD          = 2;


    public function __construct($bdd, $params = '')
    {
        parent::lenders_accounts($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `lenders_accounts`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `lenders_accounts` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_lender_account')
    {
        $sql    = 'SELECT * FROM `lenders_accounts` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    /**
     * @param int $iLendersAccountId unique identifier of the lender account
     * @return array of attachments
     */
    public function getAttachments($iLendersAccountId, $attachmentTypes = array())
    {

        $sql = 'SELECT a.id, a.id_type, a.id_owner, a.type_owner, a.path, a.added, a.updated, a.archived
                FROM attachment a
                WHERE a.id_owner = ' . $iLendersAccountId . '
                AND a.type_owner = "lenders_accounts"';

        if (false === empty($attachmentTypes)) {
            $sql .=  ' AND a.id_type IN ('. implode(',' , $attachmentTypes) . ')';
        }

        $result       = $this->bdd->query($sql);
        $aAttachments = array();
        while ($record = $this->bdd->fetch_array($result)) {
            $aAttachments[$record["id_type"]] = $record;
        }
        return $aAttachments;
    }

    public function getInfosben($iYear, $iLimit = null, $iOffset = null)
    {
        $sOffset = '';
        if (null !== $iOffset) {
            $iOffset = $this->bdd->escape_string($iOffset);
            $sOffset = 'OFFSET ' . $iOffset;
        }

        $sLimit = '';
        if (null !== $iLimit) {
            $iLimit  = $this->bdd->escape_string($iLimit);
            $sLimit = 'LIMIT ' . $iLimit;
        }

        $sql = 'SELECT DISTINCT c.id_client, c.prenom, c.nom
                FROM lenders_accounts la
                  INNER JOIN clients c ON (la.id_client_owner = c.id_client)
                  LEFT JOIN echeanciers e ON (e.id_lender = la.id_lender_account)
                WHERE YEAR(e.date_echeance_reel) = ' . $iYear . '
                  AND e.status = 1 ' . ' ' . $sLimit. ' '. $sOffset;

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function getLendersToMatchCity($iLimit)
    {
        $iLimit  = $this->bdd->escape_string($iLimit);

        $sql = 'SELECT * FROM (
                  SELECT c.id_client, ca.id_adresse, c.prenom, c.nom, ca.cp_fiscal AS zip, ca.ville_fiscal AS city, ca.cp, ca.ville, 0 AS is_company
                  FROM clients_adresses ca
                  INNER JOIN clients c ON ca.id_client = c.id_client
                  INNER JOIN lenders_accounts la ON la.id_client_owner = ca.id_client
                  WHERE c.status = 1
                      AND (ca.id_pays_fiscal = 1 OR ca.id_pays_fiscal = 0)
                      AND la.id_company_owner = 0
                      AND (
                        NOT EXISTS (SELECT cp FROM villes v WHERE v.cp = ca.cp_fiscal)
                        OR (SELECT COUNT(*) FROM villes v WHERE v.cp = ca.cp_fiscal AND v.ville = ca.ville_fiscal) <> 1
                      )
                  LIMIT '. floor($iLimit / 2).'
                ) perso
                UNION
                SELECT * FROM (
                    SELECT c.id_client, ca.id_adresse, c.prenom, c.nom, co.zip, co.city, ca.cp, ca.ville, 1 AS is_company
                    FROM clients_adresses ca
                      INNER JOIN clients c ON ca.id_client = c.id_client
                      INNER JOIN lenders_accounts la ON la.id_client_owner = ca.id_client
                      INNER JOIN companies co ON co.id_client_owner = ca.id_client
                    WHERE c.status = 1
                    AND (ca.id_pays_fiscal = 1 OR ca.id_pays_fiscal = 0)
                    AND (
                      NOT EXISTS (SELECT cp FROM villes v WHERE v.cp = co.zip)
                      OR (SELECT COUNT(*) FROM villes v WHERE v.cp = co.zip AND v.ville = co.city) <> 1
                    )  LIMIT '. floor($iLimit / 2).'
                ) company';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function getLendersToMatchBirthCity($iLimit = '', $iOffset = '')
    {
        $iOffset = $this->bdd->escape_string($iOffset);
        $iLimit  = $this->bdd->escape_string($iLimit);

        $sOffset = '';
        if ('' !== $iOffset) {
            $sOffset = 'OFFSET ' . $iOffset;
        }

        $sLimit = '';
        if ('' !== $iLimit) {
            $sLimit = 'LIMIT ' . $iLimit;
        }

        $sql = 'SELECT c.id_client, c.prenom, c.nom, c.ville_naissance
                FROM clients c
                INNER JOIN lenders_accounts la ON la.id_client_owner = c.id_client
                WHERE c.status = 1
                AND id_pays_naissance = 1
                AND c.insee_birth = ""
                ' . $sLimit. ' '. $sOffset;

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function isNaturalPerson($lenderId = null)
    {
        $bResult = false;

        if (null === $lenderId) {
            $lenderId = $this->id_lender_account;
        }

        if ($lenderId) {
            $sQuery = "SELECT c.type FROM lenders_accounts la INNER JOIN clients c ON c.id_client =  la.id_client_owner WHERE la.id_lender_account = :lenderId";
            try {
                $statement = $this->bdd->executeQuery($sQuery, ['lenderId' => $lenderId], ['lenderId' => \PDO::PARAM_INT], new \Doctrine\DBAL\Cache\QueryCacheProfile(300, md5(__METHOD__)));
                $result    = $statement->fetchAll(PDO::FETCH_ASSOC);
                $statement->closeCursor();

                if (isset($result[0]['type']) && in_array($result[0]['type'], array(1, 3))) {
                    $bResult = true;
                }
            } catch (\Doctrine\DBAL\DBALException $ex) {
                return false;
            }
        }
        return $bResult;
    }

    public function countCompaniesLenderInvestedIn($lenderId)
    {
        $query = '
            SELECT COUNT(DISTINCT p.id_company)
            FROM projects p
            INNER JOIN loans l ON p.id_project = l.id_project
            WHERE p.status >= :status AND l.id_lender = :lenderId';

        $statement = $this->bdd->executeQuery(
            $query,
            ['lenderId' => $lenderId, 'status' => \projects_status::REMBOURSEMENT],
            ['lenderId' => \PDO::PARAM_INT, 'status' => \PDO::PARAM_INT],
            new \Doctrine\DBAL\Cache\QueryCacheProfile(CacheKeys::SHORT_TIME, md5(__METHOD__))
        );
        $result    = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return (int) current($result[0]);
    }

    public function sumLoansOfProjectsInRepayment($lenderId)
    {
        $query = '
            SELECT IFNULL(SUM(l.amount), 0)
            FROM loans l
            INNER JOIN projects p ON l.id_project = p.id_project
            WHERE l.status = :loanStatus
                AND p.status >= :projectStatus
                AND l.id_lender = :lenderId';

        $statement = $this->bdd->executeQuery(
            $query,
            ['lenderId' => $lenderId, 'loanStatus' => \loans::STATUS_ACCEPTED, 'projectStatus' => \projects_status::REMBOURSEMENT],
            ['lenderId' => \PDO::PARAM_INT, 'loanStatus' => \PDO::PARAM_INT, 'projectStatus' => \PDO::PARAM_INT],
            new \Doctrine\DBAL\Cache\QueryCacheProfile(CacheKeys::SHORT_TIME, __FUNCTION__)
        );
        $result    = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return (int) current($result[0]);
    }

    public function getLendersSalesForce()
    {
        $sQuery = "SELECT
                      c.id_client as 'IDClient',
                      la.id_lender_account as 'IDPreteur',
                      c.id_langue as 'Langue',
                      REPLACE(c.source,',','') as 'Source1',
                      REPLACE(c.source2,',','') as 'Source2',
                      REPLACE(c.source3,',','') as 'Source3',
                      REPLACE(c.civilite,',','') as 'Civilite',
                      REPLACE(c.nom,',','') as 'Nom',
                      REPLACE(c.nom_usage,',','') as 'NomUsage',
                      REPLACE(c.prenom,',','') as 'Prenom',
                      REPLACE(c.fonction,',','') as 'Fonction',
                      CASE c.naissance
                      WHEN '0000-00-00' then '2001-01-01'
                      ELSE
                        CASE SUBSTRING(c.naissance,1,1)
                        WHEN '0' then '2001-01-01'
                        ELSE c.naissance
                        END
                      END as 'Datenaissance',
                      REPLACE(ville_naissance,',','') as 'Villenaissance',
                      ccountry.fr as 'PaysNaissance',
                      nv2.fr_f as 'Nationalite',
                      REPLACE(c.telephone,'\t','') as 'Telephone',
                      REPLACE(c.mobile,',','') as 'Mobile',
                      REPLACE(c.email,',','') as 'Email',
                      c.etape_inscription_preteur as 'EtapeInscriptionPreteur',
                      CASE c.type
                      WHEN 1 THEN 'Physique'
                      WHEN 2 THEN 'Morale'
                      WHEN 3 THEN 'Physique'
                      ELSE 'Morale'
                      END as 'TypeContact',
                      CASE cs.status
                      WHEN 6 THEN 'oui'
                      ELSE 'non'
                      END as 'Valide',
                      (
                        SELECT cs.label FROM clients_status_history cshs1
                        INNER JOIN clients_status cs on cshs1.id_client_status =cs.id_client_status
                        WHERE cshs1.id_client=c.id_client
                        ORDER BY cshs1.added DESC LIMIT 1
                      ) AS 'StatusCompletude',
                      CASE c.added
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.added
                      END as 'DateInscription',
                      CASE c.updated
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.updated
                      END as 'DateDerniereMiseaJour',
                      CASE c.lastlogin
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE c.lastlogin
                      END as 'DateDernierLogin',
                      cs.id_client_status as 'StatutValidation',
                      status_inscription_preteur as 'StatusInscription',
                      count(
                          distinct(l.id_project)
                      ) as 'NbPretsValides',
                      REPLACE(ca.adresse1,',','') as 'Adresse1',
                      REPLACE(ca.adresse2,',','') as 'Adresse2',
                      REPLACE(ca.adresse3,',','') as 'Adresse3',
                      REPLACE(ca.cp,',','') as 'CP',
                      REPLACE(ca.ville,',','') as 'Ville',
                      acountry.fr as 'Pays',
                      SUM(l.amount)/100 as 'TotalPretEur',
                      CASE p.id_prospect WHEN NULL THEN '' ELSE CONCAT('P', p.id_prospect) END AS 'DeletingProspect',
                      '0012400000K0Bxw' as 'Sfcompte'
                    FROM
                      clients c
                      INNER JOIN lenders_accounts la on la.id_client_owner = c.id_client
                      LEFT JOIN clients_adresses ca on c.id_client = ca.id_client
                      LEFT JOIN pays_v2 ccountry on c.id_pays_naissance = ccountry.id_pays
                      LEFT JOIN pays_v2 acountry on ca.id_pays = acountry.id_pays
                      LEFT JOIN nationalites_v2 nv2 on c.id_nationalite = nv2.id_nationalite
                      LEFT JOIN loans l on la.id_lender_account = l.id_lender and l.status = 0
                      LEFT JOIN clients_status cs on c.status = cs.id_client_status
                      LEFT JOIN prospects p ON p.email = c.email
                    WHERE c.status = 1
                    GROUP BY
                      c.id_client";

        return $this->bdd->executeQuery($sQuery);
    }

    /**
     * @param bool $bOnlyActive
     * if true only lenders activated at least once (active lenders)
     * if false all online lender (Community)
     */
    public function countLenders($bOnlyActive = false)
    {
        $clientStatus = $bOnlyActive ? ' INNER JOIN clients_status_history csh ON (csh.id_client = c.id_client AND csh.id_client_status = 6)' : '';

        $query = 'SELECT COUNT(DISTINCT(c.id_client))
                    FROM clients c
                    INNER JOIN lenders_accounts la ON c.id_client = la.id_client_owner
                    '. $clientStatus .'
                    WHERE c.status = ' . \clients::STATUS_ONLINE;
        $statement = $this->bdd->executeQuery($query);

        return $statement->fetchColumn(0);
    }

    public function countLendersByClientType($aClientType, $bOnlyActive = false)
    {
        $clientStatus = $bOnlyActive ? ' INNER JOIN clients_status_history csh ON (csh.id_client = c.id_client AND csh.id_client_status = 6)' : '';
        $type         = ['clientTypes' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY, 'clientStatus' => \PDO::PARAM_INT];
        $bind         = ['clientTypes' => $aClientType, 'clientStatus' => \clients::STATUS_ONLINE];

        $query    = 'SELECT COUNT(DISTINCT(c.id_client))
                        FROM clients c
                        INNER JOIN lenders_accounts la ON c.id_client = la.id_client_owner AND la.status = 1
                        ' . $clientStatus . '
                        WHERE c.type IN (:clientTypes) AND c.status = :clientStatus';
        $statement = $this->bdd->executeQuery($query, $bind, $type);

        return $statement->fetchColumn(0);
    }

    //@TODO @Mesbah kdo pour toi :)
    public function countProjectsForLenderByRegion($lenderId)
    {
        $bind = ['lenderId' => $lenderId];
        $type = ['lenderId' => \PDO::PARAM_INT];

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
                      COUNT(*) AS count,
                      sum(client_base.amount) / 100 AS loaned_amount,
                      avg(client_base.rate) AS average_rate
                    FROM (SELECT
                            companies.zip AS cp,
                            loans.amount,
                            loans.rate
                          FROM lenders_accounts
                              INNER JOIN loans ON loans.id_lender = lenders_accounts.id_lender_account
                              INNER JOIN projects ON loans.id_project = projects.id_project
                              INNER JOIN companies ON projects.id_company = companies.id_company
                          WHERE lenders_accounts.id_lender_account = :lenderId ) AS client_base
                    GROUP BY insee_region_code';

        $statement = $this->bdd->executeQuery($query, $bind, $type);
        $regionsCount  = array();
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $regionsCount[] = $row;
        }

        return $regionsCount;
    }

    /**
     * @param int $lenderId
     * @return array
     */
    public function getLenderTypeAndFiscalResidence($lenderId)
    {
        $sql = '
          SELECT
              max(id_lenders_imposition_history) AS id_lenders_imposition_history,
              CASE IFNULL(resident_etranger, 0)
                WHEN 0
                  THEN "fr"
                  ELSE "ww"
              END AS fiscal_address,
              CASE c.type
                WHEN ' . \clients::TYPE_LEGAL_ENTITY .
                  ' THEN "legal_entity"
                WHEN ' . \clients::TYPE_PERSON . ' OR ' . \clients::TYPE_PERSON_FOREIGNER .
                  ' THEN "person"
              END AS client_type
          FROM lenders_imposition_history lih
          INNER JOIN lenders_accounts la ON la.id_lender_account = lih.id_lender
          INNER JOIN clients c ON c.id_client = la.id_client_owner
          WHERE lih.id_lender = :id_lender';

        return $this->bdd->executeQuery($sql, ['id_lender' => $lenderId], ['id_lender' => \PDO::PARAM_INT])->fetch(\PDO::FETCH_ASSOC);
    }
}
