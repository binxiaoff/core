<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\ORM\EntityManager;
use Psr\Cache\{CacheException, CacheItemPoolInterface};
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, Users};
use Unilend\librairies\CacheKeys;

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
        $lenderValidationManager = $this->getContainer()->get('unilend.service.lender_validation_manager');

        $clientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $userRepository   = $entityManager->getRepository('UnilendCoreBusinessBundle:Users');

        try {
            foreach ($clientRepository->getClientsToAutoValidate() as $clientData) {
                $duplicates = [];
                $client     = $clientRepository->find($clientData['id_client']);
                $user       = $userRepository->find(Users::USER_ID_CRON);
                $validation = $lenderValidationManager->validateClient($client, $user, $duplicates);

                if (true !== $validation && false === empty($duplicates)) {
                    $this->notifyMarketingOfDuplicate($client, $duplicates);
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

    /**
     * @param Clients $client
     * @param array   $duplicates
     */
    private function notifyMarketingOfDuplicate(Clients $client, array $duplicates): void
    {
        /** @var CacheItemPoolInterface $cachePool */
        $cachePool = $this->getContainer()->get('memcache.default');

        try {
            $cachedItem = $cachePool->getItem(CacheKeys::LENDER_DUPLICATE_NOTIFICATION . '_' . $client->getIdClient());
            $cacheHit   = $cachedItem->isHit();
        } catch (CacheException $exception) {
            $cachedItem = null;
            $cacheHit   = false;
        }

        if (false === $cacheHit) {
            $alertTitle   = 'Validation automatique des prêteurs :';
            $alertMessage = 'Le client ' . $client->getIdClient() . ' est un doublon de ' . implode(', ', $duplicates) . '. Vous devez choisir le compte à valider dans le BO.';
            $this->getContainer()->get('unilend.service.slack_manager')
                 ->sendMessage($alertTitle . "\n> " . $alertMessage, '#team-marketing');

            $cachedItem->set(true)->expiresAfter(CacheKeys::DAY * 7);
            $cachePool->save($cachedItem);
        }
    }
}
