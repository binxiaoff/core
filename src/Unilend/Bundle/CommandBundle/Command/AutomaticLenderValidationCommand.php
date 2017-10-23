<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule;
use Unilend\Bundle\CoreBusinessBundle\Repository\BankAccountRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager;

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

            foreach ($clientsToValidate as $clientData) {
                $this->validateLender($clientData);
            }
        } catch (\Exception $exception) {
            $logger = $this->getContainer()->get('monolog.logger.console');
            $logger->error('Could not validate the lender. Exception message: ' . $exception->getMessage(),
                ['id_client' => $client->id_client, 'class' => __CLASS__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
        }
    }

    /**
     * @param array $clientData
     *
     * @throws \Exception
     */
    private function validateLender(array $clientData)
    {
        $entityManager          = $this->getContainer()->get('doctrine.orm.entity_manager');
        $entityManagerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        $logger                 = $this->getContainer()->get('monolog.logger.console');

        /** @var BankAccountRepository $bankAccountRepository */
        $bankAccountRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount');
        $client                = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($clientData['id_client']);
        $bankAccountToValidate = $bankAccountRepository->getLastModifiedBankAccount($client);

        if (null === $bankAccountToValidate) {
            throw new \Exception('Lender has no associated bank account - Client: ' . $client->getIdClient());
        }
        if (null === $bankAccountToValidate->getDateValidated()
            && (null === $bankAccountToValidate->getAttachment() || ($bankAccountToValidate->getAttachment() && $clientData['rib_attachment_id'] != $bankAccountToValidate->getAttachment()->getId()))
        ) {
            $attachmentId = (null === $bankAccountToValidate->getAttachment()) ? '' : ' (id_attachment: ' . $bankAccountToValidate->getAttachment()->getId() . ') ';
            throw new \Exception('Lender\'s pending bank account (id: ' . $bankAccountToValidate->getId() . ')' . $attachmentId .
                'is not associated with the validated RIB attachment (id:' . $clientData['rib_attachment_id'] . ') - Client: ' . $client->getIdClient());
        }

        /** @var BankAccountManager $bankAccountManager */
        $bankAccountManager = $this->getContainer()->get('unilend.service.bank_account_manager');
        $bankAccountManager->validateBankAccount($bankAccountToValidate);

        /** @var Users $user */
        $user                    = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_CRON);
        $lenderValidationManager = $this->getContainer()->get('unilend.service.lender_validation_manager');
        $validation              = $lenderValidationManager->validateClient($client, $user);
        if (true !== $validation) {
            $logger->warning('Processing client id: ' . $client->getIdClient() . ' - Duplicate client found: ' . json_encode($validation), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->getIdClient()]);
            return;
        }

        /** @var \users_history $userHistory */
        $userHistory = $entityManagerSimulator->getRepository('users_history');
        $serialize   = serialize(['id_client' => $client->getIdClient(), 'attachment_data' => $clientData]);
        $userHistory->histo(\users_history::FORM_ID_LENDER, 'validation auto preteur', '0', $serialize);

        /** @var \clients_adresses $clientAddress */
        $clientAddress = $entityManagerSimulator->getRepository('clients_adresses');
        $clientAddress->get($client->getIdClient(), 'id_client');
        $taxManager = $this->getContainer()->get('unilend.service.tax_manager');
        $taxManager->addTaxToApply($client, $clientAddress, Users::USER_ID_CRON);
    }
}
