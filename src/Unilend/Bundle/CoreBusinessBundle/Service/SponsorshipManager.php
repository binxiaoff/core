<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Sponsorship;
use Unilend\Bundle\CoreBusinessBundle\Entity\SponsorshipBlacklist;
use Unilend\Bundle\CoreBusinessBundle\Entity\SponsorshipCampaign;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class SponsorshipManager
{
    const UTM_SOURCE   = 'Parrainage';
    const UTM_MEDIUM   = 'lien';
    const UTM_CAMPAIGN = 'Parrainage';

    /** @var EntityManager  */
    private $entityManager;
    /** @var OperationManager  */
    private $operationManager;
    /** @var ClientStatusManager */
    private $clientStatusManager;
    /** @var \NumberFormatter */
    private $numberFormatter;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;
    /** @var LoggerInterface */
    private $logger;
    private $surl;
    private $furl;

    /**
     * @param EntityManager           $entityManager
     * @param OperationManager        $operationManager
     * @param ClientStatusManager     $clientStatusManager
     * @param \NumberFormatter        $numberFormatter
     * @param TemplateMessageProvider $messageProvider
     * @param \Swift_Mailer           $mailer
     * @param LoggerInterface         $logger
     * @param Packages                $assetsPackages
     * @param                         $schema
     * @param                         $frontHost
     */
    public function __construct(
        EntityManager $entityManager,
        OperationManager $operationManager,
        ClientStatusManager $clientStatusManager,
        \NumberFormatter $numberFormatter,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        LoggerInterface $logger,
        Packages $assetsPackages,
        $schema,
        $frontHost
    )
    {
        $this->operationManager    = $operationManager;
        $this->entityManager       = $entityManager;
        $this->clientStatusManager = $clientStatusManager;
        $this->numberFormatter     = $numberFormatter;
        $this->messageProvider     = $messageProvider;
        $this->mailer              = $mailer;
        $this->logger              = $logger;
        $this->surl                = $assetsPackages->getUrl('');
        $this->furl                = $schema . '://' . $frontHost;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param int       $amountSponsee
     * @param int       $amountSponsor
     * @param int       $maxNumberSponsee
     * @param int       $validityDays
     * @param null|int  $idCampaign
     *
     * @throws \Exception
     */
    public function saveSponsorshipCampaign(\DateTime $start, \DateTime $end, $amountSponsee, $amountSponsor, $maxNumberSponsee, $validityDays, $idCampaign = null)
    {
        if (null !== $idCampaign) {
            $campaign = $this->entityManager->getRepository('UnilendCoreBusinessBundle:SponsorshipCampaign')->find($idCampaign);
            if (null === $campaign) {
                throw new \Exception('The id does not match any campaign');
            }
            $campaign->setStatus(SponsorshipCampaign::STATUS_ARCHIVED);
            $this->entityManager->flush($campaign);
            $start = new \DateTime('NOW');
        }

        $validCampaigns = $this->entityManager->getRepository('UnilendCoreBusinessBundle:SponsorshipCampaign')->findBy(['status' => SponsorshipCampaign::STATUS_VALID]);

        foreach ($validCampaigns as $campaign) {
            if ($campaign->getEnd() > $start) {
                throw new \Exception('Campaign does overlap with exiting campaign');
            }
        }

        $newCampaign = new SponsorshipCampaign();
        $newCampaign->setStart($start)
            ->setEnd($end)
            ->setAmountSponsee($amountSponsee)
            ->setAmountSponsor($amountSponsor)
            ->setMaxNumberSponsee($maxNumberSponsee)
            ->setValidityDays($validityDays);

        $this->entityManager->persist($newCampaign);
        $this->entityManager->flush($newCampaign);
    }

    public function modifySponsorshipCampaign()
    {

    }

    /**
     * @return null|SponsorshipCampaign
     */
    public function getCurrentSponsorshipCampaign()
    {
        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:SponsorshipCampaign')->findCurrentCampaign();
    }

    /**
     * @param Clients $sponsee
     *
     * @return bool
     * @throws \Exception
     */
    public function attributeSponsorReward(Clients $sponsee)
    {
        $sponsorship = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship')->findOneBy(['idClientSponsee' => $sponsee]);
        if (null === $sponsorship) {
            throw new \Exception('Client ' . $sponsee->getIdClient() . ' has no sponsor');
        }

        if (false === $this->isEligibleForSponsorReward($sponsorship)) {
            throw new \Exception('Client ' . $sponsee->getIdClient() . ' is not eligible for sponsor reward');
        }

        $sponsorWallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($sponsorship->getIdClientSponsor(), WalletType::LENDER);
        if (null === $sponsorWallet) {
            throw new \Exception('Client ' . $sponsorship->getIdClientSponsor()->getIdClient() . ' has no lender wallet (only lenders can be sponsors)');
        }

        $this->operationManager->newSponsorReward($sponsorWallet, $sponsorship);
        $this->sendSponsorRewardEmail($sponsorship);

        return true;
    }

    /**
     * @param Clients $sponsee
     *
     * @throws \Exception
     */
    public function attributeSponseeReward(Clients $sponsee)
    {
        $sponsorship = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship')->findOneBy(['idClientSponsee' => $sponsee]);
        if (null === $sponsorship) {
            throw new \Exception('Client ' . $sponsee->getIdClient() . ' has no sponsor');
        }

        if (Sponsorship::STATUS_ONGOING != $sponsorship->getStatus()) {
            throw new \Exception('Sponsorship for client ' . $sponsee->getIdClient() . ' has already been paid');
        }

        $sponseeWallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($sponsee, WalletType::LENDER);
        if (null === $sponseeWallet) {
            throw new \Exception('Client ' . $sponsorship->getIdClientSponsee()->getIdClient() . ' has no lender wallet (only lenders can be sponsored)');
        }

        $paidReward = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->findBy(['idWalletCreditor' => $sponseeWallet, 'idSponsorship' => $sponsorship]);
        if (null !== $paidReward) {
            throw new \Exception('Client ' . $sponsorship->getIdClientSponsee()->getIdClient() . ' has already recieved the sponsee reward');
        }

        $this->operationManager->newSponseeReward($sponseeWallet, $sponsorship);
        $sponsorship->setStatus(Sponsorship::STATUS_SPONSEE_PAID);
        $this->entityManager->flush($sponsorship);

        if (false === $this->isClientBlacklistedForSponsorship($sponsorship)) {
            $this->sendSponseeRewardEmail($sponsorship);
        }
    }

    private function sendSponsorRewardEmail(Sponsorship $sponsorship)
    {

    }

    private function sendSponseeRewardEmail(Sponsorship $sponsorship)
    {

    }

    /**
     * @param Sponsorship $sponsorship
     *
     * @return bool
     */
    public function cancelSponsorReward(Sponsorship $sponsorship)
    {
        $operationRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $sponsorWallet       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($sponsorship->getIdClientSponsor(), WalletType::LENDER);
        $rewardOperation     = $operationRepository->findOneBy(['idSponsorship' => $sponsorship, 'idWalletCreditor' => $sponsorWallet]);
        $loansSinceReward    = $operationRepository->sumDebitOperationsByTypeSince($sponsorWallet, [OperationType::LENDER_LOAN], null, $rewardOperation->getAdded());

        if (
            $rewardOperation >= $loansSinceReward
            && $sponsorWallet->getCommittedBalance() < $sponsorship->getIdSponsorshipCampaign()->getAmountSponsor()
            && $sponsorWallet->getAvailableBalance() > $sponsorship->getIdSponsorshipCampaign()->getAmountSponsor()
        ) {
            $this->operationManager->cancelSponsorReward($sponsorWallet, $sponsorship);

            return true;
        }

        return false;
    }

    /**
     * @param Sponsorship $sponsorship
     *
     * @return bool
     */
    public function cancelSponseeReward(Sponsorship $sponsorship)
    {
        $operationRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $sponseeWallet       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($sponsorship->getIdClientSponsee(), WalletType::LENDER);
        $rewardOperation     = $operationRepository->findOneBy(['idSponsorship' => $sponsorship, 'idWalletCreditor' => $sponseeWallet]);
        $loansSinceReward    = $operationRepository->sumDebitOperationsByTypeSince($sponseeWallet, [OperationType::LENDER_LOAN], null, $rewardOperation->getAdded());

        if (
            $rewardOperation >= $loansSinceReward
            && $sponseeWallet->getCommittedBalance() < $sponsorship->getIdSponsorshipCampaign()->getAmountSponsor()
            && $sponseeWallet->getAvailableBalance() > $sponsorship->getIdSponsorshipCampaign()->getAmountSponsor()
        ){
            $this->operationManager->cancelSponseeReward($sponseeWallet, $sponsorship);

            return true;
        }

        return false;
    }

    /**
     * @param Clients                  $client
     * @param Users                    $user
     * @param SponsorshipCampaign|null $campaign
     */
    public function blacklistClientAsSponsor(Clients $client, Users $user, SponsorshipCampaign $campaign = null)
    {
        $blacklisted = new SponsorshipBlacklist();
        $blacklisted->setIdClient($client)
            ->setIdUser($user);

        if (null !== $campaign) {
            $blacklisted->setIdCampaign($campaign);
        }

        $this->entityManager->persist($blacklisted);
        $this->entityManager->flush($blacklisted);
    }

    public function sendSponsorshipInvitation()
    {

    }

    public function informSponsorAboutSponsee()
    {

    }

    public function sendInternalMaxSponseeNotification()
    {

    }

    /**
     * @param Clients $sponsee
     * @param         $sponsorCode
     */
    public function createSponsorship(Clients $sponsee, $sponsorCode)
    {
        $sponsor = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['sponsorCode' => $sponsorCode]);

        if ($this->clientStatusManager->hasBeenValidatedAtLeastOnce($sponsor)) {
            $campaign = $this->getCurrentSponsorshipCampaign();

            $sponsorship = new Sponsorship();
            $sponsorship->setIdClientSponsor($sponsor)
                ->setIdClientSponsee($sponsee)
                ->setIdSponsorshipCampaign($campaign)
                ->setStatus(Sponsorship::STATUS_ONGOING);

            $this->entityManager->persist($sponsorship);
            $this->entityManager->flush($sponsorship);
        }
    }

    /**
     * @param Sponsorship $sponsorship
     *
     * @return bool
     */
    public function isEligibleForSponsorReward(Sponsorship $sponsorship)
    {
        if ($this->isClientBlacklistedForSponsorship($sponsorship)) {
            return false;
        }

        if (Sponsorship::STATUS_SPONSOR_PAID == $sponsorship->getStatus()) {
            return false;
        }

        $walletRepository        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $bidRepository           = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');
        $sponseeWallet           = $walletRepository->getWalletByType($sponsorship->getIdClientSponsee(), WalletType::LENDER);
        $totalAmountAcceptedBids = $bidRepository->getSumBidsForLenderAndStatus($sponseeWallet, Bids::STATUS_BID_ACCEPTED);
        if ($totalAmountAcceptedBids <= $sponsorship->getIdSponsorshipCampaign()->getAmountSponsee()) {
            return false;
        }

        $operationRepository          = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $sponsorWallet                = $walletRepository->getWalletByType($sponsorship->getIdClientSponsor(), WalletType::LENDER);
        $amountAlreadyReceivedRewards = $operationRepository->getSumRewardAmountByCampaign(OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSOR, $sponsorship->getIdSponsorshipCampaign(), $sponsorWallet);

        if ($amountAlreadyReceivedRewards >= bcmul($sponsorship->getIdSponsorshipCampaign()->getAmountSponsor(), $sponsorship->getIdSponsorshipCampaign()->getMaxNumberSponsee(), 2)) {
            return false;
        }

        return true;
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    public function isEligibleForSponseeReward(Clients $client)
    {
        $sponsorship = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship')->findOneBy(['idClientSponsee' => $client]);
        if (null === $sponsorship) {
            return false;
        }

        if (Sponsorship::STATUS_ONGOING < $sponsorship->getStatus()) {
            return false;
        }

        return true;
    }

    /**
     * @param Sponsorship $sponsorship
     *
     * @return bool
     */
    public function isClientBlacklistedForSponsorship(Sponsorship $sponsorship)
    {
        $blacklistRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:SponsorshipBlacklist');
        $blacklist           = $blacklistRepository->findBlacklistForClient($sponsorship->getIdClientSponsor(), $sponsorship->getIdSponsorshipCampaign());

        return null !== $blacklist;
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    public function isClientCurrentlyBlacklisted(Clients $client)
    {
        $currentCampaign     = $this->getCurrentSponsorshipCampaign();
        $blacklistRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:SponsorshipBlacklist');
        $blacklist           = $blacklistRepository->findBlacklistForClient($client, $currentCampaign);

        return null !== $blacklist;
    }
}
