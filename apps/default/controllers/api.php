<?php

use Unilend\librairies\greenPoint\greenPointStatus;
use Unilend\librairies\greenPoint\greenPoint;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachmentDetail;

class apiController extends Controller
{
    /**
     * @var array Posted data from GP systeme
     */
    private $aData;

    /** @var LoggerInterface */
    private $oLogger;

    public function initialize()
    {
        parent::initialize();

        $this->autoFireView = false;
        $this->hideDecoration();

        $this->catchAll = true;

        $this->oLogger = $this->get('logger');

        $this->checkIp();
        $this->init();
    }

    /**
     * Check the Remote server IP address
     */
    private function checkIp()
    {
        $aAllowedIP = array();
        $oSettings  = $this->loadData('settings');
        switch ($this->getParameter('kernel.environment')) {
            case 'prod':
                $oSettings->get('green_point_ip_prod', 'type');
                $sAllowedIPSettings = $oSettings->value;
                break;
            default:
                $oSettings->get('green_point_ip_test', 'type');
                $sAllowedIPSettings = $oSettings->value;
                $oSettings->get('green_point_ip_local', 'type');
                $sLocalIp = $oSettings->value;
                break;
        }

        $aAllowedIPSettings = json_decode($sAllowedIPSettings, true);

        if (false === isset($aAllowedIPSettings['root'])) {
            header('HTTP/1.0 500 Internal Server Error');
            echo 'Internal Server Error';
            die;
        }
        if (false === empty($aAllowedIPSettings['out_of_range'])) {
            foreach (explode(',', $aAllowedIPSettings['out_of_range']) as $iSuffix) {
                $aAllowedIP[] = $aAllowedIPSettings['root'] . $iSuffix;
            }
        }
        if (false === empty($aAllowedIPSettings['min_range']) && false === empty($aAllowedIPSettings['max_range'])) {
            for ($iSuffix = (int)$aAllowedIPSettings['min_range']; $iSuffix <= $aAllowedIPSettings['max_range']; $iSuffix++) {
                $aAllowedIP[] = $aAllowedIPSettings['root'] . $iSuffix;
            }
        }

        $this->oLogger->info('Allowed IP : ' . var_export($aAllowedIP, true) . ' Local IP : ' . $sLocalIp, array('class' => __CLASS__, 'function' => __FUNCTION__));

        if (false === in_array($_SERVER['REMOTE_ADDR'], $aAllowedIP) && false === in_array($_SERVER['REMOTE_ADDR'], explode(',', $sLocalIp))) {
            header('HTTP/1.0 403 Forbidden');
            echo 'Forbidden';
            die;
        }
    }

    /**
     * Check "document" and "dossier" parameters and then initialize the green point objects
     */
    private function init()
    {
        if ('POST' !== $_SERVER['REQUEST_METHOD']) {
            echo 405;
            exit;
        }
        $this->aData = $this->filterPost();
        if (empty($this->aData['document']) || empty($this->aData['dossier'])) {
            echo 400;
            die;
        }
    }

    /**
     * Service called by green point BO in asynch mode to update the verification status of the attachments.
     */
    public function _update_status()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $this->oLogger->info('Start GreenPoint Asynchronous return', array('class' => __CLASS__, 'function' => __FUNCTION__));
        $this->oLogger->info('Input parameters : ' . var_export($this->aData, true), array('class' => __CLASS__, 'function' => __FUNCTION__));

        switch ($this->aData['type']) {
            case 1:
                $greenPointData = greenPointStatus::getGreenPointData($this->aData, attachment_type::CNI_PASSPORTE_DIRIGEANT);
                break;
            case 2:
                $greenPointData = greenPointStatus::getGreenPointData($this->aData, attachment_type::RIB);
                break;
            case 3:
                $greenPointData = greenPointStatus::getGreenPointData($this->aData, attachment_type::JUSTIFICATIF_DOMICILE);
                break;
            default:
                $this->oLogger->error('Wrong type value (' . $this->aData['type'] . '). Expected to be one of [1, 2, 3]', array('class' => __CLASS__, 'function' => __FUNCTION__));
                $this->_404();
        }

        $this->oLogger->info('Parsed Data from input params : ' . var_export($greenPointData, true), array('class' => __CLASS__, 'function' => __FUNCTION__));

