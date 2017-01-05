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
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \settings $oSettings */
        $oSettings = $entityManager->getRepository('settings');
        /** @var \offres_bienvenues_details $oWelcomeOfferDetails */
        $oWelcomeOfferDetails = $entityManager->getRepository('offres_bienvenues_details');
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');

        $oSettings->get('Durée validité Offre de bienvenue', 'type');
        $sOfferValidity = $oSettings->value;

        $aUnusedWelcomeOffers = $oWelcomeOfferDetails->select('status = 0');
        $oDateTime            = new \DateTime();

        $em               = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationManager = $this->getContainer()->get('unilend.service.operation_manager');

        $iNumberOfUnusedWelcomeOffers = 0;

        foreach ($aUnusedWelcomeOffers as $aWelcomeOffer) {
            $oAdded    = \DateTime::createFromFormat('Y-m-d H:i:s', $aWelcomeOffer['added']);
            $oInterval = $oDateTime->diff($oAdded);

            if ($oInterval->days >= $sOfferValidity) {
                $oWelcomeOfferDetails->get($aWelcomeOffer['id_offre_bienvenue_detail']);
                $oWelcomeOfferDetails->status = 2;
                $oWelcomeOfferDetails->update();

                $wallet       = $em->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($oWelcomeOfferDetails->id_client, WalletType::LENDER);
                $welcomeOffer = $em->getRepository('UnilendCoreBusinessBundle:OffresBienvenuesDetails')->find($oWelcomeOfferDetails->id_offre_bienvenue_detail);
                $operationManager->cancelWelcomeOffer($wallet, $welcomeOffer);

                $iNumberOfUnusedWelcomeOffers +=1;
            }
        }

        $logger->info('Number of withdrawn welcome offers: ' . $iNumberOfUnusedWelcomeOffers);
    }
}
