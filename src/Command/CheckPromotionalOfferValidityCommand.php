<?php

namespace Unilend\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Entity\{OffresBienvenuesDetails, Operation, OperationSubType, OperationType, Settings, Sponsorship, SponsorshipCampaign, Wallet, WalletType};
use Unilend\Repository\SponsorshipRepository;

class CheckPromotionalOfferValidityCommand extends ContainerAwareCommand
{
    const PROMOTIONAL_WALLET_BALANCE_WARNING_LIMIT = 1000;

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

        $this->archivePastSponsorshipCampaigns();

        $this->checkUnilendPromotionalBalance();
    }

    private function cancelWelcomeOffers()
    {
        $entityManager               = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationRepository         = $entityManager->getRepository(Operation::class);
        $operationManager            = $this->getContainer()->get('unilend.service.operation_manager');
        $logger                      = $this->getContainer()->get('monolog.logger.console');
        $validitySetting             = $entityManager->getRepository(Settings::class)->findOneBy(['type' => 'Durée validité Offre de bienvenue']);
        $dateLimit                   = new \DateTime('NOW - ' . $validitySetting->getValue() . ' DAYS');
        $numberOfUnusedWelcomeOffers = 0;

        /** @var OffresBienvenuesDetails $welcomeOffer */
        foreach ($entityManager->getRepository(OffresBienvenuesDetails::class)->findUnusedWelcomeOffers($dateLimit) as $welcomeOffer) {
            $wallet                    = $entityManager->getRepository(Wallet::class)->getWalletByType($welcomeOffer->getIdClient(), WalletType::LENDER);
            $sumLoans                  = $operationRepository->sumDebitOperationsByTypeUntil($wallet, [OperationType::LENDER_LOAN]);
            $sumWelcomeOffers          = $operationRepository->sumCreditOperationsByTypeUntil($wallet, [OperationType::UNILEND_PROMOTIONAL_OPERATION], [OperationSubType::UNILEND_PROMOTIONAL_OPERATION_WELCOME_OFFER]);
            $sumCancelledWelcomeOffers = $operationRepository->sumDebitOperationsByTypeUntil($wallet, [OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL], [OperationSubType::UNILEND_PROMOTIONAL_OPERATION_CANCEL_WELCOME_OFFER]);
            $totalWelcomeOffer         = round(bcsub($sumWelcomeOffers, $sumCancelledWelcomeOffers, 4), 2);

            if ($sumLoans < $totalWelcomeOffer) {
                $welcomeOffer->setStatus(OffresBienvenuesDetails::STATUS_CANCELED);
                try {
                    $operationManager->cancelWelcomeOffer($wallet, $welcomeOffer);
                    $numberOfUnusedWelcomeOffers +=1;
                } catch (\Exception $exception) {
                    continue;
                }
            }
        }
        $entityManager->flush();

        $logger->info('Number of cancelled welcome offers: ' . $numberOfUnusedWelcomeOffers);
    }

    private function cancelSponsorshipOffers()
    {
        /** @var SponsorshipRepository $sponsorshipRepository */
        $sponsorshipRepository = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository(Sponsorship::class);
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
        $unilendWalletType = $entityManager->getRepository(WalletType::class)->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
        $unilendWallet     = $entityManager->getRepository(Wallet::class)->findOneBy(['idType' => $unilendWalletType]);

        if ($unilendWallet->getAvailableBalance() <= self::PROMOTIONAL_WALLET_BALANCE_WARNING_LIMIT) {
            /** @var Settings $recipientSetting */
            $recipientSetting = $entityManager->getRepository(Settings::class)->findOneBy(['type' => 'Adresse notification solde unilend promotion']);
            $variables = [
                'balance' => $currencyFormatter->formatCurrency($unilendWallet->getAvailableBalance(), 'EUR')
            ];

            /** @var \Unilend\SwiftMailer\TemplateMessage $message */
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

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function archivePastSponsorshipCampaigns()
    {
        $entityManager   = $this->getContainer()->get('doctrine.orm.entity_manager');
        $activeCampaigns = $entityManager->getRepository(SponsorshipCampaign::class)->findBy(['status' => SponsorshipCampaign::STATUS_VALID], ['start' => 'ASC']);
        $now             = new \DateTime('NOW');

        /** @var SponsorshipCampaign $campaign */
        foreach ($activeCampaigns as $campaign) {
            if ($campaign->getEnd()->format('Y-m-d') < $now->format('Y-m-d')) {
                $campaign->setStatus(SponsorshipCampaign::STATUS_ARCHIVED);
                $entityManager->flush($campaign);
            }
        }
    }
}
