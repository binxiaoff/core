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

class projects extends projects_crud
{
    const MINIMUM_CREATION_DAYS_PROSPECT = 720;

    const MINIMUM_CREATION_DAYS = 1080;

    public function __construct($bdd, $params = '')
    {
        parent::projects($bdd, $params);
    }

    public function get($id, $field = 'id_project')
    {
        return parent::get($id, $field);
    }

    public function delete($id, $field = 'id_project')
    {
        parent::delete($id, $field);
    }

    public function create()
    {
        $this->id_project            = $this->bdd->escape_string($this->id_project);
        $this->hash                  = $this->bdd->escape_string($this->hash);
        $this->slug                  = $this->bdd->escape_string($this->slug);
        $this->id_company            = $this->bdd->escape_string($this->id_company);
        $this->id_partenaire         = $this->bdd->escape_string($this->id_partenaire);
        $this->id_partenaire_subcode = $this->bdd->escape_string($this->id_partenaire_subcode);
        $this->id_prescripteur       = $this->bdd->escape_string($this->id_prescripteur);
        $this->amount                = $this->bdd->escape_string($this->amount);
        $this->status_solde          = $this->bdd->escape_string($this->status_solde);
        $this->period                = $this->bdd->escape_string($this->period);
        $this->title                 = $this->bdd->escape_string($this->title);
        $this->title_bo              = $this->bdd->escape_string($this->title_bo);
        $this->photo_projet          = $this->bdd->escape_string($this->photo_projet);
        $this->lien_video            = $this->bdd->escape_string($this->lien_video);
        $this->comments              = $this->bdd->escape_string($this->comments);
        $this->nature_project        = $this->bdd->escape_string($this->nature_project);
        $this->objectif_loan         = $this->bdd->escape_string($this->objectif_loan);
        $this->presentation_company  = $this->bdd->escape_string($this->presentation_company);
        $this->means_repayment       = $this->bdd->escape_string($this->means_repayment);
        $this->type                  = $this->bdd->escape_string($this->type);
        $this->target_rate           = $this->bdd->escape_string($this->target_rate);
        $this->stand_by              = $this->bdd->escape_string($this->stand_by);
        $this->id_analyste           = $this->bdd->escape_string($this->id_analyste);
        $this->date_publication      = $this->bdd->escape_string($this->date_publication);
        $this->date_publication_full = $this->bdd->escape_string($this->date_publication_full);
        $this->date_retrait          = $this->bdd->escape_string($this->date_retrait);
        $this->date_retrait_full     = $this->bdd->escape_string($this->date_retrait_full);
        $this->date_fin              = $this->bdd->escape_string($this->date_fin);
        $this->create_bo             = $this->bdd->escape_string($this->create_bo);
        $this->risk                  = $this->bdd->escape_string($this->risk);
        $this->retour_altares        = $this->bdd->escape_string($this->retour_altares);
        $this->process_fast          = $this->bdd->escape_string($this->process_fast);
        $this->remb_auto             = $this->bdd->escape_string($this->remb_auto);
        $this->status                = $this->bdd->escape_string($this->status);
        $this->stop_relances         = $this->bdd->escape_string($this->stop_relances);
        $this->display               = $this->bdd->escape_string($this->display);
        $this->added                 = $this->bdd->escape_string($this->added);
        $this->updated               = $this->bdd->escape_string($this->updated);

        $this->bdd->query('INSERT INTO `projects` (`hash`,`slug`,`id_company`,`id_partenaire`,`id_partenaire_subcode`,`id_prescripteur`,`amount`,`status_solde`,`period`,`title`,`title_bo`,`photo_projet`,`lien_video`,`comments`,`nature_project`,`objectif_loan`,`presentation_company`,`means_repayment`,`type`,`target_rate`,`stand_by`,`id_analyste`,`date_publication`,`date_publication_full`,`date_retrait`,`date_retrait_full`,`date_fin`,`create_bo`,`risk`,`retour_altares`,`process_fast`,`remb_auto`,`status`,`stop_relances`,`display`,`added`,`updated`) VALUES(MD5(CONCAT(UUID(), NOW())),"' . $this->slug . '","' . $this->id_company . '","' . $this->id_partenaire . '","' . $this->id_partenaire_subcode . '","' . $this->id_prescripteur . '","' . $this->amount . '","' . $this->status_solde . '","' . $this->period . '","' . $this->title . '","' . $this->title_bo . '","' . $this->photo_projet . '","' . $this->lien_video . '","' . $this->comments . '","' . $this->nature_project . '","' . $this->objectif_loan . '","' . $this->presentation_company . '","' . $this->means_repayment . '","' . $this->type . '","' . $this->target_rate . '","' . $this->stand_by . '","' . $this->id_analyste . '","' . $this->date_publication . '","' . $this->date_publication_full . '","' . $this->date_retrait . '","' . $this->date_retrait_full . '","' . $this->date_fin . '","' . $this->create_bo . '","' . $this->risk . '","' . $this->retour_altares . '","' . $this->process_fast . '","' . $this->remb_auto . '","' . $this->status . '","' . $this->stop_relances . '","' . $this->display . '",NOW(),NOW())');

        $this->get($this->bdd->insert_id(), 'id_project');

        $this->bdd->controlSlug('projects', $this->slug, 'id_project', $this->id_project);

        return $this->id_project;
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
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

    public function counter($where = '')
    {
        if ($where != '')
            $where = ' WHERE ' . $where;

        $sql = 'SELECT count(*) FROM `projects` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_project')
    {
        $sql = 'SELECT * FROM `projects` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    public function searchDossiers($date1 = '', $date2 = '', $montant = '', $duree = '', $status = '', $analyste = '', $siren = '', $id = '', $raison_sociale = '', $start = '', $nb = '')
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

    public function selectProjectsByStatus($status, $where = '', $order = '', $start = '', $nb = '')
    {
        if ($order == '') {
            $order = 'lestatut ASC, p.date_retrait DESC';
        }

        $sql = '
          SELECT p.*,
              projects_status.status,
              CASE WHEN projects_status.status = 50
                THEN "1"
                ELSE "2"
              END AS lestatut
            FROM projects p
            LEFT JOIN projects_last_status_history USING (id_project)
            LEFT JOIN projects_status_history USING (id_project_status_history)
            LEFT JOIN projects_status USING (id_project_status)
            WHERE projects_status.status IN (' . $status . ')' .
            $where . '
            ORDER BY ' . $order .
            ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $result        = array();
        $resultat      = $this->bdd->query($sql);
        $positionStart = $start + $nb;

        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
            // on récupere la derniere position pour demarrer une autre requete au meme niveau
            $result[0]['positionStart'] = $positionStart;
        }
        return $result;
    }

    // version slim
    public function selectProjectsByStatusSlim($status, $where = '', $order = '', $start = '', $nb = '')
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

    public function countSelectProjectsByStatus($status, $where = '')
    {
        $sql = '
            SELECT COUNT(*)
            FROM projects p
            LEFT JOIN projects_last_status_history ON p.id_project = projects_last_status_history.id_project
            LEFT JOIN projects_status_history ON projects_last_status_history.id_project_status_history = projects_status_history.id_project_status_history
            LEFT JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
            WHERE projects_status.status IN (' . $status . ')' . $where;

        return current($this->bdd->fetch_assoc($this->bdd->query($sql)));
    }

    public function searchDossiersRemb($siren = '', $societe = '', $nom = '', $prenom = '', $projet = '', $email = '', $start = '', $nb = '')
    {
        $where = '';
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

        $sql = '
            SELECT p.*,
                co.*,
                c.*,
                (SELECT ps.status FROM projects_status ps LEFT JOIN projects_status_history psh ON (ps.id_project_status = psh.id_project_status) WHERE psh.id_project = p.id_project ORDER BY psh.added DESC LIMIT 1) as status_project
            FROM ((projects p
            LEFT JOIN companies co ON (p.id_company = co.id_company)
            LEFT JOIN clients c ON (co.id_client_owner = c.id_client)))
            WHERE 1 = 1 ' . $where . '
            HAVING status_project IN(80, 60)
            ORDER BY p.added DESC
            ' . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));
        $resultat = $this->bdd->query($sql);
        $result = array();

        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function searchDossiersNoRemb($siren = '', $societe = '', $nom = '', $prenom = '', $projet = '', $email = '', $start = '', $nb = '')
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

        $sql = '
            SELECT p.*,
                co.*,
                c.*,
                (SELECT ps.status FROM projects_status ps LEFT JOIN projects_status_history psh ON (ps.id_project_status = psh.id_project_status) WHERE psh.id_project = p.id_project ORDER BY psh.added DESC LIMIT 1) as status_project
            FROM ((projects p
            LEFT JOIN companies co ON (p.id_company = co.id_company)
            LEFT JOIN clients c ON (co.id_client_owner = c.id_client)))
            WHERE 1 = 1 ' . $where . '
            HAVING status_project IN(100, 110, 120)
            ORDER BY p.added DESC
            ' . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));
        $resultat = $this->bdd->query($sql);
        $result = array();

        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function positionProject($id_project, $status = '', $order = '')
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
    public function getDerniersFav($id_client)
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

    public function getLastProject($id_company)
    {
        $sql = 'SELECT id_project
                FROM projects
                WHERE id_company = ' . $id_company . '
                ORDER BY added DESC
                LIMIT 1';

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
                    psh.added DESC LIMIT 1) IN (' . $statusString . ')';

        $result = $this->bdd->query($sql);
        $record = $this->bdd->result($result);

        return $record;
    }

    public function countProjectsSinceLendersubscription($client, $status){

        if(is_array($status)){
            $statusString = implode(",", $status);
        }

        $sql = 'SELECT    COUNT(*)
                FROM projects p
                WHERE  `date_publication_full` >= (
                        SELECT `added` FROM clients
                        WHERE id_client = '.$client.')
                        AND (
                              SELECT ps.status
                              FROM projects_status ps
                                LEFT JOIN projects_status_history psh ON (ps.id_project_status = psh.id_project_status)
                                WHERE psh.id_project = p.id_project
                            ORDER BY psh.added DESC LIMIT 1) IN ('.$statusString.')';
        $result = $this->bdd->query($sql);
        $record = $this->bdd->result($result);

        return $record;
    }

    public function getAttachments($project = null)
    {

        if (null === $project) {
            $project = $this->id_project;
        }

        if (! $project) {
            return false;
        }

        $sql = 'SELECT a.id, a.id_type, a.id_owner, a.type_owner, a.path, a.added, a.updated, a.archived
                FROM attachment a
                WHERE a.id_owner = ' . $project . '
                    AND a.type_owner = "projects"';

        $result      = $this->bdd->query($sql);
        $attachments = array();
        while ($record = $this->bdd->fetch_array($result)) {
            $attachments[$record["id_type"]] = $record;
        }
        return $attachments;
    }
}
