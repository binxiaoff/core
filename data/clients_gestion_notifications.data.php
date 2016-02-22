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
const TYPE_NOTIFICATION_IMMEDIATE = 'immediatement';
const TYPE_NOTIFICATION_DAILY     = 'quotidienne';
const TYPE_NOTIFICATION_WEEKLY    = 'hebdomadaire';
const TYPE_NOTIFICATION_MONTHLY   = 'mensuelle';
const TYPE_NOTIFICATION_NO_MAIL   = 'uniquement_notif';

class clients_gestion_notifications extends clients_gestion_notifications_crud
{

    public function __construct($bdd, $params = '')
    {
        parent::clients_gestion_notifications($bdd, $params);
    }

    public function get($list_field_value)
    {
        return parent::get($list_field_value);
    }

    public function update($list_field_value)
    {
        parent::update($list_field_value);
    }

    public function delete($list_field_value)
    {
        parent::delete($list_field_value);
    }

    public function create($list_field_value = array())
    {
        parent::create($list_field_value);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM clients_gestion_notifications' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM clients_gestion_notifications ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($list_field_value)
    {
        $list = '';
        foreach ($list_field_value as $champ => $valeur) {
            $list .= ' AND ' . $champ . ' = "' . $valeur . '" ';
        }

        $sql    = 'SELECT * FROM clients_gestion_notifications WHERE 1=1 ' . $list . ' ';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    // On recup les notifs du preteurs
    public function getNotifs($id_client)
    {
        $sql = 'SELECT * FROM clients_gestion_notifications WHERE id_client = "' . $id_client . '"';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[$record['id_notif']]['immediatement']    = $record['immediatement'];
            $result[$record['id_notif']]['quotidienne']      = $record['quotidienne'];
            $result[$record['id_notif']]['hebdomadaire']     = $record['hebdomadaire'];
            $result[$record['id_notif']]['mensuelle']        = $record['mensuelle'];
            $result[$record['id_notif']]['uniquement_notif'] = $record['uniquement_notif'];
            $result[$record['id_notif']]['immediatement']    = $record['immediatement'];
        }
        return $result;
    }

    // permet de savoir si un prêteur a coché la case d'un type de notif
    public function getNotif($id_client, $id_notif, $champ)
    {
        if ($this->counter('id_client = ' . $id_client . ' AND id_notif = ' . $id_notif . ' AND ' . $champ . ' = 1') > 0) {
            return true;
        } else {
            return false;
        }
    }


    // requte pour les cron de gestion d'alerte
    public function selectNotifs($champ = 'quotidienne', $id_notif = '', $start = '', $nb = '')
    {

        if ($id_notif != '') {
            $id_notif = ' AND cgmn.id_notif IN(' . $id_notif . ') ';
        }


        // quotidienne (19h30 tous les jours)
        if ($champ == 'quotidienne') {
            $date_now = date('Y-m-d H:i') . ':00';// 19:30:00
            //$date_now = '2015-03-30 19:30:00'; // <------------- test

            $date_now_time = strtotime($date_now);

            $date_moins1 = date('Y-m-d H:i',
                    mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time), date('d', $date_now_time) - 1, date('Y', $date_now_time))) . ':00';

            // Superieur à la date d'hier et infèrieur à la date du lancement de la requete
            $where = ' AND cgmn.date_notif > "' . $date_moins1 . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_quotidienne = 0 ' . $id_notif;
        } // hebdomadaire (chaque samedi matin à 9h00)
        elseif ($champ == 'hebdomadaire') {

            $date_now = date('Y-m-d H:i', mktime(9, 0, 0, date('m'), date('d'), date('Y'))) . ':00';
            //$date_now = '2015-03-30 09:00:00';// <------------- test

            $date_now_time = strtotime($date_now);

            $date_moins7 = date('Y-m-d H:i',
                    mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time), date('d', $date_now_time) - 7, date('Y', $date_now_time))) . ':00';

            // Superieur à il y a 7 jours 9h00 du matin et infèrieur à la date du jour à 9h00 du matin
            $where = ' AND cgmn.date_notif > "' . $date_moins7 . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_hebdomadaire = 0 ' . $id_notif;
        } // mensuelle (tous les 1er jours du mois à 9h00)
        elseif ($champ == 'mensuelle') {

            //$last_day_of_month = date('t'); // le 1er du mois a 09:00:00

            $date_now = date('Y-m-d H:i', mktime(9, 0, 0, date('m'), 1, date('Y'))) . ':00';

            $date_now_time = strtotime($date_now);

            $date_moins_1mois = date('Y-m-d H:i',
                    mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time) - 1, date('d', $date_now_time), date('Y', $date_now_time))) . ':00';
            // Supérieur au 1er du mois dernier et infèrieur au 1er du mois en cours
            $where = ' AND cgmn.date_notif > "' . $date_moins_1mois . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_mensuelle = 0 ' . $id_notif;
        }

        $sql = '
			SELECT
				cgmn.*
			FROM clients_gestion_mails_notif cgmn
			WHERE
				(SELECT cgn.' . $champ . ' FROM clients_gestion_notifications cgn WHERE cgn.id_client = cgmn.id_client AND cgn.id_notif = cgmn.id_notif) = 1 ' . $where . ' AND cgmn.' . $champ . ' = 0'
            . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }


    // requte pour les cron de gestion d'alerte
    public function selectNotifs_old($champ = 'quotidienne', $start = '', $nb = '')
    {

        // quotidienne (19h30 tous les jours)
        if ($champ == 'quotidienne') {
            $date_now = date('Y-m-d H:i') . ':00';// 19:30:00
            //$date_now = '2015-01-06 19:30:00'; // <------------- test

            $date_now_time = strtotime($date_now);

            $date_moins1 = date('Y-m-d H:i',
                    mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time), date('d', $date_now_time) - 1, date('Y', $date_now_time))) . ':00';


            $where = ' AND cgmn.date_notif > "' . $date_moins1 . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_quotidienne = 0 ';
        } // hebdomadaire (chaque samedi matin à 9h00)
        elseif ($champ == 'hebdomadaire') {

            $date_now = date('Y-m-d H:i', mktime(9, 0, 0, date('m'), date('d'), date('Y'))) . ':00';
            //$date_now = '2015-01-20 09:00:00';// <------------- test

            $date_now_time = strtotime($date_now);

            $date_moins7 = date('Y-m-d H:i',
                    mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time), date('d', $date_now_time) - 7, date('Y', $date_now_time))) . ':00';

            $where = ' AND cgmn.date_notif > "' . $date_moins7 . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_hebdomadaire = 0 ';

        } // mensuelle (tous les 1er jours du mois à 9h00)
        elseif ($champ == 'mensuelle') {

            //$last_day_of_month = date('t'); // le 1er du mois a 09:00:00

            $date_now = date('Y-m-d H:i', mktime(9, 0, 0, date('m'), 1, date('Y'))) . ':00';

            $date_now_time = strtotime($date_now);

            $date_moins_1mois = date('Y-m-d H:i',
                    mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time) - 1, date('d', $date_now_time), date('Y', $date_now_time))) . ':00';

            $where = ' AND cgmn.date_notif > "' . $date_moins_1mois . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_mensuelle = 0 ';
        }

        $sql = '
			SELECT
				cgmn.*
			FROM clients_gestion_mails_notif cgmn
			WHERE
				(SELECT cgn.' . $champ . ' FROM clients_gestion_notifications cgn WHERE cgn.id_client = cgmn.id_client AND cgn.id_notif = cgmn.id_notif) = 1 ' . $where . ' AND cgmn.' . $champ . ' = 0'
            . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    // requte pour les cron de gestion d'alerte
    public function selectNotifsByClient($id_client, $champ = 'quotidienne', $id_notif = '', $start = '', $nb = '')
    {

        if ($id_notif != '') {
            $id_notif = ' AND cgmn.id_notif IN(' . $id_notif . ') ';
        }


        // quotidienne (19h30 tous les jours)
        if ($champ == 'quotidienne') {
            $date_now = date('Y-m-d H:i') . ':00';// 19:30:00
            //$date_now = '2015-03-30 19:30:00'; // <------------- test

            if ($id_notif == 2) {
                $date_now = date('Y-m-d') . ' 20:00:00';// on fait tout le check a cette heure la
            }


            $date_now_time = strtotime($date_now);

            $date_moins1 = date('Y-m-d H:i',
                    mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time), date('d', $date_now_time) - 1, date('Y', $date_now_time))) . ':00';

            // Superieur à la date d'hier et infèrieur à la date du lancement de la requete
            $where = ' AND cgmn.date_notif > "' . $date_moins1 . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_quotidienne = 0 ' . $id_notif;
        } // hebdomadaire (chaque samedi matin à 9h00)
        elseif ($champ == 'hebdomadaire') {

            $date_now = date('Y-m-d H:i', mktime(9, 0, 0, date('m'), date('d'), date('Y'))) . ':00';
            //$date_now = '2015-03-30 09:00:00';// <------------- test

            $date_now_time = strtotime($date_now);

            $date_moins7 = date('Y-m-d H:i',
                    mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time), date('d', $date_now_time) - 7, date('Y', $date_now_time))) . ':00';

            // Superieur à il y a 7 jours 9h00 du matin et infèrieur à la date du jour à 9h00 du matin
            $where = ' AND cgmn.date_notif > "' . $date_moins7 . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_hebdomadaire = 0 ' . $id_notif;
        } // mensuelle (tous les 1er jours du mois à 9h00)
        elseif ($champ == 'mensuelle') {

            //$last_day_of_month = date('t'); // le 1er du mois a 09:00:00

            $date_now = date('Y-m-d H:i', mktime(9, 0, 0, date('m'), 1, date('Y'))) . ':00';

            $date_now_time = strtotime($date_now);

            $date_moins_1mois = date('Y-m-d H:i',
                    mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time) - 1, date('d', $date_now_time), date('Y', $date_now_time))) . ':00';
            // Supérieur au 1er du mois dernier et infèrieur au 1er du mois en cours
            $where = ' AND cgmn.date_notif > "' . $date_moins_1mois . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_mensuelle = 0 ' . $id_notif;
        }

        $sql = '
			SELECT
				cgmn.*
			FROM clients_gestion_mails_notif cgmn
			WHERE
				1 = 1 ' . $where . ' AND cgmn.id_client = ' . $id_client . ' AND cgmn.' . $champ . ' = 0'
            . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    // requte pour les cron de gestion d'alerte
    public function selectIdclientNotifs($champ = 'quotidienne', $id_notif = '', $start = '', $nb = '')
    {

        if ($id_notif != '') {
            $id_notif = ' AND cgmn.id_notif IN(' . $id_notif . ') ';
        }

        if ($id_notif == 2) {
            $date_now = date('Y-m-d') . ' 20:00:00';// on fait tout le check a cette heure la
        }

        // quotidienne (19h30 tous les jours)
        if ($champ == 'quotidienne') {
            $date_now = date('Y-m-d H:i') . ':00';// 19:30:00
            //$date_now = '2015-03-30 19:30:00'; // <------------- test

            $date_now_time = strtotime($date_now);

            $date_moins1 = date('Y-m-d H:i',
                    mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time), date('d', $date_now_time) - 1, date('Y', $date_now_time))) . ':00';

            // Superieur à la date d'hier et infèrieur à la date du lancement de la requete
            $where = ' AND cgmn.date_notif > "' . $date_moins1 . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_quotidienne = 0 ' . $id_notif;
        } // hebdomadaire (chaque samedi matin à 9h00)
        elseif ($champ == 'hebdomadaire') {

            $date_now = date('Y-m-d H:i', mktime(9, 0, 0, date('m'), date('d'), date('Y'))) . ':00';
            //$date_now = '2015-03-30 09:00:00';// <------------- test

            $date_now_time = strtotime($date_now);

            $date_moins7 = date('Y-m-d H:i',
                    mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time), date('d', $date_now_time) - 7, date('Y', $date_now_time))) . ':00';

            // Superieur à il y a 7 jours 9h00 du matin et infèrieur à la date du jour à 9h00 du matin
            $where = ' AND cgmn.date_notif > "' . $date_moins7 . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_hebdomadaire = 0 ' . $id_notif;
        } // mensuelle (tous les 1er jours du mois à 9h00)
        elseif ($champ == 'mensuelle') {

            //$last_day_of_month = date('t'); // le 1er du mois a 09:00:00

            $date_now = date('Y-m-d H:i', mktime(9, 0, 0, date('m'), 1, date('Y'))) . ':00';

            $date_now_time = strtotime($date_now);

            $date_moins_1mois = date('Y-m-d H:i',
                    mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time) - 1, date('d', $date_now_time), date('Y', $date_now_time))) . ':00';
            // Supérieur au 1er du mois dernier et infèrieur au 1er du mois en cours
            $where = ' AND cgmn.date_notif > "' . $date_moins_1mois . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_mensuelle = 0 ' . $id_notif;
        }

        $sql = '
			SELECT
				cgmn.id_client
			FROM clients_gestion_mails_notif cgmn
			WHERE
				1 = 1 ' . $where . ' AND cgmn.' . $champ . ' = 0 GROUP BY cgmn.id_client ' . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record['id_client'];
        }
        return $result;
    }
}