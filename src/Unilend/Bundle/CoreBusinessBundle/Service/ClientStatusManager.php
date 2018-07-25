<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, Attachment, AttachmentType, Clients, ClientsStatus, ClientsStatusHistory, Companies, CompanyAddress, NationalitesV2, PaysV2, Users, WalletType
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
     * @param Clients        $modifiedClient
     * @param Clients        $unattachedClient
     * @param Companies|null $modifiedCompany
     * @param Companies|null $unattachedCompany
     * @param bool           $isAddressModified
     * @param bool           $isBankAccountModified
     * @param Attachment[]   $newAttachments
     */
    public function changeClientStatusTriggeredByClientAction(
        Clients $modifiedClient,
        Clients $unattachedClient,
        ?Companies $modifiedCompany = null,
        ?Companies $unattachedCompany = null,
        bool $isAddressModified = false,
        bool $isBankAccountModified = false,
        array $newAttachments = []
    ): void
    {
        if (false === $modifiedClient->isLender()) {
            throw new \InvalidArgumentException('Could not update client status. This method expects the client to be a lender');
        }

        $companyChangeSet = $this->getCompanyChangeSet($modifiedCompany, $unattachedCompany);
        $clientChangeSet  = $this->getClientChangeSet($modifiedClient, $unattachedClient);

        if (false === $this->clientStatusNeedsToBeChanged($modifiedClient, $clientChangeSet, $companyChangeSet, $newAttachments, $isAddressModified, $isBankAccountModified)) {
            return;
        }

        if ($this->clientNeedsToBeSuspended($modifiedClient, $newAttachments, $isAddressModified)) {
            $this->addClientStatus($modifiedClient, Users::USER_ID_FRONT, ClientsStatus::STATUS_SUSPENDED, $this->formatArrayToUnorderedList($companyChangeSet));
            return;
        }

        switch ($modifiedClient->getIdClientStatusHistory()->getIdStatus()->getId()) {
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

        $this->addClientStatus($modifiedClient, Users::USER_ID_FRONT, $status, $this->formatArrayToUnorderedList($companyChangeSet));
    }

    /**
     * @param Clients $modifiedClient
     * @param array   $clientChangeSet
     * @param array   $companyChangeSet
     * @param array   $newAttachments
     * @param bool    $isAddressModified
     * @param bool    $isBankAccountModified
     *
     * @return bool
     */
    private function clientStatusNeedsToBeChanged(
        Clients $modifiedClient,
        array $clientChangeSet,
        array $companyChangeSet,
        array $newAttachments,
        bool $isAddressModified,
        bool $isBankAccountModified
    ): bool
    {
        if (ClientsStatus::STATUS_SUSPENDED === $modifiedClient->getIdClientStatusHistory()->getIdStatus()->getId()) {
            return false;
        }

        $needsTobeChangedByAttachments = $this->needsToBeChangedByAttachments($newAttachments);
        $needsTobeChangedByClientData  = $this->needsToBeChangedByClientData($modifiedClient, $clientChangeSet);
        $needsTobeChangedByCompanyData = $this->needsToBeChangedByCompanyData($companyChangeSet);
        $needsTobeChangedByAddress     = $this->needsToBeChangedByMainAddress($modifiedClient, $isAddressModified);

        return $needsTobeChangedByAttachments || $needsTobeChangedByClientData || $needsTobeChangedByCompanyData || $needsTobeChangedByAddress || $isBankAccountModified;
    }

    /**
     * @param Clients    $modifiedClient
     * @param array      $newAttachments
     * @param array|null $companyChangeSet
     * @param bool       $isAddressModified
     *
     * @return bool
     */
    private function clientNeedsToBeSuspended(Clients $modifiedClient, array $newAttachments, bool $isAddressModified): bool
    {
        $needsTobeChangedByAttachments = $this->needsToBeSuspendedByAttachments($newAttachments);
        $needsTobeChangedByClientData  = $this->needsToBeSuspendedByClientData($modifiedClient);
        $needsTobeChangedByAddress     = $this->needsToBeSuspendedByMainAddress($modifiedClient, $isAddressModified);

        return $needsTobeChangedByAttachments || $needsTobeChangedByClientData || $needsTobeChangedByAddress;
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
     * @param array $newAttachments
     *
     * @return bool
     */
    private function needsToBeChangedByAttachments(array $newAttachments): bool
    {
        if (0 < count($newAttachments)) {
            return true;
        }

        return false;
    }

    /**
     * @param array $newAttachments
     *
     * @return bool
     */
    private function needsToBeSuspendedByAttachments(array $newAttachments): bool
    {
        $suspend                 = false;
        $attachmentTypes         = $this->getAttachmentTypes($newAttachments);
        $identityAttachmentTypes = [AttachmentType::CNI_PASSPORTE, AttachmentType::CNI_PASSPORTE_DIRIGEANT, AttachmentType::CNI_PASSPORTE_VERSO];

        if (0 < count(array_intersect($identityAttachmentTypes, $attachmentTypes))) {
            $suspend = true;
        }

        return $suspend;
    }

    /**
     * @param Clients $client
     * @param array   $clientChangeSet
     *
     * @return bool
     */
    private function needsToBeChangedByClientData(Clients $client, array $clientChangeSet): bool
    {
        if ($client->isNaturalPerson()) {
            $clientModifiableFields = [
                'civilite',
                'prenom',
                'idNationalite'
            ];
        } else {
            $clientModifiableFields = [
                'civilite',
                'nom',
                'prenom',
                'fonction',
            ];
        }

        if (0 < count(array_intersect($clientChangeSet, $clientModifiableFields))) {
            return true;
        }

        return false;
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    private function needsToBeSuspendedByClientData(Clients $client): bool
    {
        if ($client->getUsPerson() || NationalitesV2::NATIONALITY_OTHER === $client->getIdNationalite()) {
            return true;
        }

        return false;
    }

    /**
     * @param array $companyChangeSet
     *
     * @return bool
     */
    private function needsToBeChangedByCompanyData(array $companyChangeSet): bool
    {
        if (empty($companyChangeSet)) {
            return false;
        }

        $companyModifiableFields = [
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

        if (0 < count(array_intersect($companyChangeSet, $companyModifiableFields))) {
            return true;
        }

        return false;
    }

    /**
     * @param Clients $client
     * @param bool    $addressModification
     *
     * @return bool
     */
    private function needsToBeChangedByMainAddress(Clients $client, bool $addressModification): bool
    {
        if (false === $addressModification) {
            return false;
        }

        try {
            if ($this->getPendingMainAddress($client)) {
                return true;
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

        return false;
    }

    /**
     * @param Clients $client
     * @param bool    $addressModification
     *
     * @return bool
     */
    private function needsToBeSuspendedByMainAddress(Clients $client, bool $addressModification): bool
    {
        if (false === $addressModification) {
            return false;
        }

        try {
            $pendingMainAddress = $this->getPendingMainAddress($client);
            if ($pendingMainAddress & in_array($pendingMainAddress->getIdCountry()->getIdPays(), [PaysV2::COUNTRY_USA, PaysV2::COUNTRY_ERITREA])) {
                return true;
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

        return false;
    }

    /**
     * @param Clients $client
     *
     * @return null|CompanyAddress
     * @throws \Exception
     */
    private function getPendingMainAddress(Clients $client)
    {
        if ($client->isNaturalPerson()) {
            $clientAddressRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress');
            $pendingMainAddress      = $clientAddressRepository->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
        } else {
            $company = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
            if ($company) {
                $companyAddressRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress');
                $pendingMainAddress       = $companyAddressRepository->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
            } else {
                throw new \Exception('Lender is a legal entity but no attached company found.');
            }
        }

        return $pendingMainAddress;
    }

    /**
     * @param Companies|null $company
     * @param Companies|null $unattachedCompany
     *
     * @return array
     */
    public function getCompanyChangeSet(?Companies $company, ?Companies $unattachedCompany): array
    {
        try {
            $companyChangeSet = $this->getModifiedFields($unattachedCompany, $company);
        } catch (\Exception $exception) {
            $this->logger->error('Could not determine modified company fields. Error: ' . $exception->getMessage(), [
                'id_company' => $company->getIdCompany(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
                'file'       => $exception->getFile(),
                'line'       => $exception->getLine()
            ]);
        }

        return $companyChangeSet;
    }

    /**
     * @param Clients $client
     * @param Clients $unattachedClient
     *
     * @return array
     */
    public function getClientChangeSet(Clients $client, Clients $unattachedClient)
    {
        try {
            $clientChangeSet = $this->getModifiedFields($unattachedClient, $client);
        } catch (\Exception $exception) {
            $clientChangeSet = [];
            $this->logger->error('Could not determine modified client fields. Error: ' . $exception->getMessage(), [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);
        }

        return $clientChangeSet;
    }

    /**
     * @param $unattachedObject
     * @param $modifiedObject
     *
     * @return array
     * @throws \Exception
     */
    private function getModifiedFields($unattachedObject, $modifiedObject): array
    {
        if (null === $unattachedObject || null === $modifiedObject) {
            return [];
        }

        if (get_class($unattachedObject) !== get_class($modifiedObject)) {
            throw new \Exception('The objects to be compared are not of the same class');
        }

        $differences = [];
        $object      = new \ReflectionObject($unattachedObject);

        foreach ($object->getMethods() as $method) {
            if (
                substr($method->name, 0, 3) === 'get'
                && $method->invoke($unattachedObject) != $method->invoke($modifiedObject)
            ) {
                if ($method->name !== 'getUpdated') {
                    $differences[] = lcfirst(str_replace('get', '', $method->name));
                }
            }
        }

        return $differences;
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
