<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\WelcomeOfferManager;

class DevCreateWelcomeOfferCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:create:welcome_offer')
            ->setDescription('add missing welcome offers');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var WelcomeOfferManager $welcomeOfferManager */
        $welcomeOfferManager = $this->getContainer()->get('unilend.service.welcome_offer_manager');

        $clients  = [];
        $fileName = $this->getContainer()->getParameter('path.protected') . 'import/welcome_offer.csv';

        if (false === file_exists($fileName)) {
            throw new \Exception($this->getContainer()->getParameter('path.protected') . 'import/welcome_offer.csv not found');
        }
        if (false === ($handle = fopen($fileName, 'r'))) {
            throw new \Exception($this->getContainer()->getParameter('path.protected') . 'import/welcome_offer.csv cannot be opened');
        }

        foreach (fgetcsv($handle, 0, ';') as $row) {
            if (false !== filter_var($row, FILTER_VALIDATE_INT)) {
                $clients[] = $row;
            }
        }
        fclose($handle);

        /** @var \clients $client */
        $client = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('clients');

        $entityManager       = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $walletRepository    = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');

        $distributedOffers = 0;

        foreach ($clients as $clientId) {
            if ($client->get($clientId)) {
                $wallet               = $walletRepository->getWalletByType($clientId, WalletType::LENDER);
                $welcomeOffer         = $operationRepository->sumCreditOperationsByTypeAndYear($wallet, [OperationType::UNILEND_PROMOTIONAL_OPERATION]);
                $welcomeOfferCanceled = $operationRepository->sumDebitOperationsByTypeAndYear($wallet, [OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL]);

                if (0 == bcsub($welcomeOffer, $welcomeOfferCanceled, 2)) {
                    $return = $welcomeOfferManager->createWelcomeOffer($client);
                    if (0 == $return['code']) {
                        $distributedOffers += 1;
                        $output->writeln(' Client ' . $clientId . ' : ' . $return['message']);
                    } else {
                        $output->writeln('Client ' . $clientId . ' : Welcome Offer not distributed - ' . $return['message']);
                    }
                }
            } else {
                $output->writeln(' Client ' . $clientId . ' : Welcome Offer already distributed');
            }
        }
        $output->writeln('Number of welcome offers created : ' . $distributedOffers);
    }
}
