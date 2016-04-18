<?php

use Unilend\librairies\greenPoint\greenPointStatus;

class apiController extends Controller
{
    private $oGreenPointAttachment;
    private $oGreenPointAttachmentDetail;
    private $aData;

    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);
        $this->autoFireView = false;
        $this->hideDecoration();

        $this->catchAll = true;

        $this->checkIp();

        $this->oGreenPointAttachment       = $this->loadData('greenpoint_attachment');
        $this->oGreenPointAttachmentDetail = $this->loadData('greenpoint_attachment_detail');
        $this->init();
    }

    public function __destruct()
    {
        unset($this->oGreenPointAttachment, $this->oGreenPointAttachmentDetail);
    }

    /**
     * Check the Remote server IP address
     */
    private function checkIp()
    {
        $aAllowedIP = array();
        $oSettings  = $this->loadData('settings');
        switch ($this->Config['env']) {
            case 'prod':
                $oSettings->get('green_point_ip_prod', 'type');
                $sAllowedIPSettings = $oSettings->value;
                break;
            default:
                $oSettings->get('green_point_ip_prod', 'type');
                $sAllowedIPSettings = $oSettings->value;
        }

        $aAllowedIPSettings = json_decode($sAllowedIPSettings, 1);

        if (false === isset($aAllowedIPSettings['root'])) {
            header("HTTP/1.0 500 Internal Server Error");
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
        if (false === in_array($_SERVER['REMOTE_ADDR'], $aAllowedIP)) {
            header("HTTP/1.0 403 Forbidden");
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
        $this->oGreenPointAttachment->get($this->aData['document'], 'id_attachment');
        $this->oGreenPointAttachmentDetail->get($this->oGreenPointAttachment->id_greenpoint_attachment, 'id_greenpoint_attachment');
    }

    /**
     * Service called by green point BO in asynch mode to update the verification status of the attachments.
     */
    public function _update_status()
    {
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
        }
        if (empty($aGreenPointData)) {
            $this->_404();
        }
        foreach ($aGreenPointData['greenpoint_attachment'] as $sKey => $mValue) {
            if (false === is_null($mValue)) {
                $this->oGreenPointAttachment->$sKey = $mValue;
            }
        }
        $this->oGreenPointAttachment->update();

        foreach ($aGreenPointData['greenpoint_attachment_detail'] as $sKey => $mValue) {
            if (false === is_null($mValue)) {
                $this->oGreenPointAttachmentDetail->$sKey = $mValue;
            }
        }
        $this->oGreenPointAttachmentDetail->update();

        echo 1;
        exit;
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
            } else {
                if (false !== ($mValue = filter_input(INPUT_POST, $mKey, FILTER_SANITIZE_STRING))) {
                    $aFilteredInput[$mKey] = trim($mValue);
                }
            }
        }
        return $aFilteredInput;
    }
}
