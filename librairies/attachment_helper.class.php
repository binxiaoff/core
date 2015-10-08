<?php

class attachment_helper
{
    const PATH_LENDER = 'protected/lenders/';
    const PATH_COMPANY = 'protected/companies/';
    const PATH_PROJECT = 'protected/projects/';
    /**
     * @param integer    $ownerId
     * @param string     $ownerType
     * @param integer    $attachmentType
     * @param string     $field
     * @param string     $basePath
     * @param upload     $upload
     * @param attachment $attachment
     * @param string     $sNewName
     * @param array      $aFiles
     *
     * @return bool|string
     */
    public function upload(
        $ownerId,
        $ownerType,
        $attachmentType,
        $field,
        $basePath,
        $upload,
        $attachment,
        $sNewName = '',
        $aFiles = null
    ) {
        if (is_null($aFiles)) {
            $aFiles = $_FILES;
        }

        if (false === ($upload instanceof \upload)) {
            return false;
        }

        if (false === ($attachment instanceof \attachment)) {
            return false;
        }

        if (false === isset($aFiles[$field]) || $aFiles[$field]['name'] == '') {
            return null; // the field is empty, NOT an error
        }

        $uploadPath = self::getUploadPath($ownerType, $attachmentType);

        if ('' === $uploadPath) {
            return false;
        }

        $upload->setUploadDir($basePath, $uploadPath);

        if (false === $upload->doUpload($field, $sNewName, $erase = false, $aFiles)) {
            return false;
        }

        $attachmentInfo = $attachment->select(
            'id_owner = ' . $ownerId . '
            AND type_owner = "' . $ownerType . '"
            AND id_type = ' . $attachmentType
        );

        if (false === empty($attachmentInfo) && $attachmentInfo[0]['path'] != '') {
            @unlink($basePath . $uploadPath . $attachmentInfo[0]['path']);
        }

        $attachment->id_type    = $attachmentType;
        $attachment->id_owner   = $ownerId;
        $attachment->type_owner = $ownerType;
        $attachment->path       = $upload->getName();
        $attachment->archived   = null;

        $attachment_id = $attachment->save();

        if (false === is_numeric($attachment_id)) {
            return false;
        }

        return $attachment_id;
    }

    private static function getUploadPath($sOwnerType, $iDocumentType)
    {
        switch ($sOwnerType) {
            case attachment::LENDER:
                return self::getLendersDocumentPath($iDocumentType) . '/';
            case attachment::PROJECT:
                return self::getProjectsDocumentPath($iDocumentType) . '/';
            default:
                return null;
        }
    }

    private static function getLendersDocumentPath($iDocumentType)
    {
        $basePath = self::PATH_LENDER;

        switch ($iDocumentType) {
            case attachment_type::CNI_PASSPORTE:
                return $basePath . 'cni_passeport';
            case attachment_type::CNI_PASSPORTE_VERSO:
                return $basePath . 'cni_passeport_verso';
            case attachment_type::JUSTIFICATIF_DOMICILE:
                return $basePath . 'justificatif_domicile';
            case attachment_type::RIB:
                return $basePath . 'rib';
            case attachment_type::ATTESTATION_HEBERGEMENT_TIERS:
                return $basePath . 'attestation_hebergement_tiers';
            case attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT:
                return $basePath . 'cni_passport_tiers_hebergeant';
            case attachment_type::CNI_PASSPORTE_DIRIGEANT:
                return $basePath . 'cni_passeport_dirigent';
            case attachment_type::DELEGATION_POUVOIR:
                return $basePath . 'delegation_pouvoir';
            case attachment_type::KBIS:
                return $basePath . 'extrait_kbis';
            case attachment_type::JUSTIFICATIF_FISCAL:
                return $basePath . 'document_fiscal';
            case attachment_type::AUTRE1:
                return $basePath . 'autre';
            case attachment_type::AUTRE2:
                return $basePath . 'autre2';
            case attachment_type::AUTRE3:
                return $basePath . 'autre3';
            case attachment_type::DISPENSE_PRELEVEMENT_2014:
                return $basePath . 'dispense_prelevement_2014';
            case attachment_type::DISPENSE_PRELEVEMENT_2015:
                return $basePath . 'dispense_prelevement_2015';
            case attachment_type::DISPENSE_PRELEVEMENT_2016:
                return $basePath . 'dispense_prelevement_2016';
            case attachment_type::DISPENSE_PRELEVEMENT_2017:
                return $basePath . 'dispense_prelevement_2017';
            default:
                return '';
        }
    }

    private function getProjectsDocumentPath($iDocumentType)
    {
        $basePath = self::PATH_PROJECT;

        switch ($iDocumentType) {
            case attachment_type::RELEVE_BANCAIRE_MOIS_N:
                return $basePath . 'releve_bancaire_mois_n/';
            case attachment_type::RELEVE_BANCAIRE_MOIS_N_1:
                return $basePath . 'releve_bancaire_mois_n_1/';
            case attachment_type::RELEVE_BANCAIRE_MOIS_N_2:
                return $basePath . 'releve_bancaire_mois_n_2/';
            case attachment_type::PRESENTATION_ENTRERPISE:
                return $basePath . 'presentation_entreprise/';
            case attachment_type::ETAT_ENDETTEMENT:
                return $basePath . 'etat_endettement/';
            case attachment_type::DERNIERE_LIASSE_FISCAL:
                return $basePath . 'liasse_fiscal/';
            case attachment_type::LIASSE_FISCAL_N_1:
                return $basePath . 'liasse_fiscal_n_1/';
            case attachment_type::LIASSE_FISCAL_N_2:
                return $basePath . 'liasse_fiscal_n_2/';
            case attachment_type::RAPPORT_CAC:
                return $basePath . 'rapport_cac/';
            case attachment_type::PREVISIONNEL:
                return $basePath . 'previsionnel/';
            case attachment_type::CNI_PASSPORTE_DIRIGEANT:
                return $basePath . 'cni_passeport_dirigeant/';
            case attachment_type::CNI_PASSPORTE_VERSO:
                return $basePath . 'cni_passeport_dirigeant_verso/';
            case attachment_type::RIB:
                return $basePath . 'rib/';
            case attachment_type::KBIS:
                return $basePath . 'extrait_kbis/';
            case attachment_type::AUTRE1:
                return $basePath . 'autre/';
            case attachment_type::AUTRE2:
                return $basePath . 'autre2/';
            case attachment_type::AUTRE3:
                return $basePath . 'autre3/';
            case attachment_type::BALANCE_CLIENT:
                return $basePath . 'balance_client/';
            case attachment_type::BALANCE_FOURNISSEUR:
                return $basePath . 'balance_fournisseur/';
            case attachment_type::ETAT_PRIVILEGES_NANTISSEMENTS:
                return $basePath . 'etat_privileges_nantissements/';
            default:
                return '';
        }
    }
}
