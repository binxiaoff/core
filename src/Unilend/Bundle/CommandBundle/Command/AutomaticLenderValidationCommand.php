<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\{
    InputInterface
};
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;

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
        /** @var EntityManager $entityManager */
        $entityManager           = $this->getContainer()->get('doctrine.orm.entity_manager');
        $entityManagerSimulator  = $this->getContainer()->get('unilend.service.entity_manager');
        $lenderValidationManager = $this->getContainer()->get('unilend.service.lender_validation_manager');

        /** @var \clients $clientDataClass */
        $clientDataClass  = $entityManagerSimulator->getRepository('clients');
        $clientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $userRepository   = $entityManager->getRepository('UnilendCoreBusinessBundle:Users');

        try {
            foreach ($clientDataClass->getClientsToAutoValidate() as $clientData) {
                $duplicates = [];
                $client     = $clientRepository->find($clientData['id_client']);
                $user       = $userRepository->find(Users::USER_ID_CRON);
                $validation = $lenderValidationManager->validateClient($client, $user, $duplicates);

                if (true !== $validation) {
                    $this->getContainer()->get('unilend.service.slack_manager')
                        ->sendMessage('La validation automatique a détecté un client en double. Le client ' . $client->getIdClient() . ' est un doublon de ' . implode(', ', $duplicates), '#team-marketing');
                    continue;
                }
            }
        } catch (\Exception $exception) {
            $logger = $this->getContainer()->get('monolog.logger.console');
            $logger->error('Could not validate the lender. Exception message: ' . $exception->getMessage(), [
                'class'     => __CLASS__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
                'id_client' => isset($client) ? $client->getIdClient() : ''
            ]);
        }
    }
}
