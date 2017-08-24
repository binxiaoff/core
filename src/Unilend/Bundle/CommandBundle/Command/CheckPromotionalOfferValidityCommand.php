<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails;
use Unilend\Bundle\CoreBusinessBundle\Entity\Settings;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Repository\SponsorshipRepository;

class CheckPromotionalOfferValidityCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('check:promotional_offer_validity')
            ->setDescription('Remove WelcomeOffers and Sponsorship rewards not used by lenders during a time period');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cancelWelcomeOffers();
        $this->cancelSponsorshipOffers();

        $this->checkUnilendPromotionalBalance();
    }

    private function cancelWelcomeOffers()
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
            $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($welcomeOffer->getIdClient(), WalletType::LENDER);
            try {
                $operationManager->cancelWelcomeOffer($wallet, $welcomeOffer);
                $numberOfUnusedWelcomeOffers +=1;
            } catch (\Exception $exception) {
                continue;
            }
        }
        $entityManager->flush();

        $logger->info('Number of cancelled welcome offers: ' . $numberOfUnusedWelcomeOffers);
    }

    private function cancelSponsorshipOffers()
    {
        /** @var SponsorshipRepository $sponsorshipRepository */
        $sponsorshipRepository = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Sponsorship');
        $sponsorshipManager    = $this->getContainer()->get('unilend.service.sponsorship_manager');
        $expiredSponseeOffers  = $sponsorshipRepository->findExpiredSponsorshipsSponsee();
        $expiredSponsorOffers  = $sponsorshipRepository->findExpiredSponsorshipsSponsor();
        $unusedSponseeOffers   = 0;
        $unusedSponsorOffers   = 0;
        $logger                = $this->getContainer()->get('monolog.logger.console');

        foreach ($expiredSponseeOffers as $expiredSponsorship) {
            $sponsorship = $sponsorshipRepository->find($expiredSponsorship['id']);
            try {
                $rewardCancelled = $sponsorshipManager->cancelSponseeReward($sponsorship);
                if (true === $rewardCancelled) {
                    $unusedSponseeOffers +=1;
                }
            } catch (\Exception $exception) {
                continue;
            }
        }
        $logger->info('Number of cancelled sponsee rewards: ' . $unusedSponseeOffers);

        foreach ($expiredSponsorOffers as $expiredSponsorship) {
            $sponsorship = $sponsorshipRepository->find($expiredSponsorship['id']);
            try {
                $rewardCancelled = $sponsorshipManager->cancelSponsorReward($sponsorship);
                if (true === $rewardCancelled) {
                    $unusedSponsorOffers +=1;
                }
            } catch (\Exception $exception) {
                continue;
            }
        }
        $logger->info('Number of cancelled sponsor rewards: ' . $unusedSponsorOffers);
    }

    private function checkUnilendPromotionalBalance()
    {
        $entityManager     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $currencyFormatter = $this->getContainer()->get('currency_formatter');
        $unilendWalletType = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendWallet     = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $unilendWalletType]);

        if ($unilendWallet->getAvailableBalance() <= 1000) {
            /** @var Settings $recipientSetting */
            $recipientSetting = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse notification solde unilend promotion']);
            $variables = [
                'balance' => $currencyFormatter->formatCurrency($unilendWallet->getAvailableBalance(), 'EUR')
            ];
            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-solde-promotion-faible', $variables);
            try {
                $message->setTo(explode(';', trim($recipientSetting->getValue())));
                $mailer = $this->getContainer()->get('mailer');
                $mailer->send($message);
            } catch (\Exception $exception) {
                $this->getContainer()->get('monolog.logger.console')->warning(
                    'Could not send email : notification-solde-promotion-faible - Exception: ' . $exception->getMessage(),
                    ['id_mail_template' => $message->getTemplateId(), 'email address' => explode(';', trim($recipientSetting->getValue())), 'class' => __CLASS__, 'function' => __FUNCTION__]
                );
            }
        }
    }
}
