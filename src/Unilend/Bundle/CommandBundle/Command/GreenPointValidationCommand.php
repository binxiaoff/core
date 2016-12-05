<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\librairies\greenPoint\greenPoint;
use Unilend\librairies\greenPoint\greenPointStatus;
use Unilend\core\Loader;

class GreenPointValidationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('lender:greenpoint_validation')
            ->setDescription('Validate the lenders attachment via Green Point service')
            ->setHelp(<<<EOF
The <info>lender:loan_contract</info> validates lenders documents : identity bank details and address.
<info>php bin/console lender:greenpoint_validation</info>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $oEntityManager = $this->getContainer()->get('unilend.service.entity_manager');

        $this->greenPointValidation($oEntityManager);
    }


    private function greenPointValidation(EntityManager $oEntityManager)
    {
        /** @var \clients $oClients */
        $oClients = $oEntityManager->getRepository('clients');
        /** @var \greenpoint_attachment $oGreenPointAttachment */
        $oGreenPointAttachment = $oEntityManager->getRepository('greenpoint_attachment');
        /** @var \greenpoint_kyc $oGreenPointKyc */
        $oGreenPointKyc = $oEntityManager->getRepository('greenpoint_kyc');
        /** @var LoggerInterface $oLogger */
        $oLogger = $this->getContainer()->get('monolog.logger.console');

        $aStatusToCheck           = [
            \clients_status::TO_BE_CHECKED,
            \clients_status::COMPLETENESS_REPLY,
            \clients_status::MODIFICATION
        ];
        $attachmentTypeToValidate = [
            \attachment_type::CNI_PASSPORTE,
            \attachment_type::JUSTIFICATIF_DOMICILE,
            \attachment_type::ATTESTATION_HEBERGEMENT_TIERS,
            \attachment_type::CNI_PASSPORT_TIERS_HEBERGEANT,
            \attachment_type::CNI_PASSPORTE_DIRIGEANT,
            \attachment_type::RIB,
            \attachment_type::DELEGATION_POUVOIR,
            \attachment_type::KBIS,
            \attachment_type::JUSTIFICATIF_FISCAL,
        ];
        $aQueryID                 = [];
        $aClientsToCheck          = $oClients->selectLendersByLastStatus($aStatusToCheck);

        if (false === empty($aClientsToCheck)) {
            /** @var \lenders_accounts $oLendersAccount */
            $oLendersAccount = $oEntityManager->getRepository('lenders_accounts');
            /** @var greenPoint $oGreenPoint */
            $oGreenPoint = new greenPoint($this->getContainer()->getParameter('kernel.environment'));
            /** @var \attachment $oAttachment */
            $oAttachment = $oEntityManager->getRepository('attachment');
            /** @var \attachment_type $oAttachmentType */
            $oAttachmentType = $oEntityManager->getRepository('attachment_type');
            /** @var \attachment_helper $oAttachmentHelper */
            $oAttachmentHelper = Loader::loadLib('attachment_helper', [$oAttachment, $oAttachmentType, $this->getContainer()->getParameter('kernel.root_dir') . '/../']);

            foreach ($aClientsToCheck as $iClientId => $aClient) {
                $aAttachments = $oLendersAccount->getAttachments($aClient['id_lender_account'], $attachmentTypeToValidate);
                /** @var array $aAttachmentsToRevalidate */
                $aAttachmentsToRevalidate = [];

                if (false === empty($aAttachments)) {
                    foreach ($aAttachments as $iAttachmentTypeId => $aAttachment) {
                        if ($oGreenPointAttachment->get($aAttachment['id'], 'id_attachment') && 0 == $oGreenPointAttachment->revalidate) {
                            continue;
                        } elseif (1 == $oGreenPointAttachment->revalidate) {
                            $aAttachmentsToRevalidate[$iAttachmentTypeId] = $oGreenPointAttachment->id_greenpoint_attachment;
                        }
                        $sAttachmentPath = $oAttachmentHelper->getFullPath($aAttachment['type_owner'], $aAttachment['id_type']) . $aAttachment['path'];
                        $sFullPath       = realpath($sAttachmentPath);

                        if (false == $sFullPath) {
                            $oLogger->error('Attachment not found (ID ' . $aAttachment['id'] . ')', ['class' => __CLASS__, 'function' => __FUNCTION__]);
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
                            $oLogger->error(
                                'Greenpoint was unable to process data (client ' . $iClientId . ') - Message: ' . $oException->getMessage() . ' - Code: ' . $oException->getCode(),
                                ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $iClientId]
                            );
                        }
                    }

                    if (false === empty($aQueryID) && is_array($aQueryID)) {
                        $aResult = $oGreenPoint->sendRequests();
                        $this->processGreenPointResponse($iClientId, $aResult, $aQueryID, $aAttachmentsToRevalidate, $oEntityManager);
                        unset($aResult, $aQueryID);
                        greenPointStatus::addCustomer($iClientId, $oGreenPoint, $oGreenPointKyc);
                    }
                }
            }
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
        $aData = [
            'files'    => new \CURLFile($sPath),
            'dossier'  => $iClientId,
            'document' => $iAttachmentId,
            'detail'   => 1,
            'nom'      => $this->getFamilyNames($aClient['nom'], $aClient['nom_usage']),
            'prenom'   => $aClient['prenom']
        ];

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
        $aData['adresse']     = $aClient['adresse_fiscal'];
        $aData['code_postal'] = $aClient['cp_fiscal'];
        $aData['ville']       = $aClient['ville_fiscal'];
        $aData['pays']        = strtoupper($aClient['fr']);
    }

    /**
     * @param int $iClientId
     * @param array $aResponseDetail
     * @param array $aResponseKeys
     * @param array $aExistingAttachment
     * @param EntityManager $oEntityManager
     */
    private function processGreenPointResponse($iClientId, array $aResponseDetail, array $aResponseKeys, array $aExistingAttachment, EntityManager $oEntityManager)
    {
        /** @var \greenpoint_attachment $oGreenPointAttachment */
        $oGreenPointAttachment = $oEntityManager->getRepository('greenpoint_attachment');

        /** @var \greenpoint_attachment_detail $oGreenPointAttachmentDetail */
        $oGreenPointAttachmentDetail = $oEntityManager->getRepository('greenpoint_attachment_detail');

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
                $aGreenPointData = greenPointStatus::getGreenPointData([], $iAttachmentTypeId, $iAttachmentId, $iClientId, $aResponse['code']);
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