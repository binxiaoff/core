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

        $clients = [113309,113312,113390,113389,113384,113339,94969,109804,109781,109757,109748,109739,109726,109694,109660,109462,109385,109202,108473,108211,104896,81995,39865,108112,109744,109756,109699,109807,109592,109510,109021,109232,109417,109342,109319,109067,105571,108995,103297,109246,108644,109241,109178,108910,47125,25015,109174,109138,109090,109084,108952,101864,109078,107917,108901,60707,21597,11658,107705,108772,108701,108574,108182,107603,96583,108671,108595,108566,108482,108457,108413,108397,108394,108320,108307,108190,108176,108140,108082,108079,108023,107887,107857,107800,107741,107573,100955,104243,77714,70549,107533,108134,98560,108331,107872,107531,107063,104260,41460,35392,100757,108257,105761,107530,106811,105403,85791,53748,23663,108194,106394,2019,107447,49689,106906,107437,107327,106973,106898,106793,106598,96388,107011,106958,106930,106909,106868,106867,106840,106777,106553,106223,104870,87980,102751,49499,106952,106901,106895,106877,106810,106802,106795,106742,106589,102817,104434,106309,106723,106627,106619,106543,106424,106400,19479,106318,106165,105895,106268,106079,106057,106013,106012,104177,105725,104800,104222,36413,105215,105530,100192,105122,104699,105470,105637,105928,106231,104308,106409,95431,105638,106088,106049,45319,79198,82158,105902,105734,105617,105601,105587,105665,105584,105575,105485,105295,100844,84621,100286];

        /** @var \clients $client */
        $client = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('clients');
        /** @var \transactions $transactions */
        $transactions = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('transactions');

        $distributedOffers = 0;

        foreach ($clients as $clientId){
            if ($client->get($clientId) && false === $transactions->exist('id_client = ' . $client->id_client . ' AND type_transaction = ' . \transactions_types::TYPE_WELCOME_OFFER)) {
                $return = $welcomeOfferManager->createWelcomeOffer($client);
                if (0 == $return){
                    $distributedOffers = 1;
                } else {
                    $output->writeln('Welcome Offer not distributed for client: ' . $clientId . ' because : ' . $return['message']);
                }
            } else {
                $output->writeln('Welcome Offer already distributed for client ' . $clientId);
            }
        }
        $output->writeln('Number of welcome offers created : ' . $distributedOffers);
    }
}
