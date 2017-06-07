<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule;
use Unilend\Bundle\CoreBusinessBundle\Repository\BankAccountRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager;
use Unilend\Bundle\CoreBusinessBundle\Service\MailerManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\CoreBusinessBundle\Service\TaxManager;
use Unilend\Bundle\CoreBusinessBundle\Service\WelcomeOfferManager;

class AutomaticLenderValidationCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('unilend:lender:auto_validate')
            ->setDescription('Auto validate lenders having all attachments of type (1, 2, 3) validated by GreenPoint with status 9');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');
        /** @var \clients $client */
        $client = $entityManagerSimulator->getRepository('clients');

        try {
            $clientsToValidate = $client->getClientsToAutoValidate(
                [ClientsStatus::TO_BE_CHECKED, ClientsStatus::COMPLETENESS_REPLY, ClientsStatus::MODIFICATION],
                [VigilanceRule::VIGILANCE_STATUS_HIGH, VigilanceRule::VIGILANCE_STATUS_REFUSE]
            );

            foreach ($clientsToValidate as $row) {
                $client->get($row['id_client']);
                $this->validateLender($client, $row);
            }
        } catch (\Exception $exception) {
            $logger->error('An exception occurred. Exception message: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__]);
        }
    }

    /**
     * @param \clients $client
     * @param array    $attachment
     *
     * @throws \Exception
     */
    private function validateLender(\clients $client, array $attachment)
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');
        /** @var \clients_adresses $clientAddress */
        $clientAddress = $entityManagerSimulator->getRepository('clients_adresses');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $entityManagerSimulator->getRepository('lenders_accounts');
        /** @var \users_history $userHistory */
        $userHistory = $entityManagerSimulator->getRepository('users_history');
        /** @var \clients_status_history $clientStatusHistory */
        $clientStatusHistory = $entityManagerSimulator->getRepository('clients_status_history');
        /** @var WelcomeOfferManager $welcomeOfferManager */
        $welcomeOfferManager = $this->getContainer()->get('unilend.service.welcome_offer_manager');
        /** @var MailerManager $mailerManager */
        $mailerManager = $this->getContainer()->get('unilend.service.email_manager');
        /** @var TaxManager $taxManager */
        $taxManager = $this->getContainer()->get('unilend.service.tax_manager');
        /** @var ClientStatusManager $clientStatusManager */
        $clientStatusManager = $this->getContainer()->get('unilend.service.client_status_manager');

        /** @var BankAccountRepository $bankAccountRepository */
        $bankAccountRepository = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:BankAccount');
        if (null === $bankAccountRepository->getClientValidatedBankAccount($client->id_client)) {
            /** @var BankAccount $bankAccount */
            $bankAccount = $bankAccountRepository->findOneBy(['idClient' => $client->id_client, 'dateArchived' => null, 'dateValidated' => null]);
            if (null === $bankAccount) {
                throw new \Exception('Lender has no pending bank account to validate and could not be validated');
            }
            $gpAttachment = $bankAccount->getAttachment()->getGreenpointAttachment();
            if ($gpAttachment && GreenpointAttachment::STATUS_VALIDATION_VALID === $gpAttachment->getValidationStatus()) {
                /** @var BankAccountManager $bankAccountManager */
                $bankAccountManager = $this->getContainer()->get('unilend.service.bank_account_manager');
                $bankAccountManager->validateBankAccount($bankAccount);
            } else {
                throw new \Exception('Lender has no valid bank account and could not be validated');
            }
        }
        $existingClient = $client->getDuplicates($client->nom, $client->prenom, $client->naissance);
        $existingClient = array_shift($existingClient);

        if (false === empty($existingClient) && $existingClient['id_client'] != $client->id_client) {
            $logger->warning('Processing client id: ' . $client->id_client . ' - Duplicate client found: ' . json_encode($existingClient), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);
            return;
        } elseif (1 == $client->origine && 0 == $clientStatusHistory->counter('id_client = ' . $client->id_client . ' AND id_client_status = (SELECT cs.id_client_status FROM clients_status cs WHERE cs.status = ' . ClientsStatus::VALIDATED . ')')) {
            $response = $welcomeOfferManager->createWelcomeOffer($client);
            $logger->info('Client ID: ' . $client->id_client . ' Welcome offer creation result: ' . json_encode($response), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_lender' => $client->id_client]);
        }
        $lenderAccount->get($client->id_client, 'id_client_owner');
        $clientAddress->get($client->id_client, 'id_client');
        $clientStatusManager->addClientStatus($client, Users::USER_ID_CRON, ClientsStatus::VALIDATED, 'Validation automatique basée sur Green Point');
        $serialize = serialize(array('id_client' => $client->id_client, 'attachment_data' => $attachment));
        $userHistory->histo(\users_history::FORM_ID_LENDER, 'validation auto preteur', '0', $serialize);

        if ($clientStatusHistory->counter('id_client = ' . $client->id_client . ' AND id_client_status = (SELECT cs.id_client_status FROM clients_status cs WHERE cs.status = ' . ClientsStatus::MODIFICATION . ')') > 0) {
            $mailerManager->sendClientValidationEmail($client, 'preteur-validation-modification-compte');
        } else {
            $mailerManager->sendClientValidationEmail($client, 'preteur-confirmation-activation');
        }
        $taxManager->addTaxToApply($client, $lenderAccount, $clientAddress, Users::USER_ID_CRON);
    }
}
