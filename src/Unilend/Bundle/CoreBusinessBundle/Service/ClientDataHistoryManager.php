<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, Attachment, Clients, Companies, Wallet, WalletType
};
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class ClientDataHistoryManager
{
    const MAIN_ADDRESS_FORM_LABEL   = 'main_address';
    const POSTAL_ADDRESS_FORM_LABEL = 'postal_address';
    const BANK_ACCOUNT_FORM_LABEL   = 'bank_account';

    /** @var EntityManager */
    private $entityManager;
    /** @var TranslatorInterface */
    private $translator;
    /** @var LoggerInterface */
    private $logger;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;

    /**
     * @param EntityManager           $entityManager
     * @param TranslatorInterface     $translator
     * @param LoggerInterface         $logger
     * @param TemplateMessageProvider $messageProvider
     * @param \Swift_Mailer           $mailer
     */
    public function __construct(
        EntityManager $entityManager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer
    )
    {
        $this->entityManager   = $entityManager;
        $this->translator      = $translator;
        $this->logger          = $logger;
        $this->messageProvider = $messageProvider;
        $this->mailer          = $mailer;
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

    /**
     * @param Clients      $client
     * @param array        $modifiedData
     * @param Attachment[] $modifiedAttachments
     */
    public function sendAccountModificationEmail(Clients $client, array $modifiedData = [], array $modifiedAttachments = []): void
    {
        if (empty($modifiedData) && empty($modifiedAttachments)) {
            $this->logger->warning('There are no modified data or attachments, the notification email will not be sent to the client: ' . $client->getIdClient(), [
                'id_client' => $client,
                'class'     => __CLASS__,
                'function'  => __FUNCTION__
            ]);

            return;
        }

        $attachmentLabels = [];

        foreach ($modifiedAttachments as $attachment) {
            $attachmentLabels[] = $attachment->getType()->getLabel();
        }

        try {
            /** @var Wallet $lenderWallet */
            $lenderWallet        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')
                ->getWalletByType($client, WalletType::LENDER);
            $wireTransferPattern = null !== $lenderWallet ? $lenderWallet->getWireTransferPattern() : '';
        } catch (\Exception $exception) {
            $wireTransferPattern = '';
            $this->logger->error('Could not get client lender wallet - Exception: ' . $exception->getMessage(), [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);
        }

        $modifiedData     = $this->formatArrayToUnorderedList(array_intersect_key($this->getFormFieldsTranslationsForEmail(), $modifiedData));
        $attachmentLabels = $this->formatArrayToUnorderedList($attachmentLabels);

        $keywords = [
            'firstName'               => $client->getPrenom(),
            'lenderPattern'           => $wireTransferPattern,
            'modifiedDataTitle'       => null === $modifiedData ? null : $this->translator->trans('lender-modification-email_modified-data-title'),
            'modifiedData'            => $modifiedData,
            'modifiedAttachmentTitle' => null === $attachmentLabels ? null : $this->translator->trans('lender-modification-email_modified-attachment-title'),
            'modifiedAttachment'      => $attachmentLabels
        ];

        if ($client->isNaturalPerson()) {
            $templateName = 'synthese-modification-donnees-personne-physique';
        } else {
            $templateName = 'synthese-modification-donnees-personne-morale';
            /** @var Companies $company */
            $company  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
            $keywords += [
                'companyName' => $company->getName()
            ];
        }

        $message = $this->messageProvider->newMessage($templateName, $keywords);

        try {
            $message->setTo($client->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->error('Could not send email: ' . $templateName . ' - Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'id_client'        => $client->getIdClient(),
                'class'            => __CLASS__,
                'function'         => __FUNCTION__,
                'file'             => $exception->getFile(),
                'line'             => $exception->getLine()
            ]);
        }
    }

    /**
     * @return array
     */
    private function getFormFieldsTranslationsForEmail()
    {
        $formFields = [
            self::MAIN_ADDRESS_FORM_LABEL,
            self::POSTAL_ADDRESS_FORM_LABEL,
            self::BANK_ACCOUNT_FORM_LABEL
        ];

        foreach (array_merge($formFields, ClientAuditer::LOGGED_FIELDS) as $field) {
            $translations[$field] = $this->translator->trans('lender-modification-email_field-' . $field);
        }

        return $translations ?? [];
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
