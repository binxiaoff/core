<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Attachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachmentDetail;
use Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2;
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
        $entityManagerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \greenpoint_kyc $greenPointKyc */
        $greenPointKyc = $entityManagerSimulator->getRepository('greenpoint_kyc');
        /** @var LoggerInterface $logger */
        $logger        = $this->getContainer()->get('monolog.logger.console');
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $statusToCheck            = [
            ClientsStatus::TO_BE_CHECKED,
            ClientsStatus::COMPLETENESS_REPLY,
            ClientsStatus::MODIFICATION
        ];
        $attachmentTypeToValidate = [
            AttachmentType::CNI_PASSPORTE,
            AttachmentType::JUSTIFICATIF_DOMICILE,
            AttachmentType::ATTESTATION_HEBERGEMENT_TIERS,
            AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT,
            AttachmentType::CNI_PASSPORTE_DIRIGEANT,
            AttachmentType::RIB,
        ];
        $clients                  = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->getLendersInStatus($statusToCheck);

        if (false === empty($clients)) {
            /** @var greenPoint $oGreenPoint */
            $greenPoint        = new greenPoint($this->getContainer()->getParameter('kernel.environment'));
            $attachmentManager = $this->getContainer()->get('unilend.service.attachment_manager');
            $requests          = [];

            foreach ($clients as $client) {
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

                    if (false == file_exists(realpath($attachmentManager->getFullPath($attachment)))) {
                        $logger->error('Attachment file not found (ID ' . $attachment->getId() . ')', ['class' => __CLASS__, 'function' => __FUNCTION__]);
                        continue;
                    }
                    try {
                        switch ($attachment->getType()->getId()) {
                            case AttachmentType::CNI_PASSPORTE:
                            case AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT:
                            case AttachmentType::CNI_PASSPORTE_DIRIGEANT:
                                $type = greenPoint::REQUEST_TYPE_ID;
                                break;
                            case AttachmentType::RIB:
                                $type = greenPoint::REQUEST_TYPE_IBAN;
                                break;
                            case AttachmentType::JUSTIFICATIF_DOMICILE:
                            case AttachmentType::ATTESTATION_HEBERGEMENT_TIERS:
                                $type = greenPoint::REQUEST_TYPE_ADDRESS;
                                break;
                            default :
                                continue 2;
                        }
                        $greenPointData = $this->getGreenPointData($client, $attachment, $type);
                        $requestId      = $greenPoint->send($greenPointData, $type, false);
                        if (is_int($requestId)) {
                            $requests[$requestId] = $greenPointAttachment;
                        }
                    } catch (\Exception $exception) {
                        $logger->error(
                            'Greenpoint was unable to process data (client ' . $client->getIdClient() . ') - Message: ' . $exception->getMessage() . ' - Code: ' . $exception->getCode(),
                            ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->getIdClient()]
                        );
                        continue 2;
                    }
                }
                if (false === empty($requests)) {
                    $response = $greenPoint->sendRequests();
                    $this->processGreenPointResponse($client->getIdClient(), $response, $requests);
                    unset($response, $requests);
                    greenPointStatus::addCustomer($client->getIdClient(), $greenPoint, $greenPointKyc);
                }
            }
        }
    }

    /**
     * @param Clients    $client
     * @param Attachment $attachment
     * @param string     $type
     *
     * @return array
     */
    private function getGreenPointData(Clients $client, Attachment $attachment, $type)
    {
        $attachmentManager = $this->getContainer()->get('unilend.service.attachment_manager');
        $entityManager     = $this->getContainer()->get('doctrine.orm.entity_manager');

        $data = [
            'files'    => new \CURLFile(realpath($attachmentManager->getFullPath($attachment))),
            'dossier'  => $client->getIdClient(),
            'document' => $attachment->getId(),
            'detail'   => 1,
            'nom'      => $this->getFamilyNames($client->getNom(), $client->getNomUsage()),
            'prenom'   => $client->getPrenom()
        ];

        switch ($type) {
            case greenPoint::REQUEST_TYPE_ID:
                $data['date_naissance'] = $client->getNaissance()->format('d/m/Y');
                break;
            case greenPoint::REQUEST_TYPE_IBAN:
                $bankAccount = $attachment->getBankAccount();
                if ($bankAccount) {
                    $data['iban'] = $bankAccount->getIban();
                    $data['bic']  = $bankAccount->getBic();
                }
                break;
            case greenPoint::REQUEST_TYPE_ADDRESS:
                if (in_array($client->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) {
                    $clientAddress       = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $client]);
                    $data['adresse']     = $clientAddress->getAdresseFiscal();
                    $data['code_postal'] = $clientAddress->getCpFiscal();
                    $data['ville']       = $clientAddress->getVilleFiscal();
                    $countryId           = $clientAddress->getIdPaysFiscal();
                } else {
                    $company             = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client->getIdClient()]);
                    $data['adresse']     = $company->getAdresse1() . ' ' . $company->getAdresse2();
                    $data['code_postal'] = $company->getZip();
                    $data['ville']       = $company->getCity();
                    $countryId           = $company->getIdPays();
                }
                $country = $entityManager->getRepository('UnilendCoreBusinessBundle:PaysV2')->find($countryId);
                if (null === $country) {
                    $country = $entityManager->getRepository('UnilendCoreBusinessBundle:PaysV2')->find(PaysV2::COUNTRY_FRANCE);
                }
                $data['pays'] = strtoupper($country->getFr());
                break;
            default:
                break;
        }

        return $data;
    }

    /**
     * @param string $familyName
     * @param string $usedName
     *
     * @return string
     */
    private function getFamilyNames($familyName, $usedName)
    {
        $allowedNames = $familyName;
        if (false === empty($usedName)) {
            $allowedNames .= '|' . $usedName;
        }
        return $allowedNames;
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
                $greenPointData = greenPointStatus::getGreenPointData($response['resource'], $attachment->getType()->getId(), $response['code']);
            } else {
                $greenPointData = greenPointStatus::getGreenPointData([], $attachment->getType()->getId(), $response['code']);
            }

            $greenPointAttachment->setValidationCode($greenPointData['greenpoint_attachment']['validation_code'])
                                 ->setValidationStatus($greenPointData['greenpoint_attachment']['validation_status'])
                                 ->setValidationStatusLabel($greenPointData['greenpoint_attachment']['validation_status_label']);
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
