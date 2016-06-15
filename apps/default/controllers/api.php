<?php

use Unilend\librairies\greenPoint\greenPointStatus;
use Unilend\librairies\greenPoint\greenPoint;
use Psr\Log\LoggerInterface;

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
            for ($iSuffix = (int) $aAllowedIPSettings['min_range']; $iSuffix <= $aAllowedIPSettings['max_range']; $iSuffix++) {
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
        $this->oLogger->info('************************************* Begin GreenPoint Asynchronous return *************************************', array('class' => __CLASS__, 'function' => __FUNCTION__));

        /** @var \greenpoint_attachment $oGreenPointAttachment */
        $oGreenPointAttachment = $this->loadData('greenpoint_attachment');

        /** @var \greenpoint_attachment_detail $oGreenPointAttachmentDetail */
        $oGreenPointAttachmentDetail = $this->loadData('greenpoint_attachment_detail');
        
        $this->oLogger->info('Input parameters : ' . var_export($this->aData, true), array('class' => __CLASS__, 'function' => __FUNCTION__));
        $oGreenPointAttachment->get($this->aData['document'], 'id_attachment');
        $oGreenPointAttachmentDetail->get($oGreenPointAttachment->id_greenpoint_attachment, 'id_greenpoint_attachment');

        switch ($this->aData['type']) {
            case '1':
                $aGreenPointData = greenPointStatus::getGreenPointData($this->aData, attachment_type::CNI_PASSPORTE_DIRIGEANT);
                break;
            case '2':
                $aGreenPointData = greenPointStatus::getGreenPointData($this->aData, attachment_type::RIB);
                break;
            case '3':
                $aGreenPointData = greenPointStatus::getGreenPointData($this->aData, attachment_type::JUSTIFICATIF_DOMICILE);
                break;
            default:
                $aGreenPointData = array();
                break;
        }
        if (empty($aGreenPointData)) {
            $this->oLogger->error('Wrong type value. Expected to be one of [1, 2, 3]', array('class' => __CLASS__, 'function' => __FUNCTION__));
            $this->_404();
        }
        $this->oLogger->info('Parsed Data from input params : ' . var_export($aGreenPointData, true), array('class' => __CLASS__, 'function' => __FUNCTION__));
        foreach ($aGreenPointData['greenpoint_attachment'] as $sKey => $mValue) {
            if (false === is_null($mValue)) {
                $oGreenPointAttachment->$sKey = $mValue;
            }
        }
        $oGreenPointAttachment->final_status = 1;
        $oGreenPointAttachment->revalidate   = 0;
        $oGreenPointAttachment->update();

        foreach ($aGreenPointData['greenpoint_attachment_detail'] as $sKey => $mValue) {
            if (false === is_null($mValue)) {
                $oGreenPointAttachmentDetail->$sKey = $mValue;
            }
        }
        $oGreenPointAttachmentDetail->update();
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
