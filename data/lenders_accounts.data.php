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

    function lenders_accounts($bdd, $params = '')
    {
        parent::lenders_accounts($bdd, $params);
    }

    function get($id, $field = 'id_lender_account')
    {
        return parent::get($id, $field);
    }

    function update($cs = '')
    {
        parent::update($cs);
    }

    function delete($id, $field = 'id_lender_account')
    {
        parent::delete($id, $field);
    }

    function create($cs = '')
    {
        $id = parent::create($cs);
        return $id;
    }

    function select($where = '', $order = '', $start = '', $nb = '')
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

    function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT count(*) FROM `lenders_accounts` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    function exist($id, $field = 'id_lender_account')
    {
        $sql    = 'SELECT * FROM `lenders_accounts` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    public function getValuesforTRI($lender)
    {
        $aValuesTRI = array();
        //get loans values as negativ , dates and project status
        $sql = 'SELECT (l.amount *-1) as loan,
					( SELECT psh.added
						FROM `projects_status_history` psh
						WHERE psh.id_project_status = "8"
						AND l.id_project = psh.id_project
						ORDER BY psh.added ASC LIMIT 1 ) as date
				  FROM loans l WHERE l.id_lender = ' . $lender . ';';

        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_array($result)) {
            $aValuesTRI[$record["date"]] = $record["loan"];
        }

        //get echeancier values
        $sql = 'SELECT
						e.montant as montant,
						e.date_echeance_reel as date_echeance_reel,
						e.date_echeance as date_echeance,
						e.status as echeance_status,
							(
							SELECT ps.status
							FROM projects_status ps
									LEFT JOIN projects_status_history psh ON (
									ps.id_project_status = psh.id_project_status)
									WHERE psh.id_project = p.id_project
									ORDER BY psh.added DESC LIMIT 1) as project_status
						FROM echeanciers e
							LEFT JOIN projects p ON e.id_project = p.id_project
							INNER JOIN loans l ON e.id_loan = l.id_loan
						WHERE e.id_lender = ' . $lender . ';';

        $result = $this->bdd->query($sql);

        $statusKo = array(projects_status::PROBLEME, projects_status::RECOUVREMENT);
        while ($record = $this->bdd->fetch_array($result)) {
            if (in_array($record["project_status"], $statusKo) && 0 === (int)$record["echeance_status"]) {
                $record["montant"] = 0;
            }

            if ($record["date_echeance_reel"] == "0000-00-00 00:00:00") {
                $record["date_echeance_reel"] = $record["date_echeance"];
            }

            if (array_key_exists($record["date_echeance_reel"], $aValuesTRI)) {
                $aValuesTRI[$record["date_echeance_reel"]] += $record["montant"];
            } else {
                $aValuesTRI[$record["date_echeance_reel"]] = $record["montant"];
            }
        }

        return $aValuesTRI;
    }

    public function getAttachments($lender)
    {

        $sql = 'SELECT a.id, a.id_type, a.id_owner, a.type_owner, a.path, a.added, a.updated, a.archived
				FROM attachment a
				WHERE a.id_owner = ' . $lender . '
					AND a.type_owner = "lenders_accounts";';

        $result      = $this->bdd->query($sql);
        $attachments = array();
        while ($record = $this->bdd->fetch_array($result)) {

            $attachments[$record["id_type"]] = $record;
        }
        return $attachments;

    }

    public function getInfosben($iOffset = '', $iLimit = '')
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

        $sql = 'SELECT DISTINCT (c.id_client), c.prenom, c.nom
                FROM clients c
                INNER JOIN lenders_accounts la ON la.id_client_owner = c.id_client
                INNER JOIN loans l on l.id_lender = la.id_lender_account
                INNER JOIN projects p ON p.id_project = l.id_project
                INNER JOIN projects_last_status_history plsh ON p.id_project = plsh.id_project
                INNER JOIN projects_status_history psh USING (id_project_status_history)
                INNER JOIN projects_status ps USING (id_project_status)
                WHERE ps.status > 80'. ' ' . $sLimit. ' '. $sOffset;

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }
}
