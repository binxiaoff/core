<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Repository\SponsorshipRepository;

class CheckSponsorshipRewardValidityCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('check:sponsorship_reward_validity')
            ->setDescription('Remove sponsorship rewards not used by lenders during a time period');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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

        $logger->info('Number of withdrawn sponsee rewards: ' . $unusedSponseeOffers);

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

        $logger->info('Number of withdrawn sponsor rewards: ' . $unusedSponsorOffers);
    }
}
