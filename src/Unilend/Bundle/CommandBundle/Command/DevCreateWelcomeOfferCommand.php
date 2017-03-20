<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
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
        /** @var \transactions $transactions */
        $transactions = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('transactions');

        $distributedOffers = 0;

        foreach ($clients as $clientId) {
            if (
                $client->get($clientId)
                && 0 == $transactions->sum('id_client = ' . $clientId . ' AND type_transaction IN (' . implode(',', [\transactions_types::TYPE_WELCOME_OFFER, \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION]) . ')', 'montant')
            ) {
                $return = $welcomeOfferManager->createWelcomeOffer($client);
                if (0 == $return['code']) {
                    $distributedOffers += 1;
                    $output->writeln(' Client ' . $clientId . ' : ' . $return['message']);
                } else {
                    $output->writeln('Client ' . $clientId . ' : Welcome Offer not distributed - ' . $return['message']);
                }
            } else {
                $output->writeln(' Client ' . $clientId . ' : Welcome Offer already distributed');
            }
        }
        $output->writeln('Number of welcome offers created : ' . $distributedOffers);
    }
}
