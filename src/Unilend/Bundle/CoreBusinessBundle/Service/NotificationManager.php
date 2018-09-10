<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, ClientsGestionMailsNotif, ClientsGestionNotifications, ClientsGestionTypeNotif, WalletBalanceHistory, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class NotificationManager
{
    /** @var MailerManager */
    private $mailerManager;
    /** @var  EntityManagerInterface */
    private $entityManager;
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;

    /**
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param EntityManagerInterface $entityManager
     * @param MailerManager          $mailerManager
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManagerInterface $entityManager,
        MailerManager $mailerManager
    )
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->mailerManager          = $mailerManager;
    }

    /**
     * @param int                       $notificationType
     * @param int                       $mailType
     * @param int                       $clientId
     * @param null|int                  $mailFunction
     * @param null|int                  $projectId
     * @param null|float                $amount
     * @param null|int                  $bidId
     * @param null|WalletBalanceHistory $walletBalanceHistory
     * @param null|int                  $loanId
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create(
        $notificationType,
        $mailType,
        $clientId,
        $mailFunction = null,
        $projectId = null,
        $amount = null,
        $bidId = null,
        WalletBalanceHistory $walletBalanceHistory = null,
        $loanId = null
    ) {
        /** @var \clients_gestion_notifications $notificationSettings */
        $notificationSettings = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');
        $notification         = $this->createNotification($notificationType, $clientId, $projectId, $amount, $bidId);

        if ($notificationSettings->getNotif($clientId, $mailType, 'uniquement_notif') == false) {
            if (
                $notificationSettings->getNotif($clientId, $mailType, 'immediatement')
                || false === $notificationSettings->exist(['id_client' => $clientId, 'id_notif'  => $mailType])
                && null !== $mailFunction && method_exists($this->mailerManager, $mailFunction)
            ) {
                $this->mailerManager->$mailFunction($notification);
                $sent = true;
            } else {
                $sent = false;
            }

            $this->createEmailNotification($mailType, $clientId, $notification->id_notification, $walletBalanceHistory, $projectId, $loanId, $sent);
        }
    }

    /**
     * @param int        $notificationType
     * @param int        $clientId
     * @param null|int   $projectId
     * @param null|float $amount
     * @param null|int   $bidId
     *
     * @return \notifications
     */
    public function createNotification($notificationType, $clientId, $projectId = null, $amount = null, $bidId = null)
    {
        /** @var \notifications $notification */
        $notification = $this->entityManagerSimulator->getRepository('notifications');
        $wallet       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($clientId, WalletType::LENDER);

        $lenderId = '';
        if (null !== $wallet) {
            $lenderId = $wallet->getId();
        }
        $notification->type       = $notificationType;
        $notification->id_lender  = $lenderId;
        $notification->id_project = $projectId;
        $notification->amount     = bcmul($amount, 100);
        $notification->id_bid     = $bidId;
        $notification->create();

        return $notification;
    }

    /**
     * @param int                       $mailType
     * @param int                       $clientId
     * @param int|null                  $notificationId
     * @param WalletBalanceHistory|null $walletBalanceHistory
     * @param int|null                  $projectId
     * @param int|null                  $loanId
     * @param bool                      $sent
     * @param \DateTime|null            $notificationDate
     *
     * @return ClientsGestionMailsNotif
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createEmailNotification(
        int $mailType,
        int $clientId,
        ?int $notificationId = null,
        ?WalletBalanceHistory $walletBalanceHistory = null,
        ?int $projectId = null,
        ?int $loanId = null,
        bool $sent = false,
        ?\DateTime $notificationDate = null
    ): ClientsGestionMailsNotif
    {
        $emailNotification = new ClientsGestionMailsNotif();
        $emailNotification
            ->setIdClient($clientId)
            ->setIdNotif($mailType)
            ->setDateNotif(new \DateTime('NOW'))
            ->setIdNotification($notificationId)
            ->setIdProject($projectId)
            ->setIdLoan($loanId);

        if ($notificationDate instanceof \DateTime) {
            $emailNotification->setDateNotif($notificationDate);
        }

        if (null !== $walletBalanceHistory) {
            $emailNotification->setIdWalletBalanceHistory($walletBalanceHistory);
        }

        if ($sent) {
            $emailNotification->setImmediatement(1);
        }

        $this->entityManager->persist($emailNotification);
        $this->entityManager->flush($emailNotification);

        return $emailNotification;
    }

    /**
     * @param Clients $client
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function generateDefaultNotificationSettings(Clients $client)
    {
        $allTypes = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsGestionTypeNotif')->findAll();

        /** @var ClientsGestionTypeNotif $type */
        foreach ($allTypes as $type) {
            $this->createMissingNotificationSettingWithDefaultValue($type, $client);
        }
    }

    /**
     * @param Clients $client
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function checkNotificationSettingsAndCreateDefaultIfMissing(Clients $client): void
    {
        $settings = $this->entityManagerSimulator->getRepository('clients_gestion_notifications')->getNotifs($client->getIdClient());

        if (empty($settings)) {
            $this->generateDefaultNotificationSettings($client);
            return;
        }

        $allTypes = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsGestionTypeNotif')->findAll();

        /** @var ClientsGestionTypeNotif $type */
        foreach ($allTypes as $type) {
            if (false === isset($settings[$type->getIdClientGestionTypeNotif()])) {
                $this->createMissingNotificationSettingWithDefaultValue($type, $client);
            }
        }
    }

    /**
     * @param ClientsGestionTypeNotif $type
     * @param Clients                 $client
     *
     * @return ClientsGestionNotifications
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createMissingNotificationSettingWithDefaultValue(ClientsGestionTypeNotif $type, Clients $client): ClientsGestionNotifications
    {
        $immediateSetting = in_array($type->getIdClientGestionTypeNotif(), ClientsGestionTypeNotif::TYPES_WITH_DEFAULT_SETTING_IMMEDIATE) ? 1 : 0;
        $dailySetting     = in_array($type->getIdClientGestionTypeNotif(), ClientsGestionTypeNotif::TYPES_WITH_DEFAULT_SETTING_DAILY) ? 1 : 0;
        $weeklySetting    = in_array($type->getIdClientGestionTypeNotif(), ClientsGestionTypeNotif::TYPES_WITH_DEFAULT_SETTING_WEEKLY) ? 1 : 0;
        $monthlySetting   = 0;
        $onlyNotification = 0;

        $setting = new ClientsGestionNotifications();
        $setting
            ->setIdClient($client->getIdClient())
            ->setIdNotif($type->getIdClientGestionTypeNotif())
            ->setImmediatement($immediateSetting)
            ->setQuotidienne($dailySetting)
            ->setHebdomadaire($weeklySetting)
            ->setMensuelle($monthlySetting)
            ->setUniquementNotif($onlyNotification);

        $this->entityManager->persist($setting);
        $this->entityManager->flush($setting);

        return $setting;
    }

    /**
     * @param \clients $client
     */
    public function deactivateAllNotificationSettings(\clients $client)
    {
        /** @var \clients_gestion_notifications $clientNotificationSettings */
        $clientNotificationSettings = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');

        foreach ($clientNotificationSettings->getNotifs($client->id_client) as $idNotification => $notification){
            $clientNotificationSettings->get(['id_notif' => $idNotification]);
            $clientNotificationSettings->immediatement    = 0;
            $clientNotificationSettings->quotidienne      = 0;
            $clientNotificationSettings->hebdomadaire     = 0;
            $clientNotificationSettings->mensuelle        = 0;
            $clientNotificationSettings->uniquement_notif = 0;
            $clientNotificationSettings->update(['id_notif' => $clientNotificationSettings->id_notif]);
        }
    }

    /**
     * Method placed here in order to reduce use of EntityManagerSimulator
     * in new code (which will reduce migration cost)
     *
     * @param int    $clientId
     * @param string $type
     * @param string $frequency
     *
     * @return bool
     */
    public function getNotif(int $clientId, string $type, string $frequency): bool
    {
        /** @var \clients_gestion_notifications $clientNotifications */
        $clientNotifications = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');

        return $clientNotifications->getNotif($clientId, $type, $frequency);
    }
}
