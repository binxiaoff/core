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

class partenaires extends partenaires_crud
{
    public function partenaires($bdd, $params = '')
    {
        parent::partenaires($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM partenaires' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = [];
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

        $sql = 'SELECT COUNT(*) FROM partenaires ' . $where;

        $result = $this->bdd->query($sql);
        return (int) $this->bdd->result($result);
    }

    public function exist($id, $field = 'id_partenaire')
    {
        $sql    = 'SELECT * FROM partenaires WHERE ' . $field . ' = "' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_assoc($result) > 0);
    }

    /**
     * Récupération du CA d'un partenaire
     * @param int $id_partenaire
     * @return mixed
     */
    public function recupCA($id_partenaire)
    {
        $sql = '
            SELECT ROUND(SUM(montant) / 100, 2) 
            FROM transactions 
            WHERE id_partenaire = ' . $id_partenaire . ' AND status != ' . \transactions::STATUS_CANCELED;

        $result = $this->bdd->query($sql);
        return $this->bdd->result($result);
    }

    /**
     * Récupération du nombre de commandes d'un partenaire
     * @param int $id_partenaire
     * @return mixed
     */
    public function recupCmde($id_partenaire)
    {
        $sql = '
            SELECT COUNT(id_transaction) 
            FROM transactions 
            WHERE id_partenaire = ' . $id_partenaire . ' AND status != ' . \transactions::STATUS_CANCELED;

        $result = $this->bdd->query($sql);
        return $this->bdd->result($result);
    }

    /**
     * Récupération du nombre de clic global
     * @param int $id_partenaire
     * @return int
     */
    public function nbClicTotal($id_partenaire)
    {
        $sql = '
            SELECT SUM(nb_clics) 
            FROM partenaires_clics
            WHERE id_partenaire = ' . $id_partenaire;

        $result = $this->bdd->query($sql);
        return (int) $this->bdd->result($result);
    }
}