        $greenPointAttachment = $entityManager->getRepository('UnilendCoreBusinessBundle:GreenpointAttachment')->findOneBy(['idAttachment' => $this->aData['document']]);
        if (null === $greenPointAttachment) {
            $attachment = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->find($this->aData['document']);
            if (null === $attachment) {
                $this->oLogger->error('Attachment id : ' . $this->aData['document'] . ' not found. Input parameters : ' . var_export($this->aData, true),
                    array('class' => __CLASS__, 'function' => __FUNCTION__));
                exit;
            }
            $greenPointAttachment = new GreenpointAttachment();
            $greenPointAttachment->setIdAttachment($attachment);
            $entityManager->persist($greenPointAttachment);
        }
        $greenPointAttachment->setValidationCode($greenPointData['greenpoint_attachment']['validation_code'])
                             ->setValidationStatus($greenPointData['greenpoint_attachment']['validation_status'])
                             ->setValidationStatusLabel($greenPointData['greenpoint_attachment']['validation_status_label']);
        $entityManager->flush($greenPointAttachment);

        $greenPointAttachmentDetails = $greenPointAttachment->getGreenpointAttachmentDetail();
        if (null === $greenPointAttachmentDetails) {
            $greenPointAttachmentDetails = new GreenpointAttachmentDetail();
            $greenPointAttachmentDetails->setIdGreenpointAttachment($greenPointAttachment);
            $entityManager->persist($greenPointAttachmentDetails);
        }
        $greenPointAttachmentDetails->setDocumentType($greenPointData['greenpoint_attachment_detail']['document_type'])
                                    ->setIdentityCivility($greenPointData['greenpoint_attachment_detail']['identity_civility'])
                                    ->setIdentityName($greenPointData['greenpoint_attachment_detail']['identity_name'])
                                    ->setIdentitySurname($greenPointData['greenpoint_attachment_detail']['identity_surname'])
                                    ->setIdentityExpirationDate($greenPointData['greenpoint_attachment_detail']['identity_expiration_date'])
                                    ->setIdentityBirthdate($greenPointData['greenpoint_attachment_detail']['identity_birthdate'])
                                    ->setIdentityMrz1($greenPointData['greenpoint_attachment_detail']['identity_mrz1'])
                                    ->setIdentityMrz2($greenPointData['greenpoint_attachment_detail']['identity_mrz2'])
                                    ->setIdentityMrz3($greenPointData['greenpoint_attachment_detail']['identity_mrz3'])
                                    ->setIdentityNationality($greenPointData['greenpoint_attachment_detail']['identity_nationality'])
                                    ->setIdentityIssuingCountry($greenPointData['greenpoint_attachment_detail']['identity_issuing_country'])
                                    ->setIdentityIssuingAuthority($greenPointData['greenpoint_attachment_detail']['identity_issuing_authority'])
                                    ->setIdentityDocumentNumber($greenPointData['greenpoint_attachment_detail']['identity_document_number'])
                                    ->setIdentityDocumentTypeId($greenPointData['greenpoint_attachment_detail']['identity_document_type_id'])
                                    ->setBankDetailsIban($greenPointData['greenpoint_attachment_detail']['bank_details_iban'])
                                    ->setBankDetailsBic($greenPointData['greenpoint_attachment_detail']['bank_details_bic'])
                                    ->setBankDetailsUrl($greenPointData['greenpoint_attachment_detail']['bank_details_url'])
                                    ->setAddressAddress($greenPointData['greenpoint_attachment_detail']['address_address'])
                                    ->setAddressPostalCode($greenPointData['greenpoint_attachment_detail']['address_postal_code'])
                                    ->setAddressCity($greenPointData['greenpoint_attachment_detail']['address_city'])
                                    ->setAddressCountry($greenPointData['greenpoint_attachment_detail']['address_country']);
        $entityManager->flush($greenPointAttachmentDetails);
        $this->updateGreenPointKyc($this->aData['dossier']);

        echo 1;
    }

    private function updateGreenPointKyc($iClientId)
    {
        /** @var \greenpoint_kyc $oGreenPointKyc */
        $oGreenPointKyc = $this->loadData('greenpoint_kyc');

        /** @var greenPoint $oGreenPoint */
        $oGreenPoint = new greenPoint($this->getParameter('kernel.environment'));
        greenPointStatus::addCustomer($iClientId, $oGreenPoint, $oGreenPointKyc);
    }

    public function _default()
    {
        parent::_404();
    }

    private function filterPost()
    {
        $aFilteredInput = array();
        foreach (array_keys($_POST) as $mKey) {
            if (strstr($mKey, 'mrz')) {
                $aFilteredInput[$mKey] = trim($_POST[$mKey]);
            } elseif (false !== ($mValue = filter_input(INPUT_POST, $mKey, FILTER_SANITIZE_STRING))) {
                $aFilteredInput[$mKey] = trim($mValue);
            }
        }
        return $aFilteredInput;
    }
}
