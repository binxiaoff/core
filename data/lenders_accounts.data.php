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

use Unilend\librairies\Cache;

class lenders_accounts extends lenders_accounts_crud
{

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
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    /**
     * @param int $iLendersAccountId unique identifier of the lender account
     * @return array of attachments
     */
    public function getAttachments($iLendersAccountId)
    {

        $sql = 'SELECT a.id, a.id_type, a.id_owner, a.type_owner, a.path, a.added, a.updated, a.archived
                FROM attachment a
                WHERE a.id_owner = ' . $iLendersAccountId . '
                AND a.type_owner = "lenders_accounts";';

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

    public function isFrenchResident($iLenderId = null)
    {
        if (null === $iLenderId) {
            $iLenderId = $this->id_lender_account;
        }

        if ($iLenderId) {
            $oCache  = Cache::getInstance();
            $sKey    = $oCache->makeKey('lenders_account', 'isFrenchResident', $iLenderId);
            $aRecord = $oCache->get($sKey);

            if (false === $aRecord) {
                $sQuery  = "SELECT resident_etranger, MAX(added) FROM `lenders_imposition_history` WHERE id_lender = $iLenderId";
                $oQuery  = $this->bdd->query($sQuery);
                $aRecord = $this->bdd->fetch_array($oQuery);
                $oCache->set($sKey, $aRecord);
            }
            if (empty($aRecord) || '0' === $aRecord['resident_etranger']) {
                return true;
            }
        }

        return false;
    }

    public function isNaturalPerson($iLenderId = null)
    {
        if (null === $iLenderId) {
            $iLenderId = $this->id_lender_account;
        }

        if ($iLenderId) {
            $oCache  = Cache::getInstance();
            $sKey    = $oCache->makeKey('lenders_account', 'isNaturalPerson', $iLenderId);
            $aRecord = $oCache->get($sKey);

            if (false === $aRecord) {
                $sQuery = "SELECT c.type FROM lenders_accounts la INNER JOIN clients c ON c.id_client =  la.id_client_owner WHERE la.id_lender_account = $iLenderId";
                $oQuery = $this->bdd->query($sQuery);
                $aRecord = $this->bdd->fetch_array($oQuery);
                $oCache->set($sKey, $aRecord);
            }

            if (isset($aRecord['type']) && in_array($aRecord['type'], array(1, 3))) {
                return true;
            }
        }
        return false;
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
}
