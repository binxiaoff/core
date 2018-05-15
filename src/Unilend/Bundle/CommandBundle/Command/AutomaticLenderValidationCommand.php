<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\{
    InputInterface
};
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    ClientsStatus, Users, VigilanceRule
};

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

        /** @var \clients $clientDataClass */
        $clientDataClass  = $entityManagerSimulator->getRepository('clients');
        $clientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $userRepository   = $entityManager->getRepository('UnilendCoreBusinessBundle:Users');

        try {
            $clientsToValidate = $clientDataClass->getClientsToAutoValidate(
                [ClientsStatus::STATUS_TO_BE_CHECKED, ClientsStatus::STATUS_COMPLETENESS_REPLY, ClientsStatus::STATUS_MODIFICATION],
                [VigilanceRule::VIGILANCE_STATUS_HIGH, VigilanceRule::VIGILANCE_STATUS_REFUSE]
            );

            foreach ($clientsToValidate as $clientData) {
                $duplicates = [];
                $client     = $clientRepository->find($clientData['id_client']);
                $user       = $userRepository->find(Users::USER_ID_CRON);
                $validation = $lenderValidationManager->validateClient($client, $user, $duplicates);

                if (true !== $validation) {
                    $logger->warning(
                        'Processing client ID: ' . $client->getIdClient() . ' - Duplicate client found: ' . implode(', ', $duplicates),
                        ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->getIdClient()]
                    );
                    continue;
                }
            }
        } catch (\Exception $exception) {
            $logger = $this->getContainer()->get('monolog.logger.console');
            $logger->error('Could not validate the lender. Exception message: ' . $exception->getMessage(),
                ['class' => __CLASS__, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'id_client' => isset($client) ? $client->getIdClient() : '']);
        }
    }
}
