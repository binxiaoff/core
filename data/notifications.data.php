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

class notifications extends notifications_crud
{
    const STATUS_READ   = 1;
    const STATUS_UNREAD = 0;

    public function __construct($bdd, $params = '')
    {
        parent::notifications($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `notifications`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $result = $this->bdd->query('SELECT COUNT(*) FROM `notifications` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_notification')
    {
        $sql    = 'SELECT * FROM `notifications` WHERE ' . $field . ' = "' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_assoc($result) > 0);
    }

    /**
     * @param lenders_accounts $lender
     */
    public function markAllLenderNotificationsAsRead(\lenders_accounts $lender)
    {
        $queryBuilder = $this->bdd->createQueryBuilder();
        $queryBuilder->update('notifications')
            ->set('status', self::STATUS_READ)
            ->where('status = ' . self::STATUS_UNREAD)
            ->andWhere('id_lender = :id_lender')
            ->setParameter('id_lender', $lender->id_lender_account);

        $queryBuilder->execute();
    }

    /**
     * @param lenders_accounts $lender
     * @param array            $notifications
     */
    public function markLenderNotificationsAsRead(\lenders_accounts $lender, array $notifications)
    {
        $queryBuilder = $this->bdd->createQueryBuilder();
        $queryBuilder->update('notifications')
            ->set('status', self::STATUS_READ)
            ->where('status = ' . self::STATUS_UNREAD)
            ->andWhere('id_lender = :id_lender')
            ->andWhere('id_notification IN (:notifications)')
            ->setParameter('id_lender', $lender->id_lender_account, \PDO::PARAM_INT)
            ->setParameter('notifications', $notifications, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        $queryBuilder->execute();
    }
}
