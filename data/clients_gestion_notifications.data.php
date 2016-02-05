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

class clients_gestion_notifications extends clients_gestion_notifications_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::clients_gestion_notifications($bdd, $params);
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

        $result   = array();
        $resultat = $this->bdd->query('SELECT * FROM clients_gestion_notifications' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : '')));
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

        $result = $this->bdd->query('SELECT COUNT(*) FROM clients_gestion_notifications ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($list_field_value)
    {
        $list = '';
        foreach ($list_field_value as $champ => $valeur) {
            $list .= ' AND ' . $champ . ' = "' . $valeur . '"';
        }

        $result = $this->bdd->query('SELECT * FROM clients_gestion_notifications WHERE 1 = 1 ' . $list);
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    // On recup les notifs du preteurs
    public function getNotifs($id_client)
    {
        $result   = array();
        $resultat = $this->bdd->query('SELECT * FROM clients_gestion_notifications WHERE id_client = "' . $id_client . '"');
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[$record['id_notif']] = array(
                'immediatement'    => $record['immediatement'],
                'quotidienne'      => $record['quotidienne'],
                'hebdomadaire'     => $record['hebdomadaire'],
                'mensuelle'        => $record['mensuelle'],
                'uniquement_notif' => $record['uniquement_notif']
            );
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

    /**
     * Retrieve the list of mail notifications for customers who have notification of the given type
     * @param string $sFrequency
     * @param int $iNotificationType
     * @return array
     */
    public function getCustomersByNotification($sFrequency, $iNotificationType)
    {
        $aResult = array();
        $rResult = $this->bdd->query(
            $this->getCustomerNotificationQuery($sFrequency, $iNotificationType, 'id_client') . '
            GROUP BY cgn.id_client
            ORDER BY cgn.id_client ASC'
        );
        while ($aRecord = $this->bdd->fetch_assoc($rResult)) {
            $aResult[] = (int) $aRecord['id_client'];
        }
        return $aResult;
    }

    public function getCustomersNotifications(array $aCustomerId, $sFrequency, $iNotificationType)
    {
        $aResult = array();
        $rResult = $this->bdd->query(
            $this->getCustomerNotificationQuery($sFrequency, $iNotificationType) . '
            AND cgn.id_client IN (' . implode(',', $aCustomerId) . ')'
        );
        while ($aRecord = $this->bdd->fetch_assoc($rResult)) {
            $aResult[] = $aRecord;
        }
        return $aResult;
    }

    private function getCustomerNotificationQuery($sFrequency, $iNotificationType, $sFieldName = null)
    {
        if ($sFrequency === 'quotidienne') { // Quotidienne (chaque jour à 20h00)
            $where = '
                cgmn.date_notif > "' . date('Y-m-d H:i:s', mktime(20, 0, 0, date('m'), date('d') - 2, date('Y'))) . '"
                AND cgmn.date_notif <= "' . date('Y-m-d H:i:s', mktime(20, 0, 0, date('m'), date('d'), date('Y'))) . '"';
        } elseif ($sFrequency === 'hebdomadaire') { // Hebdomadaire (chaque samedi matin à 9h00)
            $where = '
                cgmn.date_notif > "' . date('Y-m-d H:i:s', mktime(9, 0, 0, date('m'), date('d') - 7, date('Y'))) . '"
                AND cgmn.date_notif <= "' . date('Y-m-d H:i:s', mktime(9, 0, 0, date('m'), date('d'), date('Y'))) . '"';
        } elseif ($sFrequency === 'mensuelle') { // Mensuelle (tous les 1er jours du mois à 9h00)
            $where = '
                cgmn.date_notif > "' . date('Y-m-d H:i:s', mktime(9, 0, 0, date('m') - 1, 1, date('Y'))) . '"
                AND cgmn.date_notif <= "' . date('Y-m-d H:i:s', mktime(9, 0, 0, date('m'), 1, date('Y'))) . '"';
        }
        return '
            SELECT cgmn.' . (is_null($sFieldName) ? '*' : $sFieldName) . '
            FROM clients_gestion_mails_notif cgmn
            INNER JOIN clients_gestion_notifications cgn ON (cgn.id_client = cgmn.id_client AND cgn.id_notif = cgmn.id_notif)
            WHERE ' . $where . '
                AND cgn.' . $sFrequency . ' = 1
                AND cgmn.' . $sFrequency . ' = 0
                AND cgmn.status_check_' . $sFrequency . ' = 0
                AND cgmn.id_notif = ' . $iNotificationType;

    }

    // requte pour les cron de gestion d'alerte
    public function selectNotifsByClient($id_client, $champ = 'quotidienne', $id_notif = '')
    {
        if ($id_notif != '') {
            $id_notif = ' AND cgmn.id_notif IN(' . $id_notif . ') ';
        }

        // quotidienne (19h30 tous les jours)
        if ($champ == 'quotidienne') {
            $date_now = date('Y-m-d H:i') . ':00';// 19:30:00

            if ($id_notif == 2) {
                $date_now = date('Y-m-d') . ' 20:00:00';// on fait tout le check a cette heure la
            }

            $date_now_time = strtotime($date_now);
            $date_moins1   = date('Y-m-d H:i', mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time), date('d', $date_now_time) - 1, date('Y', $date_now_time))) . ':00';

            // Superieur à la date d'hier et infèrieur à la date du lancement de la requete
            $where = ' AND cgmn.date_notif > "' . $date_moins1 . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_quotidienne = 0 ' . $id_notif;
        } elseif ($champ == 'hebdomadaire') { // hebdomadaire (chaque samedi matin à 9h00)
            $date_now      = date('Y-m-d H:i', mktime(9, 0, 0, date('m'), date('d'), date('Y'))) . ':00';
            $date_now_time = strtotime($date_now);
            $date_moins7   = date('Y-m-d H:i', mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time), date('d', $date_now_time) - 7, date('Y', $date_now_time))) . ':00';

            // Superieur à il y a 7 jours 9h00 du matin et infèrieur à la date du jour à 9h00 du matin
            $where = ' AND cgmn.date_notif > "' . $date_moins7 . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_hebdomadaire = 0 ' . $id_notif;
        } elseif ($champ == 'mensuelle') { // mensuelle (tous les 1er jours du mois à 9h00)
            $date_now         = date('Y-m-d H:i', mktime(9, 0, 0, date('m'), 1, date('Y'))) . ':00';
            $date_now_time    = strtotime($date_now);
            $date_moins_1mois = date('Y-m-d H:i', mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time) - 1, date('d', $date_now_time), date('Y', $date_now_time))) . ':00';

            // Supérieur au 1er du mois dernier et infèrieur au 1er du mois en cours
            $where = ' AND cgmn.date_notif > "' . $date_moins_1mois . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_mensuelle = 0 ' . $id_notif;
        }

        $sql = '
            SELECT cgmn.*
            FROM clients_gestion_mails_notif cgmn
            WHERE 1 = 1
                ' . $where . '
                AND cgmn.id_client = ' . $id_client . '
                AND cgmn.' . $champ . ' = 0';

        $result   = array();
        $resultat = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($resultat)) {
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
            $date_now      = date('Y-m-d H:i') . ':00';// 19:30:00
            $date_now_time = strtotime($date_now);
            $date_moins1   = date('Y-m-d H:i', mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time), date('d', $date_now_time) - 1, date('Y', $date_now_time))) . ':00';

            // Superieur à la date d'hier et infèrieur à la date du lancement de la requete
            $where = ' AND cgmn.date_notif > "' . $date_moins1 . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_quotidienne = 0 ' . $id_notif;
        } elseif ($champ == 'hebdomadaire') { // hebdomadaire (chaque samedi matin à 9h00)
            $date_now      = date('Y-m-d H:i', mktime(9, 0, 0, date('m'), date('d'), date('Y'))) . ':00';
            $date_now_time = strtotime($date_now);
            $date_moins7   = date('Y-m-d H:i', mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time), date('d', $date_now_time) - 7, date('Y', $date_now_time))) . ':00';

            // Superieur à il y a 7 jours 9h00 du matin et infèrieur à la date du jour à 9h00 du matin
            $where = ' AND cgmn.date_notif > "' . $date_moins7 . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_hebdomadaire = 0 ' . $id_notif;
        } elseif ($champ == 'mensuelle') { // mensuelle (tous les 1er jours du mois à 9h00)
            $date_now         = date('Y-m-d H:i', mktime(9, 0, 0, date('m'), 1, date('Y'))) . ':00';
            $date_now_time    = strtotime($date_now);
            $date_moins_1mois = date('Y-m-d H:i', mktime(date('H', $date_now_time), date('i', $date_now_time), 0, date('m', $date_now_time) - 1, date('d', $date_now_time), date('Y', $date_now_time))) . ':00';

            // Supérieur au 1er du mois dernier et infèrieur au 1er du mois en cours
            $where = ' AND cgmn.date_notif > "' . $date_moins_1mois . '" AND cgmn.date_notif <= "' . $date_now . '" AND status_check_mensuelle = 0 ' . $id_notif;
        }

        $sql = '
            SELECT cgmn.id_client
            FROM clients_gestion_mails_notif cgmn
            WHERE 1 = 1
                ' . $where . '
                AND cgmn.' . $champ . ' = 0
            GROUP BY cgmn.id_client '
            . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $result   = array();
        $resultat = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record['id_client'];
        }
        return $result;
    }
}
