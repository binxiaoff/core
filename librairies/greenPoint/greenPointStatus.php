<?php

namespace Unilend\librairies\greenPoint;

use Unilend\data;

/**
 * Class greenPointStatus
 * @package Unilend\librairies\greenPoint
 */
class greenPointStatus
{
    CONST NOT_VERIFIED                   = 0;
    CONST OUT_OF_BOUNDS                  = 1;
    CONST FALSIFIED_OR_MINOR             = 2;
    CONST ILLEGIBLE                      = 3;
    CONST VERSO_MISSING                  = 4;
    CONST NAME_SURNAME_INVERSION         = 5;
    CONST INCOHERENT_OTHER_ERROR         = 6;
    CONST EXPIRED                        = 7;
    CONST CONFORM_COHERENT_NOT_QUALIFIED = 8;
    CONST CONFORM_COHERENT_QUALIFIED     = 9;


    public static $aIdControlStatusLabel = array(
        self::NOT_VERIFIED                   => 'Non vérifié',
        self::OUT_OF_BOUNDS                  => 'Hors périmètre (pas un document d\'identité)',
        self::FALSIFIED_OR_MINOR             => 'Falsifiée ou mineur',
        self::ILLEGIBLE                      => 'Illisible / coupée',
        self::VERSO_MISSING                  => 'Verso seul : recto manquant',
        self::NAME_SURNAME_INVERSION         => 'Non cohérent / données : inversion nom - prénom',
        self::INCOHERENT_OTHER_ERROR         => 'Non cohérent / données : autre erreur',
        self::EXPIRED                        => 'Expiré',
        self::CONFORM_COHERENT_NOT_QUALIFIED => 'Conforme, cohérent et valide mais non labellisable',
        self::CONFORM_COHERENT_QUALIFIED     => 'Conforme, cohérent et valide + label GREENPOINT IDCONTROL'
    );
    public static $aIbanFlashStatusLabel = array(
        self::NOT_VERIFIED                   => 'Non vérifié',
        self::OUT_OF_BOUNDS                  => 'Hors périmètre (pas un RIB)',
        self::FALSIFIED_OR_MINOR             => 'Falsifié',
        self::ILLEGIBLE                      => 'Illisible / coupé',
        self::VERSO_MISSING                  => 'Banque hors périmètre',
        self::NAME_SURNAME_INVERSION         => 'Non cohérent / données : inversion nom - prénom',
        self::INCOHERENT_OTHER_ERROR         => 'Non cohérent / données : autre erreur',
        self::EXPIRED                        => '-',
        self::CONFORM_COHERENT_NOT_QUALIFIED => 'Vérifié sauf prénom du titulaire non vérifié',
        self::CONFORM_COHERENT_QUALIFIED     => 'Conforme, cohérent et valide'
    );
    public static $aAddressControlStatusLabel = array(
        self::NOT_VERIFIED                   => 'Non vérifié',
        self::OUT_OF_BOUNDS                  => 'Hors périmètre (pas un justificatif de domicile)',
        self::FALSIFIED_OR_MINOR             => 'Falsifié',
        self::ILLEGIBLE                      => 'Illisible / coupé',
        self::VERSO_MISSING                  => 'Fournisseur hors périmètre',
        self::NAME_SURNAME_INVERSION         => 'Non cohérent / données : erreur sur le titulaire',
        self::INCOHERENT_OTHER_ERROR         => 'Non cohérent / données : erreur sur l\'adresse',
        self::EXPIRED                        => '-',
        self::CONFORM_COHERENT_NOT_QUALIFIED => 'Vérifié sauf prénom du titulaire non vérifié',
        self::CONFORM_COHERENT_QUALIFIED     => 'Conforme, cohérent et valide'
    );

