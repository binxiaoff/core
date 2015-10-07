<?php

class attachment_helper
{
    /**
     * @param integer $ownerId
     * @param string $ownerType
     * @param integer $attachmentType
     * @param string $field
     * @param string $path
     * @param string $uploadPath
     * @param upload $upload
     * @param attachment $attachment
     * @param string $sNewName
     * @param array $aFiles
     * @return bool|string
     */
    public function upload($ownerId, $ownerType, $attachmentType, $field, $path, $uploadPath, $upload, $attachment, $sNewName = '', $aFiles = null)
    {
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

        $upload->setUploadDir($path, $uploadPath);

        if (false === $upload->doUpload($field, $sNewName, $erase = false, $aFiles)) {
            return false;
        }

        $attachmentInfo = $attachment->select('
            id_owner = ' . $ownerId . '
            AND type_owner = "' . $ownerType . '"
            AND id_type = ' . $attachmentType
        );

        if (false === empty($attachmentInfo) && $attachmentInfo[0]['path'] != '') {
            @unlink($path . $uploadPath . $attachmentInfo[0]['path']);
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

    private function getUploadPath($sOwnerType, $iDocumentType)
    {
        switch ($sOwnerType) {
            case attachment::LENDER:
                return 'protected/lenders/' . $this->getLendersDocumentPath($iDocumentType) . '/';
            case attachment::COMPANY:
                return 'protected/companies/' . $this->getCompaniesDocumentPath($iDocumentType) . '/';
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

    private function getCompaniesDocumentPath($iDocumentType)
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
}
