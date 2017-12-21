<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Attachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Notifications;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Repository\NotificationsRepository;
use Unilend\Bundle\CoreBusinessBundle\Repository\WalletRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\MailerManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\librairies\CacheKeys;

class ProjectsPublishCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('projects:publish')
            ->setDescription('Finds the projects to be funded and publish them')
            ->setHelp(<<<EOF
The <info>projects:publish</info> command finds the projects to be funded and publishes them.
<info>php bin/console publish:projects</info>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $oLogger = $this->getContainer()->get('monolog.logger.console');
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \projects $oProject */
        $oProject = $entityManagerSimulator->getRepository('projects');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
        $oProjectManager = $this->getContainer()->get('unilend.service.project_manager');
        /** @var bool $bHasProjectPublished */
        $bHasProjectPublished = false;

        // One project each execution, to avoid the memory issue.
        $aProjectToFund = $oProject->selectProjectsByStatus([\projects_status::AUTO_BID_PLACED], "AND p.date_publication <= NOW()", [], '', 1, false);
        $oLogger->info('Number of projects to publish: ' . count($aProjectToFund), ['class' => __CLASS__, 'function' => __FUNCTION__]);

        foreach ($aProjectToFund as $aProject) {
            if ($oProject->get($aProject['id_project'])) {
                $oLogger->info('Publishing the project ' . $aProject['id_project'], ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $aProject['id_project']]);

                $bHasProjectPublished = true;
                $oProjectManager->publish($oProject);

                if ($oProjectManager->isFunded($oProject)) {
                    /** @var MailerManager $mailerManager */
                    $mailerManager = $this->getContainer()->get('unilend.service.email_manager');
                    $mailerManager->sendFundedToBorrower($oProject);
                }

                $this->zipProjectAttachments($oProject, $entityManagerSimulator);

                if (false === $oProjectManager->isRateMinReached($oProject)) {
                    $this->sendNewProjectEmail($oProject, $entityManagerSimulator);
                }
            }
        }

        if ($bHasProjectPublished) {
            /** @var \Cache\Adapter\Memcache\MemcacheCachePool $oCachePool */
            $oCachePool = $this->getContainer()->get('memcache.default');
            $oCachePool->deleteItem(CacheKeys::LIST_PROJECTS);
        }
    }

    /**
     * @param \projects              $project
     * @param EntityManagerSimulator $entityManagerSimulator
     */
    private function zipProjectAttachments(\projects $project, EntityManagerSimulator $entityManagerSimulator)
    {
        /** @var \companies $companies */
        $companies = $entityManagerSimulator->getRepository('companies');
        $companies->get($project->id_company, 'id_company');

        $sPathNoZip = $this->getContainer()->getParameter('path.sftp') . 'groupama_nozip/';
        $sPath      = $this->getContainer()->getParameter('path.sftp') . 'groupama/';

        if (false === is_dir($sPath)) {
            mkdir($sPath);
        }

        if (false === is_dir($sPathNoZip)) {
            mkdir($sPathNoZip);
        }

        if (false === is_dir($sPathNoZip . $companies->siren)) {
            mkdir($sPathNoZip . $companies->siren);
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $projectEntity = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($project->id_project);
        $attachments   = $projectEntity->getAttachments();

        foreach ($attachments as $projectAttachment) {
            $attachment = $projectAttachment->getAttachment();

            switch ($attachment->getType()->getId()) {
                case AttachmentType::CNI_PASSPORTE_DIRIGEANT:
                    $prefix = 'CNI-#';
                    break;
                case AttachmentType::CNI_PASSPORTE_VERSO:
                    $prefix = 'CNI-VERSO-#';
                    break;
                case AttachmentType::KBIS:
                    $prefix = 'KBIS-#';
                    break;
                case AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_1:
                    $prefix = 'CNI-25-1-#';
                    break;
                case AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_2:
                    $prefix = 'CNI-25-2-#';
                    break;
                case AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_3:
                    $prefix = 'CNI-25-3-#';
                    break;
                case AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_VERSO_1:
                    $prefix = 'CNI-25-1-VERSO-#';
                    break;
                case AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_VERSO_2:
                    $prefix = 'CNI-25-2-VERSO-#';
                    break;
                case AttachmentType::CNI_BENEFICIAIRE_EFFECTIF_VERSO_3:
                    $prefix = 'CNI-25-3-VERSO-#';
                    break;
                default:
                    continue 2;
                    break;
            }
            $this->copyAttachment($attachment, $prefix, $companies->siren, $sPathNoZip);
        }

        $zip = new \ZipArchive();
        if (is_dir($sPathNoZip . $companies->siren)) {
            if ($zip->open($sPath . $companies->siren . '.zip', \ZipArchive::CREATE) == true) {
                $aFiles = scandir($sPathNoZip . $companies->siren);
                unset($aFiles[0], $aFiles[1]);
                foreach ($aFiles as $f) {
                    $zip->addFile($sPathNoZip . $companies->siren . '/' . $f, $f);
                }
                $zip->close();
            }
        }

        $this->deleteOldFiles();
    }

    /**
     * @param Attachment $attachment
     * @param string     $sPrefix
     * @param string     $siren
     * @param string     $pathNoZip
     */
    private function copyAttachment(Attachment $attachment, $sPrefix, $siren, $pathNoZip)
    {
        $attachmentManager = $this->getContainer()->get('unilend.service.attachment_manager');
        $fullPath          = $attachmentManager->getFullPath($attachment);
        if (file_exists($fullPath)) {
            $pathInfo  = pathinfo($fullPath);
            $extension = isset($pathInfo['extension']) ? $pathInfo['extension'] : '';
            $newName   = $sPrefix . $siren . '.' . $extension;

            copy($fullPath, $pathNoZip . $siren . '/' . $newName);
        }
    }

    private function deleteOldFiles()
    {
        $fileSystem = $this->getContainer()->get('filesystem');
        $path       = $this->getContainer()->getParameter('path.sftp') . 'groupama/';
        $duration   = 30; // jours
        $aFiles     = scandir($path);
        unset($aFiles[0], $aFiles[1]);
        foreach ($aFiles as $f) {
            $sFilePath    = $path . $f;
            $time         = filemtime($sFilePath);
            $deletionDate = mktime(date("H", $time), date("i", $time), date("s", $time), date("n", $time), date("d", $time) + $duration, date("Y", $time));

            if (time() >= $deletionDate) {
                $fileSystem->remove($sFilePath);
            }
        }
    }

    /**
     * @param \projects              $project
     * @param EntityManagerSimulator $entityManagerSimulator
     */
    private function sendNewProjectEmail(\projects $project, EntityManagerSimulator $entityManagerSimulator)
    {
        /** @var \clients $clients */
        $clients = $entityManagerSimulator->getRepository('clients');
        /** @var \notifications $notifications */
        $notifications = $entityManagerSimulator->getRepository('notifications');
        /** @var \clients_gestion_notifications $clientsGestionNotifications */
        $clientsGestionNotifications = $entityManagerSimulator->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clientsGestionMailsNotif */
        $clientsGestionMailsNotif = $entityManagerSimulator->getRepository('clients_gestion_mails_notif');
        /** @var \companies $companies */
        $companies = $entityManagerSimulator->getRepository('companies');
        /** @var \autobid $oAutobid */
        $oAutobid = $entityManagerSimulator->getRepository('autobid');
        /** @var \bids $bidsData */
        $bidsData = $entityManagerSimulator->getRepository('bids');
        /** @var \project_period $oProjectPeriods */
        $oProjectPeriods = $entityManagerSimulator->getRepository('project_period');
        /** @var WalletRepository $walletRepository */
        $walletRepository = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository('UnilendCoreBusinessBundle:Wallet');
        /** @var NotificationsRepository $notificationsRepository */
        $notificationsRepository = $this->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository('UnilendCoreBusinessBundle:Notifications');

        $companies->get($project->id_company, 'id_company');
        $oProjectPeriods->getPeriod($project->period);

        $autobidSettingsManager = $this->getContainer()->get('unilend.service.autobid_settings_manager');
        $translator             = $this->getContainer()->get('translator');
        $messageProvider        = $this->getContainer()->get('unilend.swiftmailer.message_provider');
        $mailer                 = $this->getContainer()->get('mailer');
        $productManager         = $this->getContainer()->get('unilend.service_product.product_manager');
        $router                 = $this->getContainer()->get('router');
        $numberFormatter        = $this->getContainer()->get('number_formatter');
        $currencyFormatter      = $this->getContainer()->get('currency_formatter');
        $logger                 = $this->getContainer()->get('monolog.logger.console');

        $hostUrl  = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
        $commonKeywords = [
            'companyName'     => $companies->name,
            'projectAmount'   => $numberFormatter->format($project->amount),
            'projectDuration' => $project->period,
            'projectLink'     => $hostUrl . $router->generate('project_detail', ['projectSlug' => $project->slug])
        ];

        $autoBidSettings  = $oAutobid->getSettings(null, $project->risk, $oProjectPeriods->id_period, [\autobid::STATUS_ACTIVE, \autobid::STATUS_INACTIVE]);
        $autoBidsAmount   = array_column($autoBidSettings, 'amount', 'id_lender');
        $autoBidsMinRate  = array_column($autoBidSettings, 'rate_min', 'id_lender');
        $autoBidsStatus   = array_column($autoBidSettings, 'status', 'id_lender');
        $projectRateRange = $this->getContainer()->get('unilend.service.bid_manager')->getProjectRateRange($project);
        $bids             = $bidsData->getLenders($project->id_project);
        $noAutobidPlaced  = array_diff(array_keys($autoBidsAmount), array_column($bids, 'id_lender_account'));

        $offset = 0;
        $limit  = 100;
        $logger->info('Send publication emails for project: ' . $project->id_project, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]);

        while ($lenders = $clients->selectPreteursByStatus(\clients_status::VALIDATED, 'c.status = 1', 'c.id_client ASC', $offset, $limit)) {
            $emailsSent = 0;
            $offset     += $limit;
            $logger->info('Lenders retrieved: ' . count($lenders), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]);

            foreach ($lenders as $aLender) {
                $wallet = $walletRepository->getWalletByType($aLender['id_client'], WalletType::LENDER);
                $keywords = [];

                if ($productManager->isClientEligible($wallet->getIdClient(), $project)) {
                    $notifications->type       = Notifications::TYPE_NEW_PROJECT;
                    $notifications->id_lender  = $wallet->getId();
                    $notifications->id_project = $project->id_project;
                    $notifications->create();

                    if (false === $clientsGestionMailsNotif->exist(\clients_gestion_type_notif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID . '" AND id_project = ' . $project->id_project . ' AND id_client = ' . $aLender['id_client'] . ' AND immediatement = "1',
                            'id_notif')) {
                        $clientsGestionMailsNotif->id_client       = $wallet->getIdClient()->getIdClient();
                        $clientsGestionMailsNotif->id_notif        = \clients_gestion_type_notif::TYPE_NEW_PROJECT;
                        $clientsGestionMailsNotif->id_notification = $notifications->id_notification;
                        $clientsGestionMailsNotif->id_project      = $project->id_project;
                        $clientsGestionMailsNotif->date_notif      = $project->date_publication;

                        $notifyNewProject = $clientsGestionNotifications->getNotif($wallet->getIdClient()->getIdClient(), \clients_gestion_type_notif::TYPE_NEW_PROJECT, 'immediatement');
                        $notifyBidPlaced  = $clientsGestionNotifications->getNotif($wallet->getIdClient()->getIdClient(), \clients_gestion_type_notif::TYPE_BID_PLACED, 'immediatement');

                        if ($notifyNewProject || $notifyBidPlaced) {
                            $clientsGestionMailsNotif->immediatement = 1;

                            try {
                                $hasAutolendOn = $autobidSettingsManager->isOn($wallet->getIdClient());
                            } catch (\Exception $exception) {
                                $logger->warning(
                                    'Could not check Autolend activation state for lender: ' . $wallet->getId() . ' Error: ' . $exception->getMessage(),
                                    ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                                );
                                /** Do not include any advises about autolend in the email */
                                $hasAutolendOn = null;
                            }
                            $autolendSettingsAdvises = '';
                            /** @var Notifications $bidPlacedNotification */
                            $bidPlacedNotification = $notificationsRepository->findOneBy(['idLender' => $wallet->getId(), 'idProject' => $project->id_project, 'type' => Notifications::TYPE_BID_PLACED]);

                            if (
                                null !== $bidPlacedNotification
                                && $bidsData->get($bidPlacedNotification->getIdBid())
                                && false === empty($bidsData->id_autobid)
                            ) {
                                $mailType = 'nouveau-projet-autobid';

                                $bidsData->get($bidPlacedNotification->getIdBid());
                                $keywords['autoBidAmount'] = $currencyFormatter->formatCurrency(round(bcdiv($bidsData->amount, 100, 4), 2), 'EUR');
                                $autolendMinRate           = max($projectRateRange['rate_min'], $autoBidsMinRate[$wallet->getId()]);

                                $numberFormatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 1);
                                $keywords['autoBidRate']     = $numberFormatter->format($bidsData->rate);
                                $keywords['autoLendMinRate'] = $numberFormatter->format($autolendMinRate);
                                $numberFormatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);

                                $keywords['availableBalance'] = $currencyFormatter->formatCurrency($wallet->getAvailableBalance(), 'EUR');
                            } else {
                                $mailType    = 'nouveau-projet';
                                $autolendUrl = $hostUrl . $router->generate('autolend');

                                if (true === $hasAutolendOn && true === in_array($wallet->getId(), $noAutobidPlaced)) {
                                    $walletDepositUrl = $hostUrl . $router->generate('lender_wallet_deposit');

                                    if (isset($autoBidsStatus[$wallet->getId()])) {
                                        switch ($autoBidsStatus[$wallet->getId()]) {
                                            case \autobid::STATUS_INACTIVE :
                                                $autolendSettingsAdvises = $translator->trans('email-nouveau-projet_autobid-setting-for-period-rate-off', ['%autolendUrl%', $autolendUrl]);
                                                break;
                                            case \autobid::STATUS_ACTIVE :
                                                if (bccomp($wallet->getAvailableBalance(), $autoBidsAmount[$wallet->getId()]) < 0) {
                                                    $autolendSettingsAdvises = $translator->trans('email-nouveau-projet_low-balance-for-autolend', ['%walletProvisionUrl%' => $walletDepositUrl]);
                                                }
                                                if (bccomp($autoBidsMinRate[$wallet->getId()], $projectRateRange['rate_max'], 2) > 0) {
                                                    $autolendMinRateTooHigh  = $translator->trans('email-nouveau-projet_autobid-min-rate-too-high', ['%autolendUrl%' => $autolendUrl]);
                                                    $autolendSettingsAdvises .= empty($autolendSettingsAdvises) ? $autolendMinRateTooHigh : '<br>' . $autolendMinRateTooHigh;
                                                }
                                                break;
                                            default :
                                                break;
                                        }
                                    }
                                    $keywords['customAutolendContent'] = $this->getAutolendCustomMessage($autolendSettingsAdvises);
                                } elseif (false === $hasAutolendOn) {
                                    $suggestAutolendActivation         = $translator->trans('email-nouveau-projet_suggest-autolend-activation', ['%autolendUrl%', $autolendUrl]);
                                    $keywords['customAutolendContent'] = $this->getAutolendCustomMessage($suggestAutolendActivation);
                                } else {
                                    $keywords['customAutolendContent'] = '';
                                }
                            }
                            $keywords['firstName']     = $wallet->getIdClient()->getPrenom();
                            $keywords['lenderPattern'] = $wallet->getWireTransferPattern();

                            $message                   = $messageProvider->newMessage($mailType, $commonKeywords + $keywords);
                            try {
                                $message->setTo($aLender['email']);
                                $mailer->send($message);
                            } catch (\Exception $exception) {
                                $logger->warning(
                                    'Could not send email: ' . $mailType . ' - Exception: ' . $exception->getMessage(),
                                    ['method' => __METHOD__, 'id_mail_template' => $message->getTemplateId(), 'id_client' => $aLender['id_client'], 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                                );
                            }
                            ++$emailsSent;
                        }
                        $clientsGestionMailsNotif->create();
                    }
                }
            }
            $logger->info('Emails sent: ' . $emailsSent, ['method' => __METHOD__, 'id_project' => $project->id_project]);
        }
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function getAutolendCustomMessage($content)
    {
        if (empty($content)) {
            return $content;
        }
        $customAutolendContent = '
            <table width="100%" border="1" cellspacing="0" cellpadding="5" bgcolor="d8b5ce" bordercolor="b20066">
                <tr>
                    <td class="text-primary text-center">' . $content . '</td>
                </tr>
            </table>';

        return $customAutolendContent;
    }
}
