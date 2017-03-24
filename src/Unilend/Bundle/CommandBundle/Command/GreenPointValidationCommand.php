<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachmentDetail;
use Unilend\librairies\greenPoint\greenPoint;
use Unilend\librairies\greenPoint\greenPointStatus;

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

        /** @var \clients $oClients */
        $oClients = $oEntityManager->getRepository('clients');
        /** @var \greenpoint_kyc $oGreenPointKyc */
        $oGreenPointKyc = $oEntityManager->getRepository('greenpoint_kyc');
        /** @var LoggerInterface $oLogger */
        $oLogger       = $this->getContainer()->get('monolog.logger.console');
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $aStatusToCheck           = [
            \clients_status::TO_BE_CHECKED,
            \clients_status::COMPLETENESS_REPLY,
            \clients_status::MODIFICATION
        ];
        $attachmentTypeToValidate = [
            AttachmentType::CNI_PASSPORTE,
            AttachmentType::JUSTIFICATIF_DOMICILE,
            AttachmentType::ATTESTATION_HEBERGEMENT_TIERS,
            AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT,
            AttachmentType::CNI_PASSPORTE_DIRIGEANT,
            AttachmentType::RIB,
            AttachmentType::DELEGATION_POUVOIR,
            AttachmentType::KBIS,
            AttachmentType::JUSTIFICATIF_FISCAL,
        ];
        $groupedRequestIds        = [];
        $aClientsToCheck          = $oClients->selectLendersByLastStatus($aStatusToCheck);

        if (false === empty($aClientsToCheck)) {
            /** @var greenPoint $oGreenPoint */
            $oGreenPoint       = new greenPoint($this->getContainer()->getParameter('kernel.environment'));
            $clientRepo        = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
            $attachmentManager = $this->getContainer()->get('unilend.service.attachment_manager');
            $requests          = [];

            foreach ($aClientsToCheck as $clientId => $aClient) {

                /** @var Clients $client */
                $client      = $clientRepo->find($clientId);
                $attachments = $client->getAttachments();
                foreach ($attachments as $attachment) {
                    if (false === in_array($attachment->getType()->getId(), $attachmentTypeToValidate)) {
                        continue;
                    }
                    $greenPointAttachment = $attachment->getGreenpointAttachment();
                    if (null === $greenPointAttachment) {
                        $greenPointAttachment = new GreenpointAttachment();
                        $greenPointAttachment->setIdAttachment($attachment);
                        $entityManager->persist($greenPointAttachment);
                    }
                    if (null !== $greenPointAttachment->getValidationStatus()) {
                        continue;
                    }

                    $sFullPath = realpath($attachmentManager->getFullPath($attachment));

                    if (false == file_exists($sFullPath)) {
                        $oLogger->error('Attachment file not found (ID ' . $attachment->getId() . ')', ['class' => __CLASS__, 'function' => __FUNCTION__]);
                        continue;
                    }
                    try {
                        switch ($attachment->getType()->getId()) {
                            case AttachmentType::CNI_PASSPORTE:
                            case AttachmentType::CNI_PASSPORTE_VERSO:
                            case AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT:
                            case AttachmentType::CNI_PASSPORTE_DIRIGEANT:
                                $type = greenPoint::GP_REQUEST_TYPE_ID;
                                break;
                            case AttachmentType::RIB:
                                $type = greenPoint::GP_REQUEST_TYPE_IBAN;
                                break;
                            case AttachmentType::JUSTIFICATIF_DOMICILE:
                            case AttachmentType::ATTESTATION_HEBERGEMENT_TIERS:
                                $type = greenPoint::GP_REQUEST_TYPE_ADDRESS;
                                break;
                            default :
                                continue 2;
                        }
                        $greenPointData = $this->getGreenPointData($clientId, $attachment->getId(), $sFullPath, $aClient, $type);
                        $requestId      = $oGreenPoint->send($greenPointData, $type, false);
                        if (is_int($requestId)) {
                            $requests[$requestId] = $greenPointAttachment;
                        }

                    } catch (\Exception $oException) {
                        $oLogger->error(
                            'Greenpoint was unable to process data (client ' . $clientId . ') - Message: ' . $oException->getMessage() . ' - Code: ' . $oException->getCode(),
                            ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $clientId]
                        );
                        continue 2;
                    }
                }
                if (false === empty($groupedRequestIds)) {
                    $response = $oGreenPoint->sendRequests();
                    $this->processGreenPointResponse($clientId, $response, $requests);
                    unset($aResult, $aQueryID);
                    greenPointStatus::addCustomer($clientId, $oGreenPoint, $oGreenPointKyc);
                }
            }
        }
    }

    /**
     * @param int    $clientId
     * @param int    $iAttachmentId
     * @param string $sPath
     * @param array  $aClient
     * @param string $sType
     *
     * @return array
     */
    private function getGreenPointData($clientId, $iAttachmentId, $sPath, array $aClient, $sType)
    {
        $aData = [
            'files'    => new \CURLFile($sPath),
            'dossier'  => $clientId,
            'document' => $iAttachmentId,
            'detail'   => 1,
            'nom'      => $this->getFamilyNames($aClient['nom'], $aClient['nom_usage']),
            'prenom'   => $aClient['prenom']
        ];

        switch ($sType) {
            case 'idcontrol':
                $aData['date_naissance'] = $aClient['naissance'];
                break;
            case 'ibanflash':
                $aData['iban'] = $aClient['iban'];
                $aData['bic']  = $aClient['bic'];
                break;
            case 'addresscontrol':
                $aData['adresse']     = $aClient['adresse_fiscal'];
                $aData['code_postal'] = $aClient['cp_fiscal'];
                $aData['ville']       = $aClient['ville_fiscal'];
                $aData['pays']        = strtoupper($aClient['fr']);
                break;
            default:
                break;
        }

        return $aData;
    }

    /**
     * @param string $sFamilyName
     * @param string $sUseName
     *
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
     * @param int   $clientId
     * @param array $response
     * @param array $requests
     */
    private function processGreenPointResponse($clientId, array $response, array $requests)
    {
        /**
         * @var  int                  $requestId
         * @var  GreenpointAttachment $greenPointAttachment
         */
        foreach ($requests as $requestId => $greenPointAttachment) {
            if (false === isset($response[$requestId])) {
                continue;
            }
            if (null === $greenPointAttachment) {
                continue;
            }
            $attachment = $greenPointAttachment->getIdAttachment();
            if (null === $attachment) {
                continue;
            }
            $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

            $response = json_decode($response[$requestId]['RESPONSE'], true);

            if (isset($response['resource']) && is_array($response['resource'])) {
                $greenPointData = greenPointStatus::getGreenPointData($response['resource'], $attachment->getType()->getId(), $attachment->getId(), $clientId, $response['code']);
            } else {
                $greenPointData = greenPointStatus::getGreenPointData([], $attachment->getType()->getId(), $attachment->getId(), $clientId, $response['code']);
            }

            $greenPointAttachment->setValidationCode($greenPointData['greenpoint_attachment']['validation_code']);
            $greenPointAttachment->setValidationStatus($greenPointData['greenpoint_attachment']['validation_status']);
            $greenPointAttachment->setValidationStatusLabel($greenPointData['greenpoint_attachment']['validation_status_label']);
            $entityManager->flush($greenPointAttachment);

            $greenPointAttachmentDetails = new GreenpointAttachmentDetail();
            $greenPointAttachmentDetails->setIdGreenpointAttachment($greenPointAttachment)
                                        ->setDocumentType($greenPointData['greenpoint_attachment_detail']['document_type'])
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
            $entityManager->persist($greenPointAttachmentDetails);
            $entityManager->flush($greenPointAttachmentDetails);
        }
    }

}
