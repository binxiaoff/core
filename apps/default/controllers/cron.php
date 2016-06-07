<?php

use Psr\Log\LoggerInterface;
use Unilend\librairies\greenPoint\greenPoint;
use Unilend\librairies\greenPoint\greenPointStatus;

class cronController extends bootstrap
{
    /**
     * @var string $sHeadersDebug headers for mail to debug
     */
    private $sHeadersDebug;

    /**
     * @var string $sDestinatairesDebug Destinataires for mail to debug
     */
    private $sDestinatairesDebug;

    /**
     * @var int
     */
    private $iStartTime;

    /**
     * @var settings
     */
    private $oSemaphore;

    /** @var  LoggerInterface */
    private $oLogger;

    public function initialize()
    {
        parent::initialize();

        // Inclusion controller pdf
        include_once $this->path . '/apps/default/controllers/pdf.php';

        $this->hideDecoration();
        $this->autoFireView = false;
        $this->oLogger = $this->get('monolog.logger.console');

        $this->settings->get('DebugMailFrom', 'type');
        $debugEmail = $this->settings->value;
        $this->settings->get('DebugMailIt', 'type');
        $this->sDestinatairesDebug = $this->settings->value;
        $this->sHeadersDebug       = 'MIME-Version: 1.0' . "\r\n";
        $this->sHeadersDebug .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $this->sHeadersDebug .= 'From: ' . $debugEmail . "\r\n";
    }

    /**
     * @param $sName  string Cron name (used for settings name)
     * @param $iDelay int    Minimum delay (in minutes) before we consider cron has crashed and needs to be restarted
     * @return bool
     */
    private function startCron($sName, $iDelay)
    {
        $this->iStartTime = time();
        $this->oSemaphore = $this->loadData('settings');
        $this->oSemaphore->get('Controle cron ' . $sName, 'type');

        if ($this->oSemaphore->value == 0) {
            $iUpdatedDateTime      = strtotime($this->oSemaphore->updated);
            $iMinimumDelayDateTime = mktime(date('H'), date('i') - $iDelay, 0, date('m'), date('d'), date('Y'));

            if ($iUpdatedDateTime <= $iMinimumDelayDateTime) {
                $this->oSemaphore->value = 1;
                $this->oSemaphore->update();
            }
        }

        if ($this->oSemaphore->value == 1) {
            $this->oSemaphore->value = 0;
            $this->oSemaphore->update();
            $this->oLogger->info('Started cron ' . $sName . ' - Cron ID=' . $this->iStartTime, array('class' => __CLASS__, 'function' => __FUNCTION__));

            return true;
        }
        $this->oLogger->info('Semaphore locked', array('class' => __CLASS__, 'function' => __FUNCTION__));

        return false;
    }

    private function stopCron()
    {
        $this->oSemaphore->value = 1;
        $this->oSemaphore->update();
        $this->oLogger->info('End cron ID=' . $this->iStartTime, array('class' => __CLASS__, 'function' => __FUNCTION__));
    }

    public function _default()
    {
        die;
    }

    public function _greenPointValidation()
    {
        if (true === $this->startCron('green_point_attachment_validation', 10)) {
            /** @var \clients $oClients */
            $oClients = $this->loadData('clients');

            /** @var \greenpoint_attachment $oGreenPointAttachment */
            $oGreenPointAttachment = $this->loadData('greenpoint_attachment');

            /** @var \greenpoint_kyc $oGreenPointKyc */
            $oGreenPointKyc = $this->loadData('greenpoint_kyc');

            $bDebug = true;
            if ($bDebug) {
                $this->oLogger->info('************************************* Begin GreenPoint Validation *************************************', array('class' => __CLASS__, 'function' => __FUNCTION__));
            }
            $aStatusToCheck = array(
                \clients_status::TO_BE_CHECKED,
                \clients_status::COMPLETENESS_REPLY,
                \clients_status::MODIFICATION
            );

            $aQueryID        = array();
            $aClientsToCheck = $oClients->selectLendersByLastStatus($aStatusToCheck);

            if (false === empty($aClientsToCheck)) {
                /** @var \lenders_accounts $oLendersAccount */
                $oLendersAccount = $this->loadData('lenders_accounts');

                /** @var greenPoint $oGreenPoint */
                $oGreenPoint = new greenPoint();

                /** @var \attachment $oAttachment */
                $oAttachment = $this->loadData('attachment');

                /** @var \attachment_type $oAttachmentType */
                $oAttachmentType = $this->loadData('attachment_type');

                /** @var \attachment_helper $oAttachmentHelper */
                $oAttachmentHelper = $this->loadLib('attachment_helper', array($oAttachment, $oAttachmentType, $this->path));

                foreach ($aClientsToCheck as $iClientId => $aClient) {
                    $aAttachments = $oLendersAccount->getAttachments($aClient['id_lender_account']);

                    /** @var array $aAttachmentsToRevalidate */
                    $aAttachmentsToRevalidate = array();

                    if (false === empty($aAttachments)) {
                        $aError = array();
                        foreach ($aAttachments as $iAttachmentTypeId => $aAttachment) {
                            if ($oGreenPointAttachment->get($aAttachment['id'], 'id_attachment') && 0 == $oGreenPointAttachment->revalidate) {
                                continue;
                            } elseif (1 == $oGreenPointAttachment->revalidate) {
                                $aAttachmentsToRevalidate[$iAttachmentTypeId] = $oGreenPointAttachment->id_greenpoint_attachment;
                            }
                            $sAttachmentPath = $oAttachmentHelper->getFullPath($aAttachment['type_owner'], $aAttachment['id_type']) . $aAttachment['path'];
                            $sFullPath       = realpath($sAttachmentPath);

                            if (false == $sFullPath) {
                                if ($bDebug) {
                                    $this->oLogger->error('Attachment not found - ID=' . $aAttachment['id'], array('class' => __CLASS__, 'function' => __FUNCTION__));
                                }
                                continue;
                            }
                            try {
                                switch ($iAttachmentTypeId) {
                                    case \attachment_type::CNI_PASSPORTE:
                                    case \attachment_type::CNI_PASSPORTE_VERSO:
                                    case \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT:
                                    case \attachment_type::CNI_PASSPORTE_DIRIGEANT:
                                        $aData            = $this->getGreenPointData($iClientId, $aAttachment['id'], $sFullPath, $aClient, 'idcontrol');
                                        $iQRID            = $oGreenPoint->idControl($aData, false);
                                        $aQueryID[$iQRID] = $iAttachmentTypeId;
                                        break;
                                    case \attachment_type::RIB:
                                        $aData            = $this->getGreenPointData($iClientId, $aAttachment['id'], $sFullPath, $aClient, 'ibanflash');
                                        $iQRID            = $oGreenPoint->ibanFlash($aData, false);
                                        $aQueryID[$iQRID] = $iAttachmentTypeId;
                                        break;
                                    case \attachment_type::JUSTIFICATIF_DOMICILE:
                                    case \attachment_type::ATTESTATION_HEBERGEMENT_TIERS:
                                        $aData            = $this->getGreenPointData($iClientId, $aAttachment['id'], $sFullPath, $aClient, 'addresscontrol');
                                        $iQRID            = $oGreenPoint->addressControl($aData, false);
                                        $aQueryID[$iQRID] = $iAttachmentTypeId;
                                        break;
                                }
                            } catch (\Exception $oException) {
                                $aError[$aAttachment['id']][$iAttachmentTypeId] = array('iErrorCode' => $oException->getCode(), 'sErrorMessage' => $oException->getMessage());
                                unset($oException);
                            }
                        }
                        if ($bDebug && false === empty($aError)) {
                            $this->oLogger->error('CLIENT_ID=' . $iClientId . ' - Catched Exceptions : ' . var_export($aError, 1), __METHOD__);
                        }
                        if (false === empty($aQueryID) && is_array($aQueryID)) {
                            $aResult = $oGreenPoint->sendRequests();
                            if ($bDebug) {
                                $this->oLogger->info('CLIENT_ID=' . $iClientId . ' - Request Details : ' . var_export($aResult, 1), array('class' => __CLASS__, 'function' => __FUNCTION__));
                            }
                            $this->processGreenPointResponse($iClientId, $aResult, $aQueryID, $aAttachmentsToRevalidate);
                            unset($aResult, $aQueryID);
                            greenPointStatus::addCustomer($iClientId, $oGreenPoint, $oGreenPointKyc);
                        }
                    }
                }
            }
            if ($bDebug) {
                $this->oLogger->info('************************************* End GreenPoint Validation *************************************', array('class' => __CLASS__, 'function' => __FUNCTION__));
            }
            $this->stopCron();
        }
    }

    /**
     * @param int $iClientId
     * @param int $iAttachmentId
     * @param string $sPath
     * @param array $aClient
     * @param string $sType
     * @return array
     */
    private function getGreenPointData($iClientId, $iAttachmentId, $sPath, array $aClient, $sType)
    {
        $aData = array(
            'files'    => '@' . $sPath,
            'dossier'  => $iClientId,
            'document' => $iAttachmentId,
            'detail'   => 1,
            'nom'      => $this->getFamilyNames($aClient['nom'], $aClient['nom_usage']),
            'prenom'   => $aClient['prenom']
        );

        switch ($sType) {
            case 'idcontrol':
                $this->addIdControlData($aData, $aClient);
                return $aData;
            case 'ibanflash':
                $this->addIbanData($aData, $aClient);
                return $aData;
            case 'addresscontrol':
                $this->addAddressData($aData, $aClient);
                return $aData;
            default:
                return $aData;
        }
    }

