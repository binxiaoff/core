<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, Attachment, AttachmentType, ClientAddressAttachment, Clients, ClientsStatus, GreenpointAttachment, GreenpointAttachmentDetail
};
use Unilend\librairies\greenPoint\{
    greenPoint, greenPointStatus
};

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
        $logger        = $this->getContainer()->get('monolog.logger.console');
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $statusToCheck            = [
            ClientsStatus::STATUS_TO_BE_CHECKED,
            ClientsStatus::STATUS_COMPLETENESS_REPLY,
            ClientsStatus::STATUS_MODIFICATION,
            ClientsStatus::STATUS_SUSPENDED
        ];
        $attachmentTypeToValidate = [
            AttachmentType::CNI_PASSPORTE,
            AttachmentType::JUSTIFICATIF_DOMICILE,
            AttachmentType::ATTESTATION_HEBERGEMENT_TIERS,
            AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT,
            AttachmentType::CNI_PASSPORTE_DIRIGEANT,
            AttachmentType::RIB,
        ];

        /** @var Clients[] $clients */
        $clients = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->getLendersInStatus($statusToCheck, true);

        if (false === empty($clients)) {
            /** @var greenPoint $oGreenPoint */
            $greenPoint        = new greenPoint($this->getContainer()->getParameter('kernel.environment'));
            $attachmentManager = $this->getContainer()->get('unilend.service.attachment_manager');
            $requests          = [];

            $clientStatusHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory');

            foreach ($clients as $client) {
                $attachments = $client->getAttachments();
                foreach ($attachments as $attachment) {
                    if (false === in_array($attachment->getType()->getId(), $attachmentTypeToValidate)) {
                        continue;
                    }

                    try {
                        $validationCount = $clientStatusHistoryRepository->getValidationsCount($client->getIdClient());
                    } catch (ORMException $exception) {
                        $validationCount = 0;
                        $logger->warning(
                            'Could not check the validation count on id_client: ' . $client->getIdClient() . ' - Error: ' . $exception->getMessage(),
                            ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                        );
                    }

                    if ($validationCount > 0 && false === $attachmentManager->isModifiedAttachment($attachment)) {
                        continue;
                    }

                    if (false == file_exists(realpath($attachmentManager->getFullPath($attachment)))) {
                        $logger->error('Attachment file not found (ID ' . $attachment->getId() . ')', ['class' => __CLASS__, 'function' => __FUNCTION__]);
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
                            $entityManager->flush($greenPointAttachment);
                            $requests[$requestId] = $greenPointAttachment;
                        } else {
                            $entityManager->detach($greenPointAttachment);
                        }
                    } catch (\Exception $exception) {
                        $entityManager->detach($greenPointAttachment);
                        $logger->error(
                            'Greenpoint was unable to process data (client ' . $client->getIdClient() . ') - Message: ' . $exception->getMessage() . ' - Code: ' . $exception->getCode(),
                            ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->getIdClient()]
                        );
                        continue 2;
                    }
                }
                if (false === empty($requests)) {
                    $response = $greenPoint->sendRequests();
                    try {
                        $this->processGreenPointResponse($response, $requests);
                        unset($response, $requests);
                        greenPointStatus::addCustomer($client->getIdClient(), $greenPoint, $greenPointKyc);
                    } catch (\Exception $exception) {
                        $logger->error('An exception occurred during process of Greenpoint response.', [
                            'file'      => $exception->getFile(),
                            'line'      => $exception->getLine(),
                            'class'     => __CLASS__,
                            'function'  => __FUNCTION__,
                            'id_client' => $client->getIdClient()
                        ]);
                    }
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
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    private function getGreenPointData(Clients $client, Attachment $attachment, string $type): array
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
                if ($client->isNaturalPerson()) {
                    $address = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
                } else {
                    $company = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
                    $address = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
                }

                if (null === $address) {
                    throw new \Exception('Client/Company has no last modified address');
                }

                $data['adresse']     = $address->getAddress();
                $data['code_postal'] = $address->getZip();
                $data['ville']       = $address->getCity();
                $data['pays']        = strtoupper($address->getIdCountry()->getFr());
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
     * @param array $response
     * @param array $requests
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    private function processGreenPointResponse(array $response, array $requests): void
    {
        $bankAccountManager = $this->getContainer()->get('unilend.service.bank_account_manager');
        $addressManager     = $this->getContainer()->get('unilend.service.address_manager');
        $logger             = $this->getContainer()->get('monolog.logger.console');
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

            $responseData = json_decode($response[$requestId]['RESPONSE'], true);

            if (isset($responseData['resource']) && is_array($responseData['resource'])) {
                $greenPointData = greenPointStatus::getGreenPointData($responseData['resource'], $attachment->getType()->getId(), $responseData['code']);
            } else {
                $greenPointData = greenPointStatus::getGreenPointData([], $attachment->getType()->getId(), $responseData['code']);
            }

            $greenPointAttachment->setValidationCode($greenPointData['greenpoint_attachment']['validation_code'])
                ->setValidationStatus($greenPointData['greenpoint_attachment']['validation_status'])
                ->setValidationStatusLabel($greenPointData['greenpoint_attachment']['validation_status_label']);
            $entityManager->flush($greenPointAttachment);

            if (AttachmentType::RIB === $attachment->getType()->getId() && GreenpointAttachment::STATUS_VALIDATION_VALID === $greenPointAttachment->getValidationStatus()) {
                $bankAccountToValidate = $attachment->getBankAccount();
                if (null === $bankAccountToValidate) {
                    $logger->error('Lender has no associated bank account - Client: ' . $attachment->getClient()->getIdClient(), ['methode' => __METHOD__]);
                } else {
                    $bankAccountManager->validateBankAccount($bankAccountToValidate);
                }
            }

            if (AttachmentType::JUSTIFICATIF_DOMICILE === $attachment->getType()->getId() && GreenpointAttachment::STATUS_VALIDATION_VALID === $greenPointAttachment->getValidationStatus()) {
                /** @var ClientAddressAttachment $addressAttachment */
                $addressAttachment = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddressAttachment')->findOneBy(['idAttachment' => $attachment]);
                if (null === $addressAttachment || null === $addressAttachment->getIdClientAddress()) {
                    $logger->error('Lender housing certificate has no associated address - Client: ' . $attachment->getClient()->getIdClient(), ['methode' => __METHOD__]);
                } else {
                    $addressManager->validateLenderAddress($addressAttachment->getIdClientAddress());
                }
            }

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
