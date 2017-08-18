<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails;
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
        $entityManager               = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationManager            = $this->getContainer()->get('unilend.service.operation_manager');
        $logger                      = $this->getContainer()->get('monolog.logger.console');
        $validitySetting             = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Durée validité Offre de bienvenue']);
        $dateLimit                   = new \DateTime('NOW - ' . $validitySetting->getValue() . ' DAYS');
        $numberOfUnusedWelcomeOffers = 0;

        /** @var OffresBienvenuesDetails $welcomeOffer */
        foreach ($entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenuesDetails')->findUnusedWelcomeOffers($dateLimit) as $welcomeOffer) {
            $welcomeOffer->setStatus(OffresBienvenuesDetails::STATUS_CANCELED);
            $wallet       = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($welcomeOffer->getIdClient(), WalletType::LENDER);
            try {
                $operationManager->cancelWelcomeOffer($wallet, $welcomeOffer);
                $numberOfUnusedWelcomeOffers +=1;
            } catch (\Exception $exception) {
                continue;
            }
        }
        $entityManager->flush();

        $logger->info('Number of withdrawn welcome offers: ' . $numberOfUnusedWelcomeOffers);
    }
}
