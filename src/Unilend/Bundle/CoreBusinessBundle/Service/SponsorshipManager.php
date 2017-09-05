<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Sponsorship;
use Unilend\Bundle\CoreBusinessBundle\Entity\SponsorshipBlacklist;
use Unilend\Bundle\CoreBusinessBundle\Entity\SponsorshipCampaign;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class SponsorshipManager
{
    const UTM_SOURCE      = 'Parrainage';
    const UTM_MEDIUM      = 'lien';
    const UTM_CAMPAIGN    = 'Parrainage';

    const SPONSORSHIP_MANAGER_EXCEPTION_CODE = 1;

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
    /** @var RouterInterface */
    private $router;
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
     * @param RouterInterface         $router
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
        RouterInterface $router,
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
        $this->router              = $router;
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
     * @return bool
     * @throws \Exception
     */
    public function saveSponsorshipCampaign(\DateTime $start, \DateTime $end, $amountSponsee, $amountSponsor, $maxNumberSponsee, $validityDays, $idCampaign = null)
    {
        $today = new \DateTime('NOW');
        $today->setTime(0, 0, 0);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            if (null !== $idCampaign) {
                $campaign = $this->entityManager->getRepository('UnilendCoreBusinessBundle:SponsorshipCampaign')->find($idCampaign);
                if (null === $campaign) {
                    throw new \Exception('The id does not match any campaign', self::SPONSORSHIP_MANAGER_EXCEPTION_CODE);
                }
                $campaign
                    ->setStatus(SponsorshipCampaign::STATUS_ARCHIVED)
                    ->setEnd($today);
                $this->entityManager->flush($campaign);

                $start = $today->getTimestamp() < $start->getTimestamp() ? $today : $start;
            }

            if ($today->getTimestamp() > $start->getTimestamp()) {
                throw new \Exception('Campaign start can not be in the past', self::SPONSORSHIP_MANAGER_EXCEPTION_CODE);
            }

            $validCampaigns = $this->entityManager->getRepository('UnilendCoreBusinessBundle:SponsorshipCampaign')->findBy(['status' => SponsorshipCampaign::STATUS_VALID]);

            foreach ($validCampaigns as $campaign) {
                if ($campaign->getEnd()->getTimestamp() > $start->getTimestamp()) {
                    throw new \Exception('Campaign does overlap with exiting campaign. Id campaign = ' . $campaign->getId(), self::SPONSORSHIP_MANAGER_EXCEPTION_CODE);
                }
            }

            $newCampaign = new SponsorshipCampaign();
            $newCampaign->setStart($start)
                ->setEnd($end)
                ->setAmountSponsee($amountSponsee)
                ->setAmountSponsor($amountSponsor)
                ->setMaxNumberSponsee($maxNumberSponsee)
                ->setValidityDays($validityDays)
                ->setStatus(SponsorshipCampaign::STATUS_VALID);

            $this->entityManager->persist($newCampaign);
            $this->entityManager->flush($newCampaign);

            $this->entityManager->getConnection()->commit();

            return true;
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw $exception;
        }
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
        $sponsorship = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship')->findOneBy(['idClientSponsor' => $sponsee]);
        if (null === $sponsorship) {
            throw new \Exception('Client ' . $sponsee->getIdClient() . ' has no sponsee', self::SPONSORSHIP_MANAGER_EXCEPTION_CODE);
        }

        $eligibility = $this->isEligibleForSponsorReward($sponsorship);
        if (false === $eligibility['isEligible']) {
            throw new \Exception('Client ' . $sponsee->getIdClient() . ' is not eligible for sponsor reward. Reason : ' . $eligibility['reason'], self::SPONSORSHIP_MANAGER_EXCEPTION_CODE);
        }

        $sponsorWallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($sponsorship->getIdClientSponsor(), WalletType::LENDER);
        if (null === $sponsorWallet) {
            throw new \Exception('Client ' . $sponsorship->getIdClientSponsor()->getIdClient() . ' has no lender wallet (only lenders can be sponsors)', self::SPONSORSHIP_MANAGER_EXCEPTION_CODE);
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
            throw new \Exception('Client ' . $sponsee->getIdClient() . ' has no sponsor', self::SPONSORSHIP_MANAGER_EXCEPTION_CODE);
        }

        if (Sponsorship::STATUS_ONGOING != $sponsorship->getStatus()) {
            throw new \Exception('Sponsorship for client ' . $sponsee->getIdClient() . ' has already been paid', self::SPONSORSHIP_MANAGER_EXCEPTION_CODE);
        }

        $sponseeWallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($sponsee, WalletType::LENDER);
        if (null === $sponseeWallet) {
            throw new \Exception('Client ' . $sponsorship->getIdClientSponsee()->getIdClient() . ' has no lender wallet (only lenders can be sponsored)', self::SPONSORSHIP_MANAGER_EXCEPTION_CODE);
        }

        $paidReward = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->findBy(['idWalletCreditor' => $sponseeWallet, 'idSponsorship' => $sponsorship]);
        if (false === empty($paidReward)) {
            throw new \Exception('Client ' . $sponsorship->getIdClientSponsee()->getIdClient() . ' has already received the sponsee reward', self::SPONSORSHIP_MANAGER_EXCEPTION_CODE);
        }

        $this->operationManager->newSponseeReward($sponseeWallet, $sponsorship);
        $sponsorship->setStatus(Sponsorship::STATUS_SPONSEE_PAID);
        $this->entityManager->flush($sponsorship);
        $this->sendSponseeRewardEmail($sponsorship);

        if (false === $this->isClientBlacklistedForSponsorship($sponsorship)) {
            $this->informSponsorAboutSponsee($sponsorship);
        }
    }

    /**
     * @param Sponsorship $sponsorship
     */
    private function sendSponsorRewardEmail(Sponsorship $sponsorship)
    {
        $varMail = [
            'surl'               => $this->surl,
            'url'                => $this->furl,
            'sponsor_first_name' => $sponsorship->getIdClientSponsor()->getPrenom(),
            'sponsee_first_name' => $sponsorship->getIdClientSponsee()->getPrenom(),
            'sponsee_last_name'  => $sponsorship->getIdClientSponsee()->getNom(),
            'amount'             => $this->numberFormatter->format((float)$sponsorship->getIdSponsorshipCampaign()->getAmountSponsor()),
            'lien_fb'            => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Facebook'])->getValue(),
            'lien_tw'            => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Twitter'])->getValue(),
            'year'               => date('Y')
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('parrainage-versement-prime-parrain', $varMail);
        try {
            $message->setTo($sponsorship->getIdClientSponsor()->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning('Could not send email: parrainage-versement-prime-parrain - Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'id_client'        => $sponsorship->getIdClientSponsor()->getIdClient(),
                'class'            => __CLASS__,
                'function'         => __FUNCTION__
            ]);
        }
    }

    /**
     * @param Sponsorship $sponsorship
     */
    private function sendSponseeRewardEmail(Sponsorship $sponsorship)
    {
        $varMail = [
            'surl'               => $this->surl,
            'url'                => $this->furl,
            'sponsee_first_name' => $sponsorship->getIdClientSponsee()->getPrenom(),
            'amount'             => $this->numberFormatter->format((float)$sponsorship->getIdSponsorshipCampaign()->getAmountSponsee()),
            'lien_fb'            => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Facebook'])->getValue(),
            'lien_tw'            => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Twitter'])->getValue(),
            'year'               => date('Y')
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('parrainage-versement-prime-filleul', $varMail);
        try {
            $message->setTo($sponsorship->getIdClientSponsee()->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning('Could not send email: parrainage-versement-prime-filleul - Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'id_client'        => $sponsorship->getIdClientSponsee()->getIdClient(),
                'class'            => __CLASS__,
                'function'         => __FUNCTION__
            ]);
        }

    }

    /**
     * @param Sponsorship $sponsorship
     *
     * @return bool
     */
    public function cancelSponsorReward(Sponsorship $sponsorship)
    {
        $sponsorWallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($sponsorship->getIdClientSponsor(), WalletType::LENDER);
        $rewardCancelSubType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OperationSubType')
            ->findOneBy(['label' => OperationSubType::UNILEND_PROMOTIONAL_OPERATION_CANCEL_SPONSORSHIP_REWARD_SPONSOR]);

        if (null === $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy([
                'idWalletDebtor' => $sponsorWallet,
                'idSponsorship'  => $sponsorship->getId(),
                'idSubType'      => $rewardCancelSubType
            ])
            && 0 == $this->getUnusedSponsorRewardAmountFromSponsorship($sponsorship)
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
        $sponseeWallet       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($sponsorship->getIdClientSponsee(), WalletType::LENDER);
        $rewardCancelSubType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OperationSubType')
            ->findOneBy(['label' => OperationSubType::UNILEND_PROMOTIONAL_OPERATION_CANCEL_SPONSORSHIP_REWARD_SPONSEE]);

        if (
            null === $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')
                ->findOneBy(['idWalletDebtor' => $sponseeWallet, 'idSponsorship' => $sponsorship->getId(), 'idSubType' => $rewardCancelSubType])
            && 0 == $this->getUnusedSponseeRewardAmount($sponsorship->getIdClientSponsee())
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

    /**
     * @param Clients $sponsor
     * @param string  $email
     * @param string  $sponseeNames
     * @param string  $message
     */
    public function sendSponsorshipInvitation(Clients $sponsor, $email, $sponseeNames, $message)
    {
        $currentCampaign = $this->getCurrentSponsorshipCampaign();
        $mailTemplate = $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates')
            ->findOneBy(['status' => \mail_templates::STATUS_ACTIVE, 'type' => 'parrainage-invitation-filleul']);

        $varMail = [
            'surl'               => $this->surl,
            'url'                => $this->furl,
            'sponsor_first_name' => $sponsor->getPrenom(),
            'sponsor_last_name'  => $sponsor->getNom(),
            'sponsee_names'      => $sponseeNames,
            'sponsor_message'    => $message,
            'link'               => $this->router->generate('lender_sponsorship_redirect', ['sponsorCode' => $sponsor->getSponsorCode()], UrlGeneratorInterface::ABSOLUTE_URL),
            'amount'             => $this->numberFormatter->format((float) $currentCampaign->getAmountSponsee()),
            'lien_fb'            => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Facebook'])->getValue(),
            'lien_tw'            => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Twitter'])->getValue(),
            'year'               => date('Y')
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('parrainage-invitation-filleul', $varMail);
        try {
            $message->setTo($email);
            $message->setSubject(str_replace('[EMV DYN]sponsor_names[EMV DYN]',  $sponsor->getPrenom() . ' ' . $sponsor->getNom(), $mailTemplate->getSubject()));
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning('Could not send email: parrainage-invitation-filleul - Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'id_client'        => $sponsor->getIdClient(),
                'class'            => __CLASS__,
                'function'         => __FUNCTION__
            ]);
        }
    }

    /**
     * @param Sponsorship $sponsorship
     */
    private function informSponsorAboutSponsee(Sponsorship $sponsorship)
    {
        $varMail = [
            'surl'               => $this->surl,
            'url'                => $this->furl,
            'sponsor_first_name' => $sponsorship->getIdClientSponsor()->getPrenom(),
            'sponsee_first_name' => $sponsorship->getIdClientSponsee()->getPrenom(),
            'sponsee_last_name'  => $sponsorship->getIdClientSponsee()->getNom(),
            'lien_fb'            => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Facebook'])->getValue(),
            'lien_tw'            => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Twitter'])->getValue(),
            'year'               => date('Y')
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('parrainage-confirmation-validation-filleul', $varMail);
        try {
            $message->setTo($sponsorship->getIdClientSponsor()->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning('Could not send email: parrainage-confirmation-validation-filleul - Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'id_client'        => $sponsorship->getIdClientSponsor()->getIdClient(),
                'class'            => __CLASS__,
                'function'         => __FUNCTION__
            ]);
        }
    }

    /**
     * @param Clients $sponsor
     */
    private function sendMaxSponseeNotification(Clients $sponsor)
    {
        $currentCampaign = $this->getCurrentSponsorshipCampaign();

        $varMail = [
            'surl'               => $this->surl,
            'url'                => $this->furl,
            'sponsor_first_name' => $sponsor->getPrenom(),
            'end_campaign'       => $currentCampaign->getEnd()->format('d/m/Y'),
            'lien_fb'            => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Facebook'])->getValue(),
            'lien_tw'            => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Twitter'])->getValue(),
            'year'               => date('Y')
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('parrainage-plafond-filleuls', $varMail);
        try {
            $message->setTo($sponsor->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning('parrainage-plafond-filleuls - Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'id_client'        => $sponsor->getIdClient(),
                'class'            => __CLASS__,
                'function'         => __FUNCTION__
            ]);
        }
    }

    /**
     * @param Clients $sponsor
     */
    private function sendInternalMaxSponseeNotification(Clients $sponsor)
    {
        $varMail = [
            'surl'               => $this->surl,
            'url'                => $this->furl,
            'sponsor_first_name' => $sponsor->getPrenom(),
            'sponsor_last_name'  => $sponsor->getNom(),
            'sponsor_id_client'  => $sponsor->getIdClient(),
            'year'               => date('Y')
        ];

        $setting = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse notification solde unilend promotion']);

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('notification-parrainage-plafond-filleuls', $varMail);
        try {
            $message->setTo(explode(';', trim($setting->getValue())));
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning('notification-parrainage-plafond-filleuls - Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'class'            => __CLASS__,
                'function'         => __FUNCTION__
            ]);
        }
    }

    /**
     * @param Clients $sponsee
     * @param         $sponsorCode
     */
    public function createSponsorship(Clients $sponsee, $sponsorCode, SponsorshipCampaign $campaign = null)
    {
        $sponsor = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findOneBy(['sponsorCode' => $sponsorCode]);

        if ($this->clientStatusManager->hasBeenValidatedAtLeastOnce($sponsor)) {
            if (null === $campaign) {
                $campaign = $this->getCurrentSponsorshipCampaign();
            }

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
     * @return array
     */
    private function isEligibleForSponsorReward(Sponsorship $sponsorship)
    {
        if ($this->isClientBlacklistedForSponsorship($sponsorship)) {
            return ['isEligible' => false, 'reason' => 'Client is blacklisted'];
        }

        if (Sponsorship::STATUS_SPONSOR_PAID == $sponsorship->getStatus()) {
            return ['isEligible' => false, 'reason' => 'Sponsorship status is paid to sponsor'];
        }

        $walletRepository        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $bidRepository           = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');
        $sponseeWallet           = $walletRepository->getWalletByType($sponsorship->getIdClientSponsee(), WalletType::LENDER);
        $totalAmountAcceptedBids = $bidRepository->getSumBidsForLenderAndStatus($sponseeWallet, Bids::STATUS_BID_ACCEPTED);

        if ($totalAmountAcceptedBids <= $sponsorship->getIdSponsorshipCampaign()->getAmountSponsee()) {
            return ['isEligible' => false, 'reason' => 'Sponsee has not enough accepted bids'];
        }

        if ($this->hasReachedMaxAmountSponsee($sponsorship)) {
            return ['isEligible' => false, 'reason' => 'Client has already too many sponsees'];
        }

        return ['isEligible' => true];
    }

    /**
     * @param Sponsorship $sponsorship
     *
     * @return bool
     */
    public function hasReachedMaxAmountSponsee(Sponsorship $sponsorship)
    {
        $walletRepository             = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $operationRepository          = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $sponsorWallet                = $walletRepository->getWalletByType($sponsorship->getIdClientSponsor(), WalletType::LENDER);
        $amountAlreadyReceivedRewards = $operationRepository->getSumRewardAmountByCampaign(OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSOR, $sponsorship->getIdSponsorshipCampaign(), $sponsorWallet);

        if ($amountAlreadyReceivedRewards >= bcmul($sponsorship->getIdSponsorshipCampaign()->getAmountSponsor(), $sponsorship->getIdSponsorshipCampaign()->getMaxNumberSponsee(), 2)) {
            $this->sendMaxSponseeNotification($sponsorship->getIdClientSponsor());
            $this->sendInternalMaxSponseeNotification($sponsorship->getIdClientSponsor());

            return true;
        }

        return false;
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

    /**
     * @param Clients $client
     *
     * @return float
     */
    public function getUnusedSponseeRewardAmount(Clients $client)
    {
        $operationRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $sponseeWallet       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        $reward              = $operationRepository->sumCreditOperationsByTypeUntil($sponseeWallet, [OperationType::UNILEND_PROMOTIONAL_OPERATION], [OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSEE]);
        $rewardCancel        = $operationRepository->sumDebitOperationsByTypeUntil($sponseeWallet, [OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL], [OperationSubType::UNILEND_PROMOTIONAL_OPERATION_CANCEL_SPONSORSHIP_REWARD_SPONSEE]);
        $remainingReward     = bcsub($reward, $rewardCancel, 4);
        $loans               = $operationRepository->sumDebitOperationsByTypeSince($sponseeWallet, [OperationType::LENDER_LOAN]);

        $unusedAmount = round(bcsub($remainingReward, $loans, 4), 2);
        if ($unusedAmount <= 0 ) {
            return 0;
        }

        return $unusedAmount;
    }

    /**
     * @param Clients $client
     * @param null    $sponsorship
     * @param bool    $allReward
     *
     * @return float
     */
    public function getUnusedSponsorRewardAmount(Clients $client)
    {
        $unusedAmount        = 0;
        $operationRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $sponsorWallet       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        $rewardSubType       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OperationSubType')->findOneBy(['label' => OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSOR]);
        $reward              = $operationRepository->sumCreditOperationsByTypeUntil($sponsorWallet, [OperationType::UNILEND_PROMOTIONAL_OPERATION], [OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSOR]);
        $rewardCancel        = $operationRepository->sumDebitOperationsByTypeUntil($sponsorWallet, [OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL], [OperationSubType::UNILEND_PROMOTIONAL_OPERATION_CANCEL_SPONSORSHIP_REWARD_SPONSOR]);
        $remainingReward     = bcsub($reward, $rewardCancel, 4);
        $firstReward         = $operationRepository->findOneBy(['idWalletCreditor' => $sponsorWallet, 'idSubType' => $rewardSubType], ['added' => 'ASC']);
        if (null !== $firstReward) {
            $sumLoansSinceFirstReward = $operationRepository->sumDebitOperationsByTypeSince($sponsorWallet, [OperationType::LENDER_LOAN], null, $firstReward->getAdded());

            if ($sumLoansSinceFirstReward < $remainingReward) {
                $unusedAmount = round(bcsub($remainingReward, $sumLoansSinceFirstReward, 4));
            } else {
                $allSponsorships = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship')->findBy(['idClientSponsor' => $client]);
                foreach ($allSponsorships as $sponsorship) {
                    $unusedAmount = round(bcadd($unusedAmount, $this->getUnusedSponsorRewardAmountFromSponsorship($sponsorship), 4), 2);
                }
            }
        }

        if ($unusedAmount <= 0) {
            return 0;
        }

        return $unusedAmount;
    }

    /**
     * @param Sponsorship $sponsorship
     *
     * @return float
     */
    public function getUnusedSponsorRewardAmountFromSponsorship(Sponsorship $sponsorship)
    {
        $operationRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $sponsorWallet       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($sponsorship->getIdClientSponsor(), WalletType::LENDER);
        $rewardSubType       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OperationSubType')->findOneBy(['label' => OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSOR]);
        $rewardCancelSubType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OperationSubType')->findOneBy(['label' => OperationSubType::UNILEND_PROMOTIONAL_OPERATION_CANCEL_SPONSORSHIP_REWARD_SPONSOR]);

        $reward              = $operationRepository->findOneBy(['idWalletCreditor' => $sponsorWallet, 'idSubType' => $rewardSubType, 'idSponsorship' => $sponsorship]);
        $rewardCancel        = $operationRepository->findOneBy(['idWalletCreditor' => $sponsorWallet, 'idSubType' => $rewardCancelSubType, 'idSponsorship' => $sponsorship]);
        $amountReward        = null !== $reward ? $reward->getAmount() : 0;
        $amountRewardCancel  = null !== $rewardCancel ? $rewardCancel->getAmount() : 0;
        $remainingReward     = bcsub($amountReward, $amountRewardCancel, 4);
        $sumLoansSinceReward = $operationRepository->sumDebitOperationsByTypeSince($sponsorWallet, [OperationType::LENDER_LOAN], null, $reward->getAdded());

        $unusedAmount = round(bcsub($remainingReward, $sumLoansSinceReward, 4), 2);
        if ($unusedAmount <= 0 ) {
            return 0;
        }

        return $unusedAmount;
    }
}