    /**
     * @param $aResponse
     * @param null $iAttachmentTypeId
     * @param null $iAttachmentId
     * @param null $iClientId
     * @param null $iCode
     * @return array
     */
    public static function getGreenPointData($aResponse, $iAttachmentTypeId = null, $iAttachmentId = null, $iClientId = null, $iCode = null)
    {
        $fGetColumnValue = function ($array, $key, $mCurrentValue = null) {
            if (empty($mCurrentValue)){
                return isset($array[$key]) ? $array[$key] : null;
            } else {
                return $mCurrentValue;
            }
        };

        $fFormatDate = function ($sDate) {
            $oDate = \DateTime::createFromFormat('d/m/Y', $sDate);
            if (is_object($oDate)) {
                return $oDate->format('Y-m-d');
            } else {
                return null;
            }
        };

        $aAttachment['id_attachment']   = ! is_null($iAttachmentId) ? $iAttachmentId : $fGetColumnValue($aResponse, 'document');
        $aAttachment['validation_code'] = ! is_null($iCode) ? $iCode : $fGetColumnValue($aResponse, 'code');
        $aAttachment['id_client']       = ! is_null($iClientId) ? $iClientId : $fGetColumnValue($aResponse, 'dossier');

        $aAttachment['validation_status'] = $fGetColumnValue($aResponse, 'statut_verification');

        switch ($iAttachmentTypeId) {
            case \attachment_type::CNI_PASSPORTE:
            case \attachment_type::CNI_PASSPORTE_VERSO:
            case \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT:
            case \attachment_type::CNI_PASSPORTE_DIRIGEANT:
                $aAttachment['validation_status_label'] = $fGetColumnValue(self::$aIdControlStatusLabel, (int) $aAttachment['validation_status']);
                break;
            case \attachment_type::RIB:
                $aAttachment['validation_status_label'] = $fGetColumnValue(self::$aIbanFlashStatusLabel, (int) $aAttachment['validation_status']);
                break;
            case \attachment_type::JUSTIFICATIF_DOMICILE:
            case \attachment_type::ATTESTATION_HEBERGEMENT_TIERS:
                $aAttachment['validation_status_label'] = $fGetColumnValue(self::$aAddressControlStatusLabel, (int) $aAttachment['validation_status']);
                break;
        }
        $aAttachment['agency'] = null;

        $aAttachmentDetail['document_type']              = $fGetColumnValue($aResponse, 'type');
        $aAttachmentDetail['identity_civility']          = $fGetColumnValue($aResponse, 'sexe');
        $aAttachmentDetail['identity_name']              = $fGetColumnValue($aResponse, 'prenom');
        $aAttachmentDetail['identity_surname']           = $fGetColumnValue($aResponse, 'nom');
        $aAttachmentDetail['identity_expiration_date']   = $fFormatDate($fGetColumnValue($aResponse, 'expirationdate'));
        $aAttachmentDetail['identity_birthdate']         = $fFormatDate($fGetColumnValue($aResponse, 'date_naissance'));
        $aAttachmentDetail['identity_mrz1']              = $fGetColumnValue($aResponse, 'mrz1');
        $aAttachmentDetail['identity_mrz2']              = $fGetColumnValue($aResponse, 'mrz2');
        $aAttachmentDetail['identity_mrz3']              = $fGetColumnValue($aResponse, 'mrz3');
        $aAttachmentDetail['identity_nationality']       = $fGetColumnValue($aResponse, 'nationalite');
        $aAttachmentDetail['identity_issuing_country']   = $fGetColumnValue($aResponse, 'pays_emetteur');
        $aAttachmentDetail['identity_issuing_authority'] = $fGetColumnValue($aResponse, 'autorite_emettrice');
        $aAttachmentDetail['identity_document_number']   = $fGetColumnValue($aResponse, 'numero');
        $aAttachmentDetail['identity_document_type_id']  = $fGetColumnValue($aResponse, 'type_id');
        $aAttachmentDetail['bank_details_iban']          = $fGetColumnValue($aResponse, 'iban');
        $aAttachmentDetail['bank_details_bic']           = $fGetColumnValue($aResponse, 'bic');
        $aAttachmentDetail['bank_details_url']           = $fGetColumnValue($aResponse, 'url');
        $aAttachmentDetail['address_address']            = $fGetColumnValue($aResponse, 'adresse');
        $aAttachmentDetail['address_postal_code']        = $fGetColumnValue($aResponse, 'code_postal');
        $aAttachmentDetail['address_city']               = $fGetColumnValue($aResponse, 'ville');
        $aAttachmentDetail['address_country']            = $fGetColumnValue($aResponse, 'pays');

        return array('greenpoint_attachment' => $aAttachment, 'greenpoint_attachment_detail' => $aAttachmentDetail);
    }
}