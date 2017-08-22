<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Sponsorship;
use Unilend\Bundle\CoreBusinessBundle\Entity\SponsorshipCampaign;
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
    ) {
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

    public function createSponsorshipCampaign()
    {

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
        if (false === $this->isEligibleForSponsorReward($sponsee)) {
            return false;
        }

        $sponsorship = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship')->findOneBy(['idClientSponsee' => $sponsee]);
        if (null === $sponsorship) {
            throw new \Exception('Client ' . $sponsee->getIdClient() . ' has no sponsor');
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

        $this->operationManager->newSponseeReward($sponseeWallet, $sponsorship);
        $this->sendSponseeRewardEmail($sponsorship);
    }

    private function sendSponsorRewardEmail(Sponsorship $sponsorship)
    {

    }

    private function sendSponseeRewardEmail(Sponsorship $sponsorship)
    {

    }

    public function cancelSponsorReward()
    {

    }

    public function cancelSponseeReward()
    {

    }

    public function blacklistClientAsSponsor()
    {

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
     * @param Clients $sponsee
     *
     * @return bool
     */
    public function isEligibleForSponsorReward(Clients $sponsee)
    {
        $sponsorship = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship')->findOneBy(['idClientSponsee' => $sponsee]);
        if (null === $sponsorship) {
            return false;
        }

        if (in_array($sponsorship->getStatus(), [Sponsorship::STATUS_SPONSOR_PAID, Sponsorship::STATUS_SPONSOR_EXPIRED])) {
            return false;
        }

        $sponseeWallet           = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($sponsee, WalletType::LENDER);
        $totalAmountAcceptedBids = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->getSumBidsForLenderAndStatus($sponseeWallet, Bids::STATUS_BID_ACCEPTED);
        if ($totalAmountAcceptedBids <= $sponsorship->getIdSponsorshipCampaign()->getAmountSponsee()) {
            return false;
        }

        $amountAlreadyReceivedRewards = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')
            ->getSumRewardAmountByCampaign(OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSOR, $sponsorship->getIdSponsorshipCampaign());
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
}
