<?php

class attachment_helper
{
    const PATH_LENDER = 'protected/lenders/';
    const PATH_PROJECT = 'protected/projects/';

    /** @var  attachment */
    private $oAttachment;

    /** @var  attachment_type */
    private $oAttachmentType;

    public function __construct($aAttributes)
    {
        $this->oAttachment     = $aAttributes[0];
        $this->oAttachmentType = $aAttributes[1];
    }
    /**
     * @param integer    $ownerId
     * @param string     $ownerType
     * @param integer    $attachmentType
     * @param string     $field
     * @param string     $basePath
     * @param upload     $upload
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
        $sNewName = '',
        $aFiles = null
    ) {
        if (is_null($aFiles)) {
            $aFiles = $_FILES;
        }

        if (false === ($upload instanceof \upload)) {
            return false;
        }

        if (! $this->oAttachment instanceof attachment || ! $this->oAttachmentType instanceof attachment_type) {
            return false;
        }

        if (false === isset($aFiles[$field]) || $aFiles[$field]['name'] == '') {
            return null; // the field is empty, NOT an error
        }

        $uploadPath = $this->getUploadPath($ownerType, $attachmentType);

        if ('' === $uploadPath) {
            return false;
        }

        $upload->setUploadDir($basePath, $uploadPath);

        if (false === $upload->doUpload($field, $sNewName, $erase = false, $aFiles)) {
            return false;
        }

        $attachmentInfo = $this->oAttachment->select(
            'id_owner = ' . $ownerId . '
            AND type_owner = "' . $ownerType . '"
            AND id_type = ' . $attachmentType
        );

        if (false === empty($attachmentInfo) && $attachmentInfo[0]['path'] != '') {
            @unlink($basePath . $uploadPath . $attachmentInfo[0]['path']);
        }

        $this->oAttachment->id_type    = $attachmentType;
        $this->oAttachment->id_owner   = $ownerId;
        $this->oAttachment->type_owner = $ownerType;
        $this->oAttachment->path       = $upload->getName();
        $this->oAttachment->archived   = null;

        $attachment_id = $this->oAttachment->save();

        if (false === is_numeric($attachment_id)) {
            return false;
        }

        return $attachment_id;
    }

    public function getUploadPath($sOwnerType, $iDocumentType)
    {
        switch ($sOwnerType) {
            case attachment::LENDER:
                return self::PATH_LENDER . $this->getLendersDocumentPath($iDocumentType) . '/';
            case attachment::PROJECT:
                return self::PATH_PROJECT . $this->getProjectsDocumentPath($iDocumentType) . '/';
            default:
                return null;
        }
    }

    private function getLendersDocumentPath($iDocumentType)
    {
        switch ($iDocumentType) {
            case attachment_type::CNI_PASSPORTE:
                return 'cni_passeport';
            case attachment_type::CNI_PASSPORTE_VERSO:
                return 'cni_passeport_verso';
            case attachment_type::JUSTIFICATIF_DOMICILE:
                return 'justificatif_domicile';
            case attachment_type::RIB:
                return 'rib';
            case attachment_type::ATTESTATION_HEBERGEMENT_TIERS:
                return 'attestation_hebergement_tiers';
            case attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT:
                return 'cni_passport_tiers_hebergeant';
            case attachment_type::CNI_PASSPORTE_DIRIGEANT:
                return 'cni_passeport_dirigent';
            case attachment_type::DELEGATION_POUVOIR:
                return 'delegation_pouvoir';
            case attachment_type::KBIS:
                return 'extrait_kbis';
            case attachment_type::JUSTIFICATIF_FISCAL:
                return 'document_fiscal';
            case attachment_type::AUTRE1:
                return 'autre';
            case attachment_type::AUTRE2:
                return 'autre2';
            case attachment_type::AUTRE3:
                return 'autre3';
            case attachment_type::DISPENSE_PRELEVEMENT_2014:
                return 'dispense_prelevement_2014';
            case attachment_type::DISPENSE_PRELEVEMENT_2015:
                return 'dispense_prelevement_2015';
            case attachment_type::DISPENSE_PRELEVEMENT_2016:
                return 'dispense_prelevement_2016';
            case attachment_type::DISPENSE_PRELEVEMENT_2017:
                return 'dispense_prelevement_2017';
            default:
                return '';
        }
    }

    private function getProjectsDocumentPath($iDocumentType)
    {
        switch ($iDocumentType) {
            case attachment_type::RELEVE_BANCAIRE_MOIS_N:
                return 'releve_bancaire_mois_n/';
            case attachment_type::RELEVE_BANCAIRE_MOIS_N_1:
                return 'releve_bancaire_mois_n_1/';
            case attachment_type::RELEVE_BANCAIRE_MOIS_N_2:
                return 'releve_bancaire_mois_n_2/';
            case attachment_type::PRESENTATION_ENTRERPISE:
                return 'presentation_entreprise/';
            case attachment_type::ETAT_ENDETTEMENT:
                return 'etat_endettement/';
            case attachment_type::DERNIERE_LIASSE_FISCAL:
                return 'liasse_fiscal/';
            case attachment_type::LIASSE_FISCAL_N_1:
                return 'liasse_fiscal_n_1/';
            case attachment_type::LIASSE_FISCAL_N_2:
                return 'liasse_fiscal_n_2/';
            case attachment_type::RAPPORT_CAC:
                return 'rapport_cac/';
            case attachment_type::PREVISIONNEL:
                return 'previsionnel/';
            case attachment_type::CNI_PASSPORTE_DIRIGEANT:
                return 'cni_passeport_dirigeant/';
            case attachment_type::CNI_PASSPORTE_VERSO:
                return 'cni_passeport_dirigeant_verso/';
            case attachment_type::RIB:
                return 'rib/';
            case attachment_type::KBIS:
                return 'extrait_kbis/';
            case attachment_type::AUTRE1:
                return 'autre/';
            case attachment_type::AUTRE2:
                return 'autre2/';
            case attachment_type::AUTRE3:
                return 'autre3/';
            case attachment_type::BALANCE_CLIENT:
                return 'balance_client/';
            case attachment_type::BALANCE_FOURNISSEUR:
                return 'balance_fournisseur/';
            case attachment_type::ETAT_PRIVILEGES_NANTISSEMENTS:
                return 'etat_privileges_nantissements/';
            case attachment_type::COMPANY_LOGO:
                return 'logo_company/';
            default:
                return '';
        }
    }
}
