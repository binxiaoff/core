<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule;

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
        $entityManager           = $this->getContainer()->get('doctrine.orm.entity_manager');
        $entityManagerSimulator  = $this->getContainer()->get('unilend.service.entity_manager');
        $logger                  = $this->getContainer()->get('monolog.logger.console');
        $lenderValidationManager = $this->getContainer()->get('unilend.service.lender_validation_manager');

        /** @var \clients $clientData */
        $clientData       = $entityManagerSimulator->getRepository('clients');
        $clientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $userRepository   = $entityManager->getRepository('UnilendCoreBusinessBundle:Users');

        try {
            $clientsToValidate = $clientData->getClientsToAutoValidate(
                [ClientsStatus::TO_BE_CHECKED, ClientsStatus::COMPLETENESS_REPLY, ClientsStatus::MODIFICATION],
                [VigilanceRule::VIGILANCE_STATUS_HIGH, VigilanceRule::VIGILANCE_STATUS_REFUSE]
            );

            foreach ($clientsToValidate as $clientData) {
                $client = $clientRepository->find($clientData['id_client']);
                $user   = $userRepository->find(Users::USER_ID_CRON);

                $validation = $lenderValidationManager->validateClient($client, $user);
                if (true !== $validation) {
                    $logger->warning('Processing client id: ' . $client->getIdClient() . ' - Duplicate client found: ' . json_encode($validation),
                        ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->getIdClient()]);
                    continue;
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
        } catch (\Exception $exception) {
            $logger = $this->getContainer()->get('monolog.logger.console');
            $logger->error('Could not validate the lender. Exception message: ' . $exception->getMessage(),
                ['id_client' => $client->id_client, 'class' => __CLASS__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
        }
    }
}
