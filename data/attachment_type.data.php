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

class attachment_type extends attachment_type_crud
{
    const CNI_PASSPORTE                 = 1;
    const JUSTIFICATIF_DOMICILE         = 2;
    const RIB                           = 3;
    const ATTESTATION_HEBERGEMENT_TIERS = 4;
    const CNI_PASSPORT_TIERS_HEBERGEANT = 5;
    const JUSTIFICATIF_FISCAL           = 6;
    const CNI_PASSPORTE_DIRIGEANT       = 7;
    const KBIS                          = 8;
    const DELEGATION_POUVOIR            = 9;
    const STATUTS                       = 10;
    const CNI_PASSPORTE_VERSO           = 11;
    const AUTRE1                        = 22;
    const AUTRE2                        = 23;
    const AUTRE3                        = 24;
    const DISPENSE_PRELEVEMENT_2014     = 25;
    const DISPENSE_PRELEVEMENT_2015     = 26;
    const DISPENSE_PRELEVEMENT_2016     = 27;
    const DISPENSE_PRELEVEMENT_2017     = 28;
    const DERNIERE_LIASSE_FISCAL        = 15;
    const RELEVE_BANCAIRE_MOIS_N        = 30;
    const RELEVE_BANCAIRE_MOIS_N_1      = 31;
    const RELEVE_BANCAIRE_MOIS_N_2      = 32;
    const PRESENTATION_ENTRERPISE       = 33;
    const ETAT_ENDETTEMENT              = 34;
    const LIASSE_FISCAL_N_1             = 35;
    const LIASSE_FISCAL_N_2             = 36;
    const RAPPORT_CAC                   = 37;
    const PREVISIONNEL                  = 38;
    const BALANCE_CLIENT                = 39;
    const BALANCE_FOURNISSEUR           = 40;
    const ETAT_PRIVILEGES_NANTISSEMENTS = 41;

    public function __construct($bdd, $params = '')
    {
        parent::attachment_type($bdd, $params);
    }

    public function get($id, $field = 'id')
    {
        return parent::get($id, $field);
    }

    public function delete($id, $field = 'id')
    {
        parent::delete($id, $field);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `attachment_type`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $result = $this->bdd->query('SELECT count(*) FROM `attachment_type` ' . $where);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id')
    {
        $result = $this->bdd->query('SELECT * FROM `attachment_type` WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    public function getAllTypesForProjects()
    {
        $aTypes = array(
            self::RELEVE_BANCAIRE_MOIS_N,
            self::RELEVE_BANCAIRE_MOIS_N_1,
            self::RELEVE_BANCAIRE_MOIS_N_2,
            self::KBIS,
            self::PRESENTATION_ENTRERPISE,
            self::ETAT_ENDETTEMENT,
            self::RIB,
            self::DERNIERE_LIASSE_FISCAL,
            self::LIASSE_FISCAL_N_1,
            self::LIASSE_FISCAL_N_2,
            self::RAPPORT_CAC,
            self::PREVISIONNEL,
            self::BALANCE_CLIENT,
            self::BALANCE_FOURNISSEUR,
            self::ETAT_PRIVILEGES_NANTISSEMENTS,
            self::AUTRE1,
            self::AUTRE2,
            self::AUTRE3,
            self::CNI_PASSPORTE_DIRIGEANT,
            self::CNI_PASSPORTE_VERSO
        );

        $resultat = $this->bdd->query('SELECT * FROM `attachment_type` WHERE `id` IN (' . $aTypes . ')');
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }
}
