<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, Attachment, Clients, Companies, WalletType
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
     * @param string[]     $clientChanges
     * @param string[]     $companyChanges
     * @param Attachment[] $modifiedAttachments
     * @param string       $modifiedAddressType
     * @param bool         $isBankAccountChanged
     */
    public function sendLenderProfileModificationEmail(
        Clients $client,
        array $clientChanges,
        array $companyChanges,
        array $modifiedAttachments,
        string $modifiedAddressType,
        bool $isBankAccountChanged
    ): void
    {
        if (empty($clientChanges) && empty($companyChanges) && empty($modifiedAttachments) && empty($modifiedAddressType) && false === $isBankAccountChanged) {
            return;
        }

        $attachmentLabels = [];

        foreach ($modifiedAttachments as $attachment) {
            $attachmentLabels[] = $attachment->getType()->getLabel();
        }

        $wireTransferPattern = '';
        $wallets = $client->getWallets();

        foreach ($wallets as $wallet) {
            if (WalletType::LENDER === $wallet->getIdType()->getLabel()) {
                $wireTransferPattern =  $wallet->getWireTransferPattern();
                break;
            }
        }

        $modifiedData     = $this->formatArrayToUnorderedList($this->getFormFieldsTranslationsForEmail($client, $clientChanges, $companyChanges, $modifiedAddressType, $isBankAccountChanged));
        $attachmentLabels = $this->formatArrayToUnorderedList($attachmentLabels);

        $keywords = [
            'firstName'               => $client->getPrenom(),
            'lenderPattern'           => $wireTransferPattern,
            'modifiedData'            => $modifiedData,
            'modifiedAttachmentTitle' => null === $attachmentLabels ? null : $this->translator->trans('lender-modification-email_modified-attachment-title'),
            'modifiedAttachment'      => $attachmentLabels
        ];

        if ($client->isNaturalPerson()) {
            $templateName = 'synthese-modification-donnees-personne-physique';
        } else {
            $templateName = 'synthese-modification-donnees-personne-morale';
            /** @var Companies $company */
            $company = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);

            if (null !== $company && false === empty($company->getName())) {
                $keywords += [
                    'companyName' => $company->getName()
                ];
            } else {
                $keywords += [
                    'companyName' => null
                ];
                $reason   = null === $company ? 'Client company not found' : 'Client company (id_company: ' . $company->getIdCompany() . ') name is empty';
                $this->logger->error('Could not fill company name keyword for email "synthese-modification-donnees-personne-morale". ' . $reason, [
                    'id_client' => $client->getIdClient(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__
                ]);
            }
        }

        if (empty($client->getPrenom())) {
            $this->logger->error('Could not fill client first name keyword for email "' . $templateName . '". ', [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__
            ]);
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
     * @param Clients $client
     * @param array   $clientChanges
     * @param array   $companyChanges
     * @param string  $modifiedAddressType
     * @param bool    $isBankAccountChanged
     *
     * @return string[]
     */
    public function getFormFieldsTranslationsForEmail(
        Clients $client,
        array $clientChanges,
        array $companyChanges,
        string $modifiedAddressType,
        bool $isBankAccountChanged
    ): array
    {
        $translationParams = [];
        $translations      = [];

        if (false === $client->isNaturalPerson()) {
            $company = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $client]);
            if ($company) {
                $translationParams = ['%companyName%' => $company->getName()];
            }
        }

        foreach ($clientChanges as $field) {
            $translationKey  = 'lender-modification-email_client-field-' . $field;
            $translationText = $this->translator->transChoice($translationKey, (int) $client->isNaturalPerson(), $translationParams);
            $translations    = $this->addTranslation($client, $translationKey, $translationText, $translations);
        }

        foreach ($companyChanges as $field) {
            $translationKey  = 'lender-modification-email_company-field-' . $field;
            $translationText = $this->translator->trans($translationKey, $translationParams);
            $translations    = $this->addTranslation($client, $translationKey, $translationText, $translations);
        }

        if (in_array($modifiedAddressType, [AddressType::TYPE_MAIN_ADDRESS, AddressType::TYPE_POSTAL_ADDRESS])) {
            $translationKey  = 'lender-modification-email_address-type-' . $modifiedAddressType;
            $translationText = $this->translator->transChoice($translationKey, (int) $client->isNaturalPerson(), $translationParams);
            $translations    = $this->addTranslation($client, $translationKey, $translationText, $translations);
        }

        if ($isBankAccountChanged) {
            $translationKey  = 'lender-modification-email_bank-account';
            $translationText = $this->translator->transChoice($translationKey, (int) $client->isNaturalPerson(), $translationParams);
            $translations    = $this->addTranslation($client, $translationKey, $translationText, $translations);
        }

        return array_unique($translations);
    }

    /**
     * @param Clients $client
     * @param string  $translationKey
     * @param string  $translationText
     * @param array   $translations
     *
     * @return array
     */
    private function addTranslation(Clients $client, string $translationKey, string $translationText, array $translations): array
    {
        if ($translationKey !== $translationText) {
            $translations[] = $translationText;
        } else {
            $this->logger->error('Trying to add modified content translations into the email of "lender data modification". Translation with key "' . $translationKey . '" is not found, and will not be added', [
                'id_client'       => $client->getIdClient(),
                'translation_key' => $translationKey,
                'class'           => __CLASS__,
                'function'        => __FUNCTION__
            ]);
        }

        return $translations;
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
