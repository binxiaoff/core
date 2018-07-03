<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;

class clients_gestion_notifications extends clients_gestion_notifications_crud
{

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

    /**
     * @param string      $frequency
     * @param int         $notificationType
     * @param string|null $fieldName
     *
     * @return string
     */
    private function getCustomerNotificationQuery(string $frequency, int $notificationType, ?string $fieldName = null): string
    {
        if ($frequency === 'quotidienne') {
            $where = '
                cgmn.date_notif > "' . date('Y-m-d H:i:s', mktime(20, 0, 0, date('m'), date('d') - 1, date('Y'))) . '"
                AND cgmn.date_notif <= "' . date('Y-m-d H:i:s', mktime(20, 0, 0, date('m'), date('d'), date('Y'))) . '"';
        } elseif ($frequency === 'hebdomadaire') {
            $where = '
                cgmn.date_notif > "' . date('Y-m-d H:i:s', mktime(9, 0, 0, date('m'), date('d') - 7, date('Y'))) . '"
                AND cgmn.date_notif <= "' . date('Y-m-d H:i:s', mktime(9, 0, 0, date('m'), date('d'), date('Y'))) . '"';
        } elseif ($frequency === 'mensuelle') {
            $where = '
                cgmn.date_notif > "' . date('Y-m-d H:i:s', mktime(9, 0, 0, date('m') - 1, 1, date('Y'))) . '"
                AND cgmn.date_notif <= "' . date('Y-m-d H:i:s', mktime(9, 0, 0, date('m'), 1, date('Y'))) . '"';
        }

        return '
            SELECT cgmn.' . (is_null($fieldName) ? '*' : $fieldName) . '
            FROM clients_gestion_mails_notif cgmn
            INNER JOIN clients_gestion_notifications cgn ON (cgn.id_client = cgmn.id_client AND cgn.id_notif = cgmn.id_notif)
            INNER JOIN clients c ON c.id_client = cgn.id_client
            INNER JOIN clients_status_history csh ON c.id_client_status_history = csh.id
            WHERE ' . $where . '
                AND csh.id_status IN (' . implode(',', ClientsStatus::GRANTED_LOGIN) . ')
                AND cgn.' . $frequency . ' = 1
                AND (cgmn.' . $frequency . ' = 0 OR cgmn.' . $frequency . ' IS NULL)
                AND (cgmn.status_check_' . $frequency . ' = 0 OR cgmn.status_check_' . $frequency . ' IS NULL)
                AND cgmn.id_notif = ' . $notificationType;
    }
}
