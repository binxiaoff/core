<?php

class attachment_helper
{
    const PATH_LENDER = 'protected/lenders/';
    const PATH_PROJECT = 'protected/projects/';

    /** @var  attachment */
    private $oAttachment;

    /** @var  attachment_type */
    private $oAttachmentType;

    /** @var  string */
    private $basePath;

    public function __construct($aAttributes)
    {
        $this->oAttachment      = $aAttributes[0];
        $this->oAttachmentType  = $aAttributes[1];
        $this->basePath         = $aAttributes[2];
    }
    /**
     * @param integer    $ownerId
     * @param string     $ownerType
     * @param integer    $attachmentType
     * @param string     $field
     * @param upload     $upload
     * @param string     $sNewName
     * @return bool|int
     */
    public function upload($ownerId, $ownerType, $attachmentType, $field, $upload, $sNewName = '')
    {
        if (false === ($upload instanceof \upload)) {
            return false;
        }

        if (! $this->oAttachment instanceof attachment || ! $this->oAttachmentType instanceof attachment_type) {
            return false;
        }

        if (false === isset($_FILES[$field]) || $_FILES[$field]['name'] == '') {
            return null; // the field is empty, NOT an error
        }

        $uploadPath = $this->getUploadPath($ownerType, $attachmentType);

        if ('' === $uploadPath) {
            return false;
        }

        $upload->setUploadDir($this->basePath, $uploadPath);

        if (false === $upload->doUpload($field, $sNewName, false)) {
            return false;
        }

        $attachmentInfo = $this->oAttachment->select(
            'id_owner = ' . $ownerId . '
            AND type_owner = "' . $ownerType . '"
            AND id_type = ' . $attachmentType
        );

        if (false === empty($attachmentInfo) && $attachmentInfo[0]['path'] != '') {
            @unlink($this->basePath . $uploadPath . $attachmentInfo[0]['path']);
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

    public function remove($iAttachmentId)
    {
        if (false === $this->oAttachment->get($iAttachmentId)) {
            return false;
        }

        $this->oAttachment->delete($iAttachmentId);

        $uploadPath = $this->getUploadPath($this->oAttachment->type_owner, $this->oAttachment->id_type);

        if ('' === $uploadPath) {
            return false;
        }

        @unlink($this->basePath . $uploadPath . $this->oAttachment->path);

        return true;
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

    public function getFullPath($sOwnerType, $iDocumentType)
    {
        $path = $this->getUploadPath($sOwnerType, $iDocumentType);

        if (null === $path) {
            return null;
        }

        return $this->basePath . $path;
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
                return 'releve_bancaire_mois_n';
            case attachment_type::RELEVE_BANCAIRE_MOIS_N_1:
                return 'releve_bancaire_mois_n_1';
            case attachment_type::RELEVE_BANCAIRE_MOIS_N_2:
                return 'releve_bancaire_mois_n_2';
            case attachment_type::PRESENTATION_ENTRERPISE:
                return 'presentation_entreprise';
            case attachment_type::ETAT_ENDETTEMENT:
                return 'etat_endettement';
            case attachment_type::DERNIERE_LIASSE_FISCAL:
                return 'liasse_fiscale';
            case attachment_type::LIASSE_FISCAL_N_1:
                return 'liasse_fiscale_n_1';
            case attachment_type::LIASSE_FISCAL_N_2:
                return 'liasse_fiscale_n_2';
            case attachment_type::RAPPORT_CAC:
                return 'annexes_rapport_special_commissaire_compte';
            case attachment_type::PREVISIONNEL:
                return 'previsionnel';
            case attachment_type::CNI_PASSPORTE_DIRIGEANT:
                return 'cni_passeport';
            case attachment_type::CNI_PASSPORTE_VERSO:
                return 'cni_passeport_verso';
            case attachment_type::RIB:
                return 'rib';
            case attachment_type::KBIS:
                return 'extrait_kbis';
            case attachment_type::AUTRE1:
                return 'autre';
            case attachment_type::AUTRE2:
                return 'autre2';
            case attachment_type::AUTRE3:
                return 'autre3';
            case attachment_type::AUTRE4:
                return 'autre4';
            case attachment_type::BALANCE_CLIENT:
                return 'balance_client';
            case attachment_type::BALANCE_FOURNISSEUR:
                return 'balance_fournisseur';
            case attachment_type::ETAT_PRIVILEGES_NANTISSEMENTS:
                return 'etat_privileges_nantissements';
            case attachment_type::CGV:
                return 'cgv';
            case attachment_type::CNI_BENEFICIAIRE_EFFECTIF_1:
                return 'cni_beneficiaire_efectif_1';
            case attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_1:
                return 'cni_beneficiaire_efectif_verso_1';
            case attachment_type::CNI_BENEFICIAIRE_EFFECTIF_2:
                return 'cni_beneficiaire_efectif_2';
            case attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_2:
                return 'cni_beneficiaire_efectif_verso_2';
            case attachment_type::CNI_BENEFICIAIRE_EFFECTIF_3:
                return 'cni_beneficiaire_efectif_3';
            case attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_3:
                return 'cni_beneficiaire_efectif_verso_3';
            case attachment_type::SITUATION_COMPTABLE_INTERMEDIAIRE:
                return 'situation_comptable_intermediaire';
            case attachment_type::DERNIERS_COMPTES_CONSOLIDES:
                return 'derniers_comptes_consolides_groupe';
            default:
                return '';
        }
    }

    /**
     * @param \attachment $oAttachment
     * @param int $ownerId
     * @param int $ownerType
     * @param int $attachmentType
     * @return int|bool
     */
    public function attachmentExists($oAttachment, $ownerId, $ownerType, $attachmentType)
    {
        $attachmentInfo = $oAttachment->select(
            'id_owner = ' . $ownerId . '
            AND type_owner = "' . $ownerType . '"
            AND id_type = ' . $attachmentType
        );
        if (false === empty($attachmentInfo) && $attachmentInfo[0]['path'] != '') {
            return $attachmentInfo[0]['id'];
        } else {
            return false;
        }
    }
}
