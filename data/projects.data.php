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

class projects extends projects_crud
{

    function projects($bdd, $params = '')
    {
        parent::projects($bdd, $params);
    }

    function get($id, $field = 'id_project')
    {
        return parent::get($id, $field);
    }

    function update($cs = '')
    {
        parent::update($cs);
    }

    function delete($id, $field = 'id_project')
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
        if ($where != '')
            $where = ' WHERE ' . $where;
        if ($order != '')
            $order = ' ORDER BY ' . $order;
        $sql = 'SELECT * FROM `projects`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    function counter($where = '')
    {
        if ($where != '')
            $where = ' WHERE ' . $where;

        $sql = 'SELECT count(*) FROM `projects` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    function exist($id, $field = 'id_project')
    {
        $sql = 'SELECT * FROM `projects` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    function searchDossiers($date1 = '', $date2 = '', $montant = '', $duree = '', $status = '', $analyste = '', $siren = '', $id = '', $raison_sociale = '', $start = '', $nb = '')
    {
        $where = '';

        if ($date1 != '') {
            $where .= ' AND p.added >= "' . $date1 . ' 00:00:00"';
        }
        if ($date2 != '') {
            $where .= ' AND p.added <= "' . $date2 . ' 23:59:59"';
        }

        if ($montant != '0' && $montant != '') {
            $where .= ' AND p.amount = "' . $montant . '"';
        }
        if ($duree != '') {
            $where .= ' AND p.period = "' . $duree . '"';
        }
        if ($status != '') {
            $where .= ' AND ps.status IN (' . $status . ')';
        }
        if ($analyste != '0' && $analyste != '') {
            $where .= ' AND p.id_analyste = "' . $analyste . '"';
        }
        if ($siren != '') {
            $where .= ' AND co.siren LIKE "%' . $siren . '%"';
        }
        if ($id != '') {
            $where .= ' AND p.id_project = "' . $id . '"';
        }
        if ($raison_sociale != '0' && $raison_sociale != '') {
            $where .= ' AND co.name LIKE "%' . $raison_sociale . '%"';
        }

        $sSqlCount = 'SELECT
                            COUNT(*)
                        FROM
                            projects p
                            LEFT JOIN projects_last_status_history plsh on plsh.id_project = p.id_project
                            LEFT JOIN projects_status_history psh on psh.id_project_status_history = plsh.id_project_status_history
                            LEFT JOIN projects_status ps on ps.id_project_status = psh.id_project_status
                        WHERE
                            (ps.label != "" or ps.label is not null)
                        ' . $where;

        $rResult = $this->bdd->query($sSqlCount);
        $iCountProjects =  (int)($this->bdd->result($rResult, 0, 0));

        $sql = 'SELECT
                    p.*,
                    p.status as statusProject,
                    co.siren,
                    co.name,
                    ps.label,
                    ps.status
                FROM
                    projects p
                    LEFT JOIN companies co ON (p.id_company = co.id_company)
                    LEFT JOIN projects_last_status_history plsh on plsh.id_project = p.id_project
                    LEFT JOIN projects_status_history psh on psh.id_project_status_history = plsh.id_project_status_history
                    LEFT JOIN projects_status ps on ps.id_project_status = psh.id_project_status
                WHERE
                    (ps.label != "" or ps.label is not null)
                ' . $where . '
                ORDER BY p.added DESC
                ' . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);

        $result = array();
        $result[0] = $iCountProjects;

        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    function selectProjectsByStatus($status, $where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '')
            $where = ' WHERE 1 = 1 ' . $where . ' ';

        //if($order == '') $order = 'lestatut ASC,IF(lestatut = 2, p.date_retrait ,(select sum(amount*rate) FROM bids b where b.id_project = p.id_project)/(select sum(amount) FROM bids b where b.id_project = p.id_project)) DESC';
        if ($order == '')
            $order = 'lestatut ASC, p.date_retrait DESC';

        $sql = 'SELECT
				p.*,
				(SELECT ps.status FROM projects_status ps LEFT JOIN projects_status_history psh ON (ps.id_project_status = psh.id_project_status) WHERE psh.id_project = p.id_project ORDER BY psh.added DESC LIMIT 1) as status,
				CASE
					WHEN  (SELECT ps.status FROM projects_status ps LEFT JOIN projects_status_history psh ON (ps.id_project_status = psh.id_project_status) WHERE psh.id_project = p.id_project ORDER BY psh.added DESC LIMIT 1) = 50
					THEN "1"
					ELSE "2"
					END as lestatut
				FROM projects p
				' . $where . '
				HAVING status IN (' . $status . ')
				ORDER BY '
            . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));
        //mail('d.courtier@equinoa.com','test unilend sql',$sql);
        $resultat = $this->bdd->query($sql);
        $result = array();

        $positionStart = $start + $nb;

        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;

            // on récupere la derniere position pour demarrer une autre requete au meme niveau
            $result[0]['positionStart'] = $positionStart;
        }
        return $result;
    }

    // version slim
    function selectProjectsByStatusSlim($status, $where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '')
            $where = ' ' . $where . ' ';

        if ($order != '')
            $order = ' ORDER BY ' . $order;

        $sql = '
			SELECT
			p.id_project,
			p.date_publication_full
			FROM projects p
			WHERE (SELECT ps.status FROM projects_status ps LEFT JOIN projects_status_history psh ON (ps.id_project_status = psh.id_project_status) WHERE psh.id_project = p.id_project ORDER BY psh.added DESC LIMIT 1)  IN (' . $status . ')' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result = array();

        $positionStart = $start + $nb;

        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    function countSelectProjectsByStatus($status, $where = '')
    {
        if ($where != '')
            $where = ' WHERE 1 = 1 ' . $where . ' ';

        $sql = 'SELECT p.*,(SELECT ps.status FROM projects_status ps LEFT JOIN projects_status_history psh ON (ps.id_project_status = psh.id_project_status) WHERE psh.id_project = p.id_project ORDER BY psh.added DESC LIMIT 1) as status
				FROM projects p
				' . $where . '
				HAVING status IN (' . $status . ')
				ORDER BY p.added DESC
				';

        $resultat = $this->bdd->query($sql);

        $i = 0;
        while ($record = $this->bdd->fetch_array($resultat)) {
            $i++;
        }
        return $i;
    }

    function searchDossiersRemb($siren = '', $societe = '', $nom = '', $prenom = '', $projet = '', $email = '', $start = '', $nb = '')
    {
        if ($siren != '') {
            $where .= ' AND co.siren = "' . $siren . '"';
        }
        if ($societe != '') {
            $where .= ' AND co.name = "' . $societe . '"';
        }
        if ($nom != '') {
            $where .= ' AND c.nom = "' . $nom . '"';
        }
        if ($prenom != '') {
            $where .= ' AND c.prenom = "' . $prenom . '"';
        }
        if ($projet != '') {
            $where .= ' AND p.title_bo LIKE "%' . $projet . '%"';
        }
        if ($email != '') {
            $where .= ' AND c.email = "' . $email . '"';
        }


        $sql = 'SELECT p.*, co.*,c.*,
						(SELECT ps.status FROM projects_status ps LEFT JOIN projects_status_history psh ON (ps.id_project_status = psh.id_project_status) WHERE psh.id_project = p.id_project ORDER BY psh.added DESC LIMIT 1) as status_project
				FROM ((projects p
					LEFT JOIN companies co ON (p.id_company = co.id_company)
					LEFT JOIN clients c ON (co.id_client_owner = c.id_client)))
				WHERE 1=1
				' . $where . '
				HAVING status_project IN(80,60)
				ORDER BY p.added DESC
				' . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));
        $resultat = $this->bdd->query($sql);
        $result = array();

        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    function searchDossiersNoRemb($siren = '', $societe = '', $nom = '', $prenom = '', $projet = '', $email = '', $start = '', $nb = '')
    {
        if ($siren != '') {
            $where .= ' AND co.siren = "' . $siren . '"';
        }
        if ($societe != '') {
            $where .= ' AND co.name = "' . $societe . '"';
        }
        if ($nom != '') {
            $where .= ' AND c.nom = "' . $nom . '"';
        }
        if ($prenom != '') {
            $where .= ' AND c.prenom = "' . $prenom . '"';
        }
        if ($projet != '') {
            $where .= ' AND p.title_bo LIKE "%' . $projet . '%"';
        }
        if ($email != '') {
            $where .= ' AND c.email = "' . $email . '"';
        }


        $sql = 'SELECT p.*, co.*,c.*,
						(SELECT ps.status FROM projects_status ps LEFT JOIN projects_status_history psh ON (ps.id_project_status = psh.id_project_status) WHERE psh.id_project = p.id_project ORDER BY psh.added DESC LIMIT 1) as status_project
				FROM ((projects p
					LEFT JOIN companies co ON (p.id_company = co.id_company)
					LEFT JOIN clients c ON (co.id_client_owner = c.id_client)))
				WHERE 1=1
				' . $where . '
				HAVING status_project IN(100,110,120)
				ORDER BY p.added DESC
				' . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));
        $resultat = $this->bdd->query($sql);
        $result = array();

        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    function positionProject($id_project, $status = '', $order = '')
    {
        if ($status == '')
            $status = '50,60,80';
        // On recupere les en funding et les fundé
        $lProjets = $this->selectProjectsByStatus($status, ' AND p.display = 0 and p.status = 0', ($order != '' ? $order : 'p.date_publication DESC'));
        $tabProjects = array();

        foreach ($lProjets as $k => $p) {
            if ($p['id_project'] == $id_project) {
                $previous = $lProjets[$k - 1]['slug'];

                $next = $lProjets[$k + 1]['slug'];
                break;
            }
        }

        return array('previous' => $previous, 'next' => $next);
    }

    // liste les projets favoris dont la date de retrait est dans j-2
    function getDerniersFav($id_client)
    {
        $sql = 'SELECT * FROM `favoris` WHERE id_client = ' . $id_client;

        $resultat = $this->bdd->query($sql);
        $lesfav = '';
        $i = 0;
        while ($f = $this->bdd->fetch_array($resultat)) {
            $lesfav .= ($i > 0 ? ',' : '') . $f['id_project'];
            $i++;
        }

        $sql = 'SELECT *,DATEDIFF(date_retrait,CURRENT_DATE) as datediff FROM projects WHERE id_project IN (' . $lesfav . ') AND DATEDIFF(date_retrait,CURRENT_DATE)<=2 AND DATEDIFF(date_retrait,CURRENT_DATE)>=0 AND date_fin = "0000-00-00 00:00:00" ORDER BY datediff';

        $resultat = $this->bdd->query($sql);
        $result = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    function getFirstProject($id_company)
    {
        $sql = 'SELECT id_project
				FROM `projects`
				WHERE id_company = ' . $id_company . '
				ORDER BY added ASC
				LIMIT 1
				';

        $result = $this->bdd->query($sql);
        $id_project = (int)($this->bdd->result($result, 0, 0));

        return parent::get($id_project, 'id_project');
    }

    public function countProjectsByStatus($status)
    {
        if(is_array($status)){
            $statusString = implode(",", $status);
        }

        $sql = 'SELECT COUNT(*) FROM projects p WHERE(
    SELECT
			ps.status
		FROM
			projects_status ps
			LEFT JOIN projects_status_history psh ON (
            ps.id_project_status = psh.id_project_status
        )
		WHERE
			psh.id_project = p.id_project
		ORDER BY
			psh.added DESC
		LIMIT
			1
	) IN (' . $statusString . ');';

        $result = $this->bdd->query($sql);
        $record = $this->bdd->result($result);

        return $record;
    }


    public function countProjectsByStatusAndLender($lender, $status){

        if(is_array($status)){
            $statusString = implode(",", $status);
        }

        $sql = 'SELECT COUNT(DISTINCT l.id_project)
                FROM loans l
                INNER JOIN projects p ON l.id_project = p.id_project
                WHERE id_lender = ' . $lender . '
                AND l.status = 0
                AND
                    ( SELECT ps.status FROM projects_status ps
                    LEFT JOIN projects_status_history psh ON ( ps.id_project_status = psh.id_project_status )
                    WHERE psh.id_project = p.id_project ORDER BY
                    psh.added DESC LIMIT 1 ) IN (' . $statusString . ');';

        $result = $this->bdd->query($sql);
        $record = $this->bdd->result($result);

        return $record;
    }

    public function countProjectsSinceLendersubscription($client, $status){

        if(is_array($status)){
            $statusString = implode(",", $status);
        }

        $sql = 'SELECT	COUNT(*)
                FROM projects p
                WHERE  `date_publication_full` >= (
                        SELECT `added` FROM clients
		                WHERE id_client = '.$client.')
		                AND (
                              SELECT ps.status
		                      FROM projects_status ps
			                    LEFT JOIN projects_status_history psh ON (ps.id_project_status = psh.id_project_status)
		                        WHERE psh.id_project = p.id_project
		                    ORDER BY psh.added DESC LIMIT 1) IN ('.$statusString.');';
        $result = $this->bdd->query($sql);
        $record = $this->bdd->result($result);

        return $record;
    }

    public function getProjectsStatusAndCount($sListStatus, $sTabOrderProject, $iStart, $iLimit)
    {
        $oCache = Cache::getInstance();

        $sKey      = $oCache->makeKey(Cache::LIST_PROJECTS, $sListStatus);
        $aElements = $oCache->get($sKey);
        if (false === $aElements) {

            // Liste des projets en funding
            $alProjetsFunding = $this->selectProjectsByStatus($sListStatus, ' AND p.status = 0 AND p.display = 0', $sTabOrderProject, $iStart, $iLimit);
            // Nb projets en funding
            $anbProjects      = $this->countSelectProjectsByStatus($sListStatus, ' AND p.status = 0 AND p.display = 0');
            $aElements = array(
                'lProjectsFunding' => $alProjetsFunding,
                'nbProjects'       => $anbProjects
            );

            $oCache->set($sKey, $aElements);
        }

        return $aElements;
    }
}