    /**
     * @param string $sFamilyName
     * @param string $sUseName
     * @return string
     */
    private function getFamilyNames($sFamilyName, $sUseName)
    {
        $sAllowedNames = $sFamilyName;
        if (false === empty($sUseName)) {
            $sAllowedNames .= '|' . $sUseName;
        }
        return $sAllowedNames;
    }

    /**
     * @param array $aData
     * @param array $aClient
     */
    private function addIdControlData(array &$aData, array $aClient)
    {
        $aData['date_naissance'] = $aClient['naissance'];
    }

    /**
     * @param array $aData
     * @param array $aClient
     */
    private function addIbanData(array &$aData, array $aClient)
    {
        $aData['iban'] = $aClient['iban'];
        $aData['bic']  = $aClient['bic'];
    }

    /**
     * @param array $aData
     * @param array $aClient
     */
    private function addAddressData(array &$aData, array $aClient)
    {
        $aData['adresse']     = $this->getFullAddress($aClient['adresse1'], $aClient['adresse2'], $aClient['adresse3']);
        $aData['code_postal'] = $aClient['cp'];
        $aData['ville']       = $aClient['ville'];
        $aData['pays']        = strtoupper($aClient['fr']);
    }

    /**
     * @param string $sAddress1
     * @param string $sAddress2
     * @param string $sAddress3
     * @return string
     */
    private function getFullAddress($sAddress1, $sAddress2, $sAddress3)
    {
        $sFullAddress = $sAddress1;
        if (false === empty($sAddress2)) {
            $sFullAddress .= ' ' . $sAddress2;
        }
        if (false === empty($sAddress3)) {
            $sFullAddress .= ' ' . $sAddress3;
        }
        return $sFullAddress;
    }

    /**
     * @param int $iClientId
     * @param array $aResponseDetail
     * @param array $aResponseKeys
     * @param array $aExistingAttachment
     */
    private function processGreenPointResponse($iClientId, array $aResponseDetail, array $aResponseKeys, array $aExistingAttachment)
    {
        /** @var \greenpoint_attachment $oGreenPointAttachment */
        $oGreenPointAttachment = $this->loadData('greenpoint_attachment');

        /** @var \greenpoint_attachment_detail $oGreenPointAttachmentDetail */
        $oGreenPointAttachmentDetail = $this->loadData('greenpoint_attachment_detail');

        foreach ($aResponseKeys as $iQRID => $iAttachmentTypeId) {
            if (false === isset($aResponseDetail[$iQRID])) {
                continue;
            }

            if (isset($aExistingAttachment[$iAttachmentTypeId]) && $oGreenPointAttachment->get($aExistingAttachment[$iAttachmentTypeId], 'id_greenpoint_attachment')) {
                $bUpdate = true;
            } else {
                $bUpdate = false;
            }
            $oGreenPointAttachment->control_level = 1;
            $oGreenPointAttachment->revalidate    = 0;
            $oGreenPointAttachment->final_status  = 0;
            $iAttachmentId                        = $aResponseDetail[$iQRID]['REQUEST_PARAMS']['document'];
            $aResponse                            = json_decode($aResponseDetail[$iQRID]['RESPONSE'], true);

            if (isset($aResponse['resource']) && is_array($aResponse['resource'])) {
                $aGreenPointData = greenPointStatus::getGreenPointData($aResponse['resource'], $iAttachmentTypeId, $iAttachmentId, $iClientId, $aResponse['code']);
            } else {
                $aGreenPointData = greenPointStatus::getGreenPointData(array(), $iAttachmentTypeId, $iAttachmentId, $iClientId, $aResponse['code']);
            }

            foreach ($aGreenPointData['greenpoint_attachment'] as $sKey => $mValue) {
                if (false === is_null($mValue)) {
                    $oGreenPointAttachment->$sKey = $mValue;
                }
            }

            if ($bUpdate) {
                $oGreenPointAttachment->update();
                $oGreenPointAttachmentDetail->get($oGreenPointAttachment->id_greenpoint_attachment, 'id_greenpoint_attachment');
            } else {
                $oGreenPointAttachment->create();
                $oGreenPointAttachmentDetail->id_greenpoint_attachment = $oGreenPointAttachment->id_greenpoint_attachment;
            }

            foreach ($aGreenPointData['greenpoint_attachment_detail'] as $sKey => $mValue) {
                if (false === is_null($mValue)) {
                    $oGreenPointAttachmentDetail->$sKey = $mValue;
                }
            }

            if ($bUpdate) {
                $oGreenPointAttachmentDetail->update();
            } else {
                $oGreenPointAttachmentDetail->create();
            }
            $oGreenPointAttachment->unsetData();
            $oGreenPointAttachmentDetail->unsetData();
        }
    }
}
