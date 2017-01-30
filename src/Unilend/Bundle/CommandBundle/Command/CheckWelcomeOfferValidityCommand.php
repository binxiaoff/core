<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class CheckWelcomeOfferValidityCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('check:welcome_offer_validity')
            ->setDescription('Remove WelcomeOffers not used by lenders during a time period');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager    = $this->getContainer()->get('unilend.service.entity_manager');
        $em               = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationManager = $this->getContainer()->get('unilend.service.operation_manager');
        $logger           = $this->getContainer()->get('monolog.logger.console');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        /** @var \offres_bienvenues_details $welcomeOfferDetails */
        $welcomeOfferDetails = $entityManager->getRepository('offres_bienvenues_details');

        $settings->get('Durée validité Offre de bienvenue', 'type');
        $offerValidity               = $settings->value;
        $dateLimit                   = new \DateTime('NOW - ' . $offerValidity . ' DAYS');
        $numberOfUnusedWelcomeOffers = 0;

        foreach ($welcomeOfferDetails->getUnusedWelcomeOffers($dateLimit) as $welcomeOffer) {
            $welcomeOfferDetails->get($welcomeOffer['id_offre_bienvenue_detail']);
            $welcomeOfferDetails->status = \offres_bienvenues_details::STATUS_CANCELED;
            $welcomeOfferDetails->update();

            $wallet       = $em->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($welcomeOfferDetails->id_client, WalletType::LENDER);
            $welcomeOffer = $em->getRepository('UnilendCoreBusinessBundle:OffresBienvenuesDetails')->find($welcomeOfferDetails->id_offre_bienvenue_detail);
            try {
                $operationManager->cancelWelcomeOffer($wallet, $welcomeOffer);
                $numberOfUnusedWelcomeOffers +=1;
            } catch (\Exception $exception) {
                continue;
            }
        }

        $logger->info('Number of withdrawn welcome offers: ' . $numberOfUnusedWelcomeOffers);
    }
}
