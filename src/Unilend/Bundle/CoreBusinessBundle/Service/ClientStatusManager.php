<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, Attachment, AttachmentType, Clients, ClientsStatus, ClientsStatusHistory, Companies, NationalitesV2, PaysV2, Users, WalletType
};

class ClientStatusManager
{
    /** @var NotificationManager */
    private $notificationManager;
    /** @var AutoBidSettingsManager */
    private $autoBidSettingsManager;
    /** @var EntityManager */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param NotificationManager    $notificationManager
     * @param AutoBidSettingsManager $autoBidSettingsManager
     * @param EntityManager          $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(
        NotificationManager $notificationManager,
        AutoBidSettingsManager $autoBidSettingsManager,
        EntityManager $entityManager,
        LoggerInterface $logger
    )
    {
        $this->notificationManager    = $notificationManager;
        $this->autoBidSettingsManager = $autoBidSettingsManager;
        $this->entityManager          = $entityManager;
        $this->logger                 = $logger;
    }

    /**
     * @param \clients $client
     * @param int      $userId
     * @param string   $comment
     *
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function closeLenderAccount(\clients $client, $userId, $comment): void
    {
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->id_client, WalletType::LENDER);
        if ($wallet->getAvailableBalance() > 0) {
            throw new \Exception('The client still has money in his account');
        }

        $this->notificationManager->deactivateAllNotificationSettings($client);
        $this->autoBidSettingsManager->off($wallet->getIdClient());

        $client->changePassword($client->email, mt_rand());

        $this->addClientStatus($client, $userId, ClientsStatus::STATUS_CLOSED_DEFINITELY, $comment);
    }

    /**
     * @param Clients        $clientBeingModified
     * @param Companies|null $companyBeingModified
     * @param bool           $bankAccountModification
     * @param bool           $addressModification
     * @param Attachment[]   $newAttachments
     */
    public function changeClientStatusTriggeredByClientAction(
        Clients $clientBeingModified,
        ?Companies $companyBeingModified = null,
        bool $bankAccountModification = false,
        bool $addressModification = false,
        array $newAttachments = []
    ): void
    {
        $newClientStatus  = null;
        $companyChangeSet = $this->getModifiedFields($companyBeingModified);

        if (false === $clientBeingModified->isLender()) {
            return;
        }

        if (
            ClientsStatus::STATUS_SUSPENDED === $clientBeingModified->getIdClientStatusHistory()->getIdStatus()->getId()
            || $clientBeingModified->getUsPerson()
            || NationalitesV2::NATIONALITY_OTHER === $clientBeingModified->getIdNationalite()
        ) {
            $this->addClientStatus($clientBeingModified, Users::USER_ID_FRONT, ClientsStatus::STATUS_SUSPENDED, $this->formatArrayToUnorderedList(array_keys($companyChangeSet)));
            return;
        }

        $newClientStatus = $this->checkAttachmentsAndGetStatus($clientBeingModified, $newAttachments);
        if (ClientsStatus::STATUS_SUSPENDED === $newClientStatus) {
            $this->addClientStatus($clientBeingModified, Users::USER_ID_FRONT, ClientsStatus::STATUS_SUSPENDED, $this->formatArrayToUnorderedList(array_keys($companyChangeSet)));
            return;
        }

        $clientDataCheck = $this->checkClientDataAndGetStatus($clientBeingModified);
        if (ClientsStatus::STATUS_SUSPENDED == $clientDataCheck) {
            $this->addClientStatus($clientBeingModified, Users::USER_ID_FRONT, ClientsStatus::STATUS_SUSPENDED, $this->formatArrayToUnorderedList(array_keys($companyChangeSet)));
            return;
        }
        $newClientStatus = null === $newClientStatus ? $clientDataCheck : $newClientStatus;

        $companyDataCheck = $this->checkCompanyDataAndGetStatus($clientBeingModified, $companyBeingModified);
        if (ClientsStatus::STATUS_SUSPENDED === $companyDataCheck) {
            $this->addClientStatus($clientBeingModified, Users::USER_ID_FRONT, ClientsStatus::STATUS_SUSPENDED, $this->formatArrayToUnorderedList(array_keys($companyChangeSet)));
            return;
        }
        $newClientStatus = null === $newClientStatus ? $companyDataCheck : $newClientStatus;

        $addressCheck = $this->checkClientAddressAndGetStatus($clientBeingModified, $addressModification);
        if (ClientsStatus::STATUS_SUSPENDED === $addressCheck) {
            $this->addClientStatus($clientBeingModified, Users::USER_ID_FRONT, ClientsStatus::STATUS_SUSPENDED, $this->formatArrayToUnorderedList(array_keys($companyChangeSet)));
            return;
        }
        $newClientStatus = null === $newClientStatus ? $addressCheck : $newClientStatus;

        if (
            $bankAccountModification
            && $this->entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($clientBeingModified)
        ) {
            $this->addClientStatus($clientBeingModified, Users::USER_ID_FRONT, ClientsStatus::STATUS_MODIFICATION, $this->formatArrayToUnorderedList(array_keys($companyChangeSet)));
            return;
        }

        if (null === $newClientStatus) {
            return;
        }

        switch ($clientBeingModified->getIdClientStatusHistory()->getIdStatus()->getId()) {
            case ClientsStatus::STATUS_COMPLETENESS:
            case ClientsStatus::STATUS_COMPLETENESS_REMINDER:
            case ClientsStatus::STATUS_COMPLETENESS_REPLY:
                $status = ClientsStatus::STATUS_COMPLETENESS_REPLY;
                break;
            case ClientsStatus::STATUS_VALIDATED:
            case ClientsStatus::STATUS_MODIFICATION:
                $status = ClientsStatus::STATUS_MODIFICATION;
                break;
            case ClientsStatus::STATUS_CREATION:
                $status = ClientsStatus::STATUS_CREATION;
                break;
            case ClientsStatus::STATUS_TO_BE_CHECKED:
            default:
                $status = ClientsStatus::STATUS_TO_BE_CHECKED;
                break;
        }

        $this->addClientStatus($clientBeingModified, Users::USER_ID_FRONT, $status, $this->formatArrayToUnorderedList(array_keys($companyChangeSet)));
    }

