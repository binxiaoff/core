<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, Clients
};

class ClientDataHistoryManager
{
    /** @var EntityManager */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Clients $client
     *
     * @return array
     */
    public function getDataHistory(Clients $client): array
    {
        $history = [];

        $this->fillClientDataHistory($client, $history);
        $this->fillBankAccountHistory($client, $history);
        $this->fillAddressHistory($client, $history);
        $this->fillAttachmentHistory($client, $history);

        krsort($history);

        return $history;
    }

    /**
     * @param Clients $client
     * @param array   $history
     */
    private function fillClientDataHistory(Clients $client, array &$history): void
    {
        $clientData = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientDataHistory')->findBy(
            ['idClient' => $client],
            ['datePending' => 'ASC', 'id' => 'ASC']
        );

        foreach ($clientData as $dataHistory) {
            $history[$dataHistory->getDatePending()->getTimestamp()][] = [
                'type' => 'data',
                'date' => $dataHistory->getDatePending(),
                'name' => $dataHistory->getField(),
                'old'  => $dataHistory->getOldValue(),
                'new'  => $dataHistory->getNewValue(),
                'user' => $dataHistory->getIdUser()
            ];
        }
    }

    /**
     * @param Clients $client
     * @param array   $history
     */
    private function fillBankAccountHistory(Clients $client, array &$history): void
    {
        $previousBankAccount = null;
        $bankAccounts        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->findBy(
            ['idClient' => $client],
            ['datePending' => 'ASC', 'id' => 'ASC']
        );

        foreach ($bankAccounts as $bankAccount) {
            if (null !== $bankAccount->getDatePending()) {
                $history[$bankAccount->getDatePending()->getTimestamp()][] = [
                    'type' => 'bank_account',
                    'date' => $bankAccount->getDatePending(),
                    'name' => 'modification',
                    'old'  => $previousBankAccount,
                    'new'  => $bankAccount,
                    'user' => null
                ];

                $previousBankAccount = $bankAccount;
            }

            if (
                null !== $bankAccount->getDateValidated()
                && $bankAccount->getDatePending()->getTimestamp() !== $bankAccount->getDateValidated()->getTimestamp()
            ) {
                $history[$bankAccount->getDateValidated()->getTimestamp()][] = [
                    'type' => 'bank_account',
                    'date' => $bankAccount->getDateValidated(),
                    'name' => 'validation',
                    'old'  => null,
                    'new'  => $bankAccount,
                    'user' => null
                ];
            }

            if (null !== $bankAccount->getDateArchived()) {
                $history[$bankAccount->getDateArchived()->getTimestamp()][] = [
                    'type' => 'bank_account',
                    'date' => $bankAccount->getDateArchived(),
                    'name' => 'archival',
                    'old'  => null,
                    'new'  => $bankAccount,
                    'user' => null
                ];
            }
        }
    }

    /**
     * @param Clients $client
     * @param array   $history
     */
    private function fillAddressHistory(Clients $client, array &$history): void
    {
        $previousAddress = [
            AddressType::TYPE_MAIN_ADDRESS   => null,
            AddressType::TYPE_POSTAL_ADDRESS => null
        ];

        if ($client->isNaturalPerson()) {
            $addresses = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')->findBy(
                ['idClient' => $client],
                ['datePending' => 'ASC', 'id' => 'ASC']
            );
        } else {
            $company   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
            $addresses = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')->findBy(
                ['idCompany' => $company],
                ['datePending' => 'ASC', 'id' => 'ASC']
            );
        }

        foreach ($addresses as $address) {
            $addressType = AddressType::TYPE_MAIN_ADDRESS === $address->getIdType()->getLabel() ? 'main' : 'postal';

            if (null !== $address->getDatePending()) {
                $history[$address->getDatePending()->getTimestamp()][] = [
                    'type' => 'address',
                    'date' => $address->getDatePending(),
                    'name' => 'modification_' . $addressType,
                    'old'  => $previousAddress[$address->getIdType()->getLabel()] ? $previousAddress[$address->getIdType()->getLabel()] : null,
                    'new'  => $address,
                    'user' => null
                ];

                $previousAddress[$address->getIdType()->getLabel()] = $address;
            }

            if (
                null !== $address->getDateValidated()
                && $address->getDatePending()->getTimestamp() !== $address->getDateValidated()->getTimestamp()
            ) {
                $history[$address->getDateValidated()->getTimestamp()][] = [
                    'type' => 'address',
                    'date' => $address->getDateValidated(),
                    'name' => 'validation_' . $addressType,
                    'old'  => '',
                    'new'  => $address,
                    'user' => null
                ];
            }

            if (null !== $address->getDateArchived()) {
                $history[$address->getDateArchived()->getTimestamp()][] = [
                    'type' => 'address',
                    'date' => $address->getDateArchived(),
                    'name' => 'archival_' . $addressType,
                    'old'  => '',
                    'new'  => $address,
                    'user' => null
                ];
            }
        }
    }

    /**
     * @param Clients $client
     * @param array   $history
     */
    private function fillAttachmentHistory(Clients $client, array &$history): void
    {
        $attachments = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findBy(
            ['idClient' => $client],
            ['added' => 'ASC', 'id' => 'ASC']
        );

        foreach ($attachments as $attachment) {
            $history[$attachment->getAdded()->getTimestamp()][] = [
                'type' => 'attachment',
                'date' => $attachment->getAdded(),
                'name' => 'upload_' . $attachment->getType()->getLabel(),
                'old'  => null,
                'new'  => $attachment,
                'user' => null
            ];

            if (null !== $attachment->getArchived()) {
                $history[$attachment->getArchived()->getTimestamp()][] = [
                    'type' => 'attachment',
                    'date' => $attachment->getArchived(),
                    'name' => 'archival_' . $attachment->getType()->getLabel(),
                    'old'  => null,
                    'new'  => $attachment,
                    'user' => null
                ];
            }
        }
    }
}
