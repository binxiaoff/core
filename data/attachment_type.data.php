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
    const CNI_PASSPORTE                        = 1;
    const JUSTIFICATIF_DOMICILE                = 2;
    const RIB                                  = 3;
    const ATTESTATION_HEBERGEMENT_TIERS        = 4;
    const CNI_PASSPORT_TIERS_HEBERGEANT        = 5;
    const JUSTIFICATIF_FISCAL                  = 6;
    const CNI_PASSPORTE_DIRIGEANT              = 7;
    const KBIS                                 = 8;
    const DELEGATION_POUVOIR                   = 9;
    const STATUTS                              = 10;
    const CNI_PASSPORTE_VERSO                  = 11;
    const DERNIERE_LIASSE_FISCAL               = 15;
    const AUTRE1                               = 22;
    const AUTRE2                               = 23;
    const AUTRE3                               = 24;
    const DISPENSE_PRELEVEMENT_2014            = 25;
    const DISPENSE_PRELEVEMENT_2015            = 26;
    const DISPENSE_PRELEVEMENT_2016            = 27;
    const DISPENSE_PRELEVEMENT_2017            = 28;
    const DELEGATION_POUVOIR_PERSONNES_MORALES = 29;
    const RELEVE_BANCAIRE_MOIS_N               = 30;
    const RELEVE_BANCAIRE_MOIS_N_1             = 31;
    const RELEVE_BANCAIRE_MOIS_N_2             = 32;
    const PRESENTATION_ENTRERPISE              = 33;
    const ETAT_ENDETTEMENT                     = 34;
    const LIASSE_FISCAL_N_1                    = 35;
    const LIASSE_FISCAL_N_2                    = 36;
    const RAPPORT_CAC                          = 37;
    const PREVISIONNEL                         = 38;
    const BALANCE_CLIENT                       = 39;
    const BALANCE_FOURNISSEUR                  = 40;
    const ETAT_PRIVILEGES_NANTISSEMENTS        = 41;
    const AUTRE4                               = 42;
    const CGV                                  = 43;
    const CNI_BENEFICIAIRE_EFFECTIF_1          = 44;
    const CNI_BENEFICIAIRE_EFFECTIF_VERSO_1    = 45;
    const CNI_BENEFICIAIRE_EFFECTIF_2          = 46;
    const CNI_BENEFICIAIRE_EFFECTIF_VERSO_2    = 47;
    const CNI_BENEFICIAIRE_EFFECTIF_3          = 48;
    const CNI_BENEFICIAIRE_EFFECTIF_VERSO_3    = 49;
    const SITUATION_COMPTABLE_INTERMEDIAIRE    = 50;
    const DERNIERS_COMPTES_CONSOLIDES          = 51;

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

    public function getAllTypesForProjects($sLanguage, $bIncludeOthers = true)
    {
        $aTypes = array(
            self::RELEVE_BANCAIRE_MOIS_N,
            self::RELEVE_BANCAIRE_MOIS_N_1,
            self::RELEVE_BANCAIRE_MOIS_N_2,
            self::KBIS,
            self::PRESENTATION_ENTRERPISE,
            self::ETAT_ENDETTEMENT,
            self::RIB,
            self::CNI_PASSPORTE_DIRIGEANT,
            self::CNI_PASSPORTE_VERSO,
            self::DERNIERE_LIASSE_FISCAL,
            self::LIASSE_FISCAL_N_1,
            self::LIASSE_FISCAL_N_2,
            self::RAPPORT_CAC,
            self::PREVISIONNEL,
            self::BALANCE_CLIENT,
            self::BALANCE_FOURNISSEUR,
            self::ETAT_PRIVILEGES_NANTISSEMENTS,
            self::CGV,
            self::CNI_BENEFICIAIRE_EFFECTIF_1,
            self::CNI_BENEFICIAIRE_EFFECTIF_VERSO_1,
            self::CNI_BENEFICIAIRE_EFFECTIF_2,
            self::CNI_BENEFICIAIRE_EFFECTIF_VERSO_2,
            self::CNI_BENEFICIAIRE_EFFECTIF_3,
            self::CNI_BENEFICIAIRE_EFFECTIF_VERSO_3,
            self::SITUATION_COMPTABLE_INTERMEDIAIRE,
            self::DERNIERS_COMPTES_CONSOLIDES,
            self::AUTRE1,
            self::AUTRE2,
            self::AUTRE3,
            self::AUTRE4
        );

        $oTextes = new \textes($this->bdd);
        $aTranslations = $oTextes->selectFront('projet', $sLanguage);

        $aTypes = array_map(
            function($aType) use ($aTranslations) {
                $aType['label'] = $aTranslations['document-type-' . $aType['id']];
                return $aType;
            },
            $this->getAllTypes($aTypes)
        );

        if (false === $bIncludeOthers) {
            $aTypes = array_slice($aTypes, 0, count($aTypes) - 4);
        }

        return $aTypes;
    }

    public function getAllTypesForLender()
    {
        $aTypes = array(
            self::CNI_PASSPORTE,
            self::CNI_PASSPORTE_VERSO,
            self::JUSTIFICATIF_DOMICILE,
            self::RIB,
            self::ATTESTATION_HEBERGEMENT_TIERS,
            self::CNI_PASSPORT_TIERS_HEBERGEANT,
            self::CNI_PASSPORTE_DIRIGEANT,
            self::DELEGATION_POUVOIR,
            self::KBIS,
            self::JUSTIFICATIF_FISCAL,
            self::DISPENSE_PRELEVEMENT_2014,
            self::DISPENSE_PRELEVEMENT_2015,
            self::DISPENSE_PRELEVEMENT_2016,
            self::DISPENSE_PRELEVEMENT_2017,
            self::AUTRE1,
            self::AUTRE2,
            self::AUTRE3
        );

        return $this->getAllTypes($aTypes);
    }

    private function getAllTypes($aTypes)
    {
        $result   = array();
        $resultat = $this->bdd->query('
            SELECT *
            FROM attachment_type
            WHERE id IN(' . implode(', ', $aTypes) . ')
            ORDER BY FIELD(id, ' . implode(', ', $aTypes) . ')');

        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
    }
}
