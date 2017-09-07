<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderValidationManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\CoreBusinessBundle\Service\TaxManager;

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
        /** @var \clients $client */
        $client = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('clients');

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
            $logger = $this->getContainer()->get('monolog.logger.console');
            $logger->error('Could not validate the lender. Exception message: ' . $exception->getMessage(), ['id_client' => $client->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]);
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
        $entityManager          = $this->getContainer()->get('doctrine.orm.entity_manager');
        $entityManagerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        $logger                 = $this->getContainer()->get('monolog.logger.console');
        $welcomeOfferManager    = $this->getContainer()->get('unilend.service.welcome_offer_manager');
        $mailerManager          = $this->getContainer()->get('unilend.service.email_manager');
        $taxManager             = $this->getContainer()->get('unilend.service.tax_manager');
        $clientStatusManager    = $this->getContainer()->get('unilend.service.client_status_manager');
        $bankAccountRepository  = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount');
        /** @var \clients_adresses $clientAddress */
        $clientAddress = $entityManagerSimulator->getRepository('clients_adresses');
        /** @var \users_history $userHistory */
        $userHistory = $entityManagerSimulator->getRepository('users_history');
        /** @var \clients_status_history $clientStatusHistory */
        $clientStatusHistory = $entityManagerSimulator->getRepository('clients_status_history');

        if (null === $bankAccountRepository->getClientValidatedBankAccount($client->id_client)) {
            /** @var BankAccount $bankAccount */
            $bankAccount = $bankAccountRepository->findOneBy(['idClient' => $client->id_client, 'dateArchived' => null, 'dateValidated' => null]);
            if (null === $bankAccount) {
                throw new \Exception('Lender has no pending bank account to validate and could not be validated - Client: ' . $client->id_client);
            }

            $attachment = $bankAccount->getAttachment();
            if (null === $attachment) {
                throw new \Exception('Lender has no attachment for his bank account and could not be validated - Client: ' . $client->id_client);
            }

            $gpAttachment = $bankAccount->getAttachment()->getGreenpointAttachment();
            if ($gpAttachment && GreenpointAttachment::STATUS_VALIDATION_VALID === $gpAttachment->getValidationStatus()) {
                /** @var BankAccountManager $bankAccountManager */
                $bankAccountManager = $this->getContainer()->get('unilend.service.bank_account_manager');
                $bankAccountManager->validateBankAccount($bankAccount);
            } else {
                throw new \Exception('Lender has no valid bank account and could not be validated - Client: ' . $client->id_client);
            }
        }

        $validation = $lenderValidationManager->validateClient($client, $user);
        if (true !== $validation) {
            $logger->warning('Processing client id: ' . $client->id_client . ' - Duplicate client found: ' . json_encode($validation), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);
            return;
        }

        $serialize = serialize(['id_client' => $client->id_client, 'attachment_data' => $attachment]);
        $userHistory->histo(\users_history::FORM_ID_LENDER, 'validation auto preteur', '0', $serialize);

        $clientAddress->get($client->id_client, 'id_client');
        $clientEntity = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        $taxManager->addTaxToApply($clientEntity, $clientAddress, Users::USER_ID_CRON);
    }
}
