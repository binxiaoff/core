<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Sponsorship;
use Unilend\Bundle\CoreBusinessBundle\Entity\SponsorshipCampaign;
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
        \NumberFormatter $numberFormatter,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        LoggerInterface $logger,
        Packages $assetsPackages,
        $schema,
        $frontHost
    ) {
        $this->operationManager = $operationManager;
        $this->entityManager    = $entityManager;
        $this->numberFormatter  = $numberFormatter;
        $this->messageProvider  = $messageProvider;
        $this->mailer           = $mailer;
        $this->logger           = $logger;
        $this->surl             = $assetsPackages->getUrl('');
        $this->furl             = $schema . '://' . $frontHost;
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

    public function attributeSponsorReward()
    {

    }

    public function attributeSponseeReward()
    {

    }

    private function sendSponsorRewardEmail()
    {

    }

    private function sendSponseeRewardEmail()
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
        $sponsor  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['sponsorCode' => $sponsorCode]);
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
