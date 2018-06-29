<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\{
    InputInterface
};
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, ClientsStatus, PaysV2, Users, VigilanceRule
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
        $lenderValidationManager = $this->getContainer()->get('unilend.service.lender_validation_manager');

        /** @var \clients $clientDataClass */
        $clientDataClass  = $entityManagerSimulator->getRepository('clients');
        $clientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $userRepository   = $entityManager->getRepository('UnilendCoreBusinessBundle:Users');

        try {
            $clientsToValidate = $clientDataClass->getClientsToAutoValidate(
                [ClientsStatus::STATUS_TO_BE_CHECKED, ClientsStatus::STATUS_COMPLETENESS_REPLY, ClientsStatus::STATUS_MODIFICATION, ClientsStatus::STATUS_SUSPENDED],
                [VigilanceRule::VIGILANCE_STATUS_HIGH, VigilanceRule::VIGILANCE_STATUS_REFUSE]
            );

            foreach ($clientsToValidate as $clientData) {
                $duplicates = [];
                /** @var Clients $client */
                $client     = $clientRepository->find($clientData['id_client']);

                if (
                    null !== $client->getIdClientStatusHistory()
                    && ClientsStatus::STATUS_SUSPENDED === $client->getIdClientStatusHistory()->getIdStatus()->getLabel()
                    && null !== $client->getIdAddress()
                    && PaysV2::COUNTRY_FRANCE !== $client->getIdAddress()->getIdCountry()
                ) {
                    continue;
                }
                /** @var Users $user */
                $user       = $userRepository->find(Users::USER_ID_CRON);
                $validation = $lenderValidationManager->validateClient($client, $user, $duplicates);

                if (true !== $validation) {
                    $this->getContainer()->get('unilend.service.slack_manager')
                        ->sendMessage('La validation automatique a détectée un client en double. Le client ' . $client->getIdClient() . ' est un doublon de ' . implode(', ', $duplicates), '#team-marketing');
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
