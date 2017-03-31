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
    const TYPE_NOTIFICATION_IMMEDIATE = 'immediatement';
    const TYPE_NOTIFICATION_DAILY     = 'quotidienne';
    const TYPE_NOTIFICATION_WEEKLY    = 'hebdomadaire';
    const TYPE_NOTIFICATION_MONTHLY   = 'mensuelle';
    const TYPE_NOTIFICATION_NO_MAIL   = 'uniquement_notif';

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
        return ($this->bdd->fetch_array($result) > 0);
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
        if (false === empty($id_client)
            && false === empty($id_notif)
            && false === empty($champ)
            && $this->counter('id_client = ' . $id_client . ' AND id_notif = ' . $id_notif . ' AND ' . $champ . ' = 1') > 0) {
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
            AND cgn.id_client IN (' . implode(',', $aCustomerId) . ')
            ORDER BY cgmn.id_clients_gestion_mails_notif'
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
                cgmn.date_notif > "' . date('Y-m-d H:i:s', mktime(20, 0, 0, date('m'), date('d') - 1, date('Y'))) . '"
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
            INNER JOIN clients c ON c.id_client = cgn.id_client
            WHERE ' . $where . '
                AND c.status = 1
                AND cgn.' . $sFrequency . ' = 1
                AND cgmn.' . $sFrequency . ' = 0
                AND cgmn.status_check_' . $sFrequency . ' = 0
                AND cgmn.id_notif = ' . $iNotificationType;
    }

    public static function getAllPeriod()
    {
        return array(
            self::TYPE_NOTIFICATION_IMMEDIATE,
            self::TYPE_NOTIFICATION_DAILY,
            self::TYPE_NOTIFICATION_WEEKLY,
            self::TYPE_NOTIFICATION_MONTHLY,
            self::TYPE_NOTIFICATION_NO_MAIL,
        );
    }
}
