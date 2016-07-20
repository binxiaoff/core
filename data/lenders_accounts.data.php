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

class lenders_accounts extends lenders_accounts_crud
{
    const LENDER_STATUS_ONLINE  = 1;
    const LENDER_STATUS_OFFLINE = 0;

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

    /**
     * @param null $lenderId
     * @return bool
     */
    public function isFrenchResident($lenderId = null)
    {
        $bResult = false;

        if (null === $lenderId) {
            $lenderId = $this->id_lender_account;
        }

        if ($lenderId) {
            $sQuery = "SELECT resident_etranger, MAX(added) FROM `lenders_imposition_history` WHERE id_lender = :lenderId";
            try {
                $result = $this->bdd->executeQuery($sQuery, array('lenderId' => $lenderId), array('lenderId' => \PDO::PARAM_INT), new \Doctrine\DBAL\Cache\QueryCacheProfile(300, md5(__METHOD__)))
                    ->fetch(PDO::FETCH_ASSOC);

                if (empty($result) || '0' === $result['resident_etranger']) {
                    $bResult = true;
                }
            } catch (\Doctrine\DBAL\DBALException $ex) {
                return null;
            }
        }
        return $bResult;
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
                $result = $this->bdd->executeQuery($sQuery, array('lenderId' => $lenderId), array(), new \Doctrine\DBAL\Cache\QueryCacheProfile(300, md5(__METHOD__)))
                    ->fetchAll(PDO::FETCH_ASSOC);

                if (isset($result[0]['type']) && in_array($result[0]['type'], array(1, 3))) {
                    $bResult = true;
                }
            } catch (\Doctrine\DBAL\DBALException $ex) {
                return false;
            }
        }
        return $bResult;
    }

    public function countCompaniesLenderInvestedIn($iLendersAccountId)
    {
        $sql = 'SELECT
                    COUNT(DISTINCT p.id_company)
                FROM
                    projects p
                    INNER JOIN loans l ON p.id_project = l.id_project
                    INNER JOIN projects_status_history psh ON l.id_project = psh.id_project
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE ps.status >= ' . \projects_status::REMBOURSEMENT . '
                AND
                    l.id_lender = ' . $iLendersAccountId;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function sumLoansOfProjectsInRepayment($iLendersAccountId)
    {
        $sql = 'SELECT
                    SUM(l.amount)
                FROM
                    `loans` l
                    INNER JOIN projects_last_status_history plsh ON l.id_project = plsh.id_project
                    INNER JOIN projects_status_history psh ON plsh.id_project_status_history = psh.id_project_status_history
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE
                    l.status = "0"
                    AND ps.status >= ' . \projects_status::REMBOURSEMENT . '
                    AND l.id_lender = ' . $iLendersAccountId;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0) / 100);
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
        $sClientStatus = $bOnlyActive ? ' INNER JOIN clients_status_history csh ON (csh.id_client = c.id_client  AND csh.id_client_status = 6)' : '';

        $sQuery = 'SELECT COUNT(DISTINCT(c.id_client))
                    FROM clients c
                    '. $sClientStatus .'
                    WHERE c.status = ' . \clients::STATUS_ONLINE;
        $statement = $this->bdd->executeQuery($sQuery);

        return $statement->fetchColumn(0);
    }

    public function countLendersByClientType($aClientType, $bOnlyActive = false)
    {
        $sClientStatus = $bOnlyActive ? ' INNER JOIN clients_status_history csh ON (csh.id_client = c.id_client  AND csh.id_client_status = 6)' : '';
        $aType         = array('clientTypes'  => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY, 'clientStatus' => \PDO::PARAM_INT);
        $aBind         = array('clientTypes' => $aClientType, 'clientStatus' => \clients::STATUS_ONLINE);

        $sQuery    = 'SELECT COUNT(DISTINCT(c.id_client))
                    FROM clients c
                    ' . $sClientStatus . '
                    WHERE c.type IN (:clientTypes) AND c.status = :clientStatus';
        $statement = $this->bdd->executeQuery($sQuery, $aBind, $aType);

        return $statement->fetchColumn(0);
    }



}