    /**
     * @param Clients|\clients $client
     * @param int              $userId
     * @param int              $status
     * @param string|null      $comment
     * @param int|null         $reminder
     */
    public function addClientStatus($client, int $userId, int $status, ?string $comment = null, ?int $reminder = null): void
    {
        if ($client instanceof \clients) {
            $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        if (
            $client->getIdClientStatusHistory()
            && $status === $client->getIdClientStatusHistory()->getIdStatus()->getId()
            && empty($comment)
            && empty($reminder)
        ) {
            return;
        }

        $this->entityManager->getConnection()->beginTransaction();

        try {
            $clientStatus = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatus')->find($status);
            $user         = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($userId);

            $clientStatusHistory = new ClientsStatusHistory();
            $clientStatusHistory
                ->setIdClient($client)
                ->setIdStatus($clientStatus)
                ->setIdUser($user)
                ->setContent($comment)
                ->setNumeroRelance($reminder);

            $this->entityManager->persist($clientStatusHistory);
            $this->entityManager->flush($clientStatusHistory);

            $client->setIdClientStatusHistory($clientStatusHistory);
            $this->entityManager->flush($client);

            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->logger->error(
                'Error while changing client status. Message: ' . $exception->getMessage(),
                ['id_client' => $client->getIdClient(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );

            try {
                $this->entityManager->getConnection()->rollBack();
            } catch (ConnectionException $rollBackException) {
                $this->logger->error(
                    'Error while trying to rollback the transaction client status update. Message: ' . $rollBackException->getMessage(),
                    ['id_client' => $client->getIdClient(), 'file' => $rollBackException->getFile(), 'line' => $rollBackException->getLine()]
                );
            }
        }
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    public function hasBeenValidatedAtLeastOnce(Clients $client): bool
    {
        $clientStatusHistory = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory');
        $previousValidation  = $clientStatusHistory->getFirstClientValidation($client);

        return null !== $previousValidation;
    }

    /**
     * @param Clients $client
     * @param array   $newAttachments
     *
     * @return int|null
     */
    private function checkAttachmentsAndGetStatus(Clients $client, array $newAttachments): ?int
    {
        $newClientStatus = null;
        $attachmentTypes = $this->getAttachmentTypes($newAttachments);

        $hasUploadedIdentityAttachments = count(array_intersect([AttachmentType::CNI_PASSPORTE, AttachmentType::CNI_PASSPORTE_VERSO], $attachmentTypes)) > 0;
        if ($hasUploadedIdentityAttachments && $client->isNaturalPerson()) {
            $attachmentTypes = ClientsStatus::STATUS_SUSPENDED;
        }

        if (count(array_intersect([
                AttachmentType::JUSTIFICATIF_DOMICILE,
                AttachmentType::ATTESTATION_HEBERGEMENT_TIERS,
                AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT,
                AttachmentType::RIB,
                AttachmentType::KBIS
            ], $attachmentTypes)) > 0
        ) {
            $newClientStatus = ClientsStatus::STATUS_MODIFICATION;
        }

        return $newClientStatus;
    }

    /**
     * @param Clients $client
     *
     * @return int|null
     */
    private function checkClientDataAndGetStatus(Clients $client): ?int
    {
        $naturalPersonClientFields = [
            'civilite',
            'prenom',
            'nomUsage',
            'idNationalite'
        ];
        $legalEntityClientFields   = [
            'civilite',
            'nom',
            'prenom',
            'fonction',
        ];
        $newClientStatus           = null;
        $clientChangeSet           = $this->getModifiedFields($client);

        if (count($clientChangeSet) > 0) {
            $clientModifiedFields = array_keys($clientChangeSet);
            if (
                $client->isNaturalPerson() && count(array_intersect($clientModifiedFields, $naturalPersonClientFields)) > 0
                || false === $client->isNaturalPerson() && count(array_intersect($clientModifiedFields, $legalEntityClientFields)) > 0
            ) {
                $newClientStatus = ClientsStatus::STATUS_MODIFICATION;
            }
        }

        return $newClientStatus;
    }

    /**
     * @param Clients        $client
     * @param Companies|null $company
     *
     * @return int|null
     */
    private function checkCompanyDataAndGetStatus(Clients $client, ?Companies $company): ?int
    {
        $companyFields    = [
            'name',
            'forme',
            'capital',
            'statusClient',
            'statusConseilExterneEntreprise',
            'preciserConseilExterneEntreprise',
            'nomDirigeant',
            'prenomDirigeant',
            'fonctionDirigeant',
            'phoneDirigeant'
        ];
        $newClientStatus  = null;
        $companyChangeSet = $this->getModifiedFields($company);

        if (count($companyChangeSet) > 0) {
            if (
                false === $client->isNaturalPerson()
                && count(array_intersect(array_keys($companyChangeSet), $companyFields)) > 0
            ) {
                $newClientStatus = ClientsStatus::STATUS_MODIFICATION;
            } elseif ($client->isNaturalPerson()) {
                $this->logger->error('Could not check modified company fields of natural person', [
                    'id_client' => $client->getIdClient(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__
                ]);
            }
        }

        return $newClientStatus;
    }

    /**
     * @param Clients $client
     * @param bool    $addressModification
     *
     * @return int|null
     */
    private function checkClientAddressAndGetStatus(Clients $client, bool $addressModification): ?int
    {
        $pendingMainAddress   = null;
        $pendingPostalAddress = null;
        $newClientStatus      = null;

        if ($addressModification) {
            try {
                if ($client->isNaturalPerson()) {
                    $clientAddressRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress');

                    $pendingMainAddress   = $clientAddressRepository->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
                    $pendingPostalAddress = $clientAddressRepository->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_POSTAL_ADDRESS);
                } else {
                    $company                  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
                    $companyAddressRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress');

                    $pendingMainAddress   = $companyAddressRepository->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
                    $pendingPostalAddress = $companyAddressRepository->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_POSTAL_ADDRESS);
                }

                if ($pendingMainAddress && in_array($pendingMainAddress->getIdCountry()->getIdPays(), [PaysV2::COUNTRY_USA, PaysV2::COUNTRY_ERITREA])) {
                    $newClientStatus = ClientsStatus::STATUS_SUSPENDED;
                }

                if ($pendingMainAddress || $pendingPostalAddress) {
                    $newClientStatus = ClientsStatus::STATUS_MODIFICATION;
                }
            } catch (\Exception $exception) {
                $this->logger->error('Could not get address information. Error: ' . $exception->getMessage(), [
                    'id_client' => $client->getIdClient(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine()
                ]);
            }
        }

        return $newClientStatus;
    }

    /**
     * @param $entityObject
     *
     * @return array
     */
    private function getModifiedFields($entityObject): array
    {
        if (null === $entityObject) {
            return [];
        }
        $classMetaData = $this->entityManager->getClassMetadata(get_class($entityObject));
        $unitOfWork    = $this->entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSet($classMetaData, $entityObject);

        $entityChangeSet = $unitOfWork->getEntityChangeSet($entityObject);
        unset($entityChangeSet['updated']);

        return $entityChangeSet;
    }

    /**
     * @param Attachment[] $attachments
     *
     * @return array
     */
    private function getAttachmentTypes(array $attachments): array
    {
        $attachmentTypes = [];

        foreach ($attachments as $attachment) {
            $attachmentTypes[$attachment->getType()->getId()] = $attachment->getType()->getId();
        }

        return $attachmentTypes;
    }

    /**
     * @param array|null $modifications
     *
     * @return string|null
     */
    private function formatArrayToUnorderedList(?array $modifications): ?string
    {
        if (empty($modifications)) {
            return null;
        }

        $list = '<ul>';

        foreach ($modifications as $modification) {
            $list .= '<li>' . $modification . '</li>';
        }

        $list .= '</ul>';

        return $list;
    }
}
