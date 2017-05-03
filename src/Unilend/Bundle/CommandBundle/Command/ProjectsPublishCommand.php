<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Attachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Repository\WalletRepository;
use Unilend\librairies\CacheKeys;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Service\MailerManager;

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
        $oLogger        = $this->getContainer()->get('monolog.logger.console');
        $oEntityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \projects $oProject */
        $oProject = $oEntityManager->getRepository('projects');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager $oProjectManager */
        $oProjectManager = $this->getContainer()->get('unilend.service.project_manager');
        /** @var bool $bHasProjectPublished */
        $bHasProjectPublished = false;

        // One project each execution, to avoid the memory issue.
        $aProjectToFund = $oProject->selectProjectsByStatus([\projects_status::AUTO_BID_PLACED], "AND p.date_publication <= NOW()", [], '', 1, false);
        $oLogger->info('Number of projects to publish: ' . count($aProjectToFund), array('class' => __CLASS__, 'function' => __FUNCTION__));

        foreach ($aProjectToFund as $aProject) {
            if ($oProject->get($aProject['id_project'])) {
                $oLogger->info('Publishing the project ' . $aProject['id_project'], array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $aProject['id_project']));

                $bHasProjectPublished = true;
                $oProjectManager->publish($oProject);

                if ($oProjectManager->isFunded($oProject)) {
                    /** @var MailerManager $mailerManager */
                    $mailerManager = $this->getContainer()->get('unilend.service.email_manager');
                    $mailerManager->sendFundedToBorrower($oProject);
                }

                $this->zipProjectAttachments($oProject, $oEntityManager, $oLogger);

                if (false === $oProjectManager->isRateMinReached($oProject)) {
                    $this->sendNewProjectEmail($oProject, $oEntityManager);
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
     * @param \projects
     * @param EntityManager   $oEntityManager
     * @param LoggerInterface $oLogger
     */
    private function zipProjectAttachments(\projects $project, EntityManager $oEntityManager, LoggerInterface $oLogger)
    {
        /** @var \companies $companies */
        $companies = $oEntityManager->getRepository('companies');
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
        $path     = $this->getContainer()->getParameter('path.sftp') . 'groupama/';
        $duration = 30; // jours
        $aFiles   = scandir($path);
        unset($aFiles[0], $aFiles[1]);
        foreach ($aFiles as $f) {
            $sFilePath    = $path . $f;
            $time         = filemtime($sFilePath);
            $deletionDate = mktime(date("H", $time), date("i", $time), date("s", $time), date("n", $time), date("d", $time) + $duration, date("Y", $time));

            if (time() >= $deletionDate) {
                unlink($sFilePath);
            }
        }
    }

    /**
     * @param \projects      $project
     * @param  EntityManager $oEntityManager
     */
    private function sendNewProjectEmail(\projects $project, EntityManager $oEntityManager)
    {
        /** @var \clients $clients */
        $clients = $oEntityManager->getRepository('clients');
        /** @var \notifications $notifications */
        $notifications = $oEntityManager->getRepository('notifications');
        /** @var \clients_gestion_notifications $clients_gestion_notifications */
        $clients_gestion_notifications = $oEntityManager->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clients_gestion_mails_notif */
        $clients_gestion_mails_notif = $oEntityManager->getRepository('clients_gestion_mails_notif');
        /** @var \companies $companies */
        $companies = $oEntityManager->getRepository('companies');

        $oAutobidSettingsManager = $this->getContainer()->get('unilend.service.autobid_settings_manager');
        $translator              = $this->getContainer()->get('translator');
        $messageProvider         = $this->getContainer()->get('unilend.swiftmailer.message_provider');
        $mailer                  = $this->getContainer()->get('mailer');
        $productManager          = $this->getContainer()->get('unilend.service_product.product_manager');
        /** @var WalletRepository $walletRepository */
        $walletRepository        = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet');

        $insufficientBalance = $translator->trans('email-nouveau-projet_solde-insuffisant-nouveau-projet');

        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var \settings $settings */
        $settings = $oEntityManager->getRepository('settings');

        $sUrl       = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
        $sStaticUrl = $this->getContainer()->get('assets.packages')->getUrl('');

        /** @var LoggerInterface $oLogger */
        $oLogger = $this->getContainer()->get('monolog.logger.console');
        $oLogger->info('Send publication emails for project: ' . $project->id_project, array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));

        $companies->get($project->id_company, 'id_company');
        $settings->get('Facebook', 'type');
        $sFacebookLink = $settings->value;
        $settings->get('Twitter', 'type');
        $sTwitterLink = $settings->value;

        $varMail = [
            'surl'            => $sStaticUrl,
            'url'             => $sUrl,
            'nom_entreprise'  => $companies->name,
            'projet-p'        => $sUrl . '/projects/detail/' . $project->slug,
            'montant'         => $ficelle->formatNumber($project->amount, 0),
            'duree'           => $project->period,
            'gestion_alertes' => $sUrl . '/profile',
            'lien_fb'         => $sFacebookLink,
            'lien_tw'         => $sTwitterLink,
            'annee'           => date('Y')
        ];
        /** @var \project_period $oProjectPeriods */
        $oProjectPeriods = $oEntityManager->getRepository('project_period');
        $oProjectPeriods->getPeriod($project->period);

        /** @var \autobid $oAutobid */
        $oAutobid    = $oEntityManager->getRepository('autobid');
        $aAutobiders = array_column($oAutobid->getSettings(null, $project->risk, $oProjectPeriods->id_period, [\autobid::STATUS_ACTIVE]), 'amount', 'id_lender');

        /** @var \bids $oBids */
        $oBids            = $oEntityManager->getRepository('bids');
        $aBids            = $oBids->getLenders($project->id_project);
        $aNoAutobidPlaced = array_diff(array_keys($aAutobiders), array_column($aBids, 'id_lender_account'));

        $iOffset = 0;
        $iLimit  = 100;

        while ($aLenders = $clients->selectPreteursByStatus(\clients_status::VALIDATED, 'c.status = 1', 'c.id_client ASC', $iOffset, $iLimit)) {
            $iEmails = 0;
            $iOffset += $iLimit;

            $oLogger->info('Lenders retrieved: ' . count($aLenders), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]);

            foreach ($aLenders as $aLender) {
                $wallet = $walletRepository->getWalletByType($aLender['id_client'], WalletType::LENDER);
                if ($productManager->getLenderEligibility($wallet->getIdClient(), $project)) {
                    $notifications->type       = \notifications::TYPE_NEW_PROJECT;
                    $notifications->id_lender  = $aLender['id_lender'];
                    $notifications->id_project = $project->id_project;
                    $notifications->create();

                    if (false === $clients_gestion_mails_notif->exist(\clients_gestion_type_notif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID . '" AND id_project = ' . $project->id_project . ' AND id_client = ' . $aLender['id_client'] . ' AND immediatement = "1', 'id_notif')) {
                        $clients_gestion_mails_notif->id_client       = $wallet->getIdClient()->getIdClient();
                        $clients_gestion_mails_notif->id_notif        = \clients_gestion_type_notif::TYPE_NEW_PROJECT;
                        $clients_gestion_mails_notif->id_notification = $notifications->id_notification;
                        $clients_gestion_mails_notif->id_project      = $project->id_project;
                        $clients_gestion_mails_notif->date_notif      = $project->date_publication;

                        if ($clients_gestion_notifications->getNotif($wallet->getIdClient()->getIdClient(), \clients_gestion_type_notif::TYPE_NEW_PROJECT, 'immediatement')) {
                            $clients_gestion_mails_notif->immediatement = 1;

                            $sAutobidInsufficientBalance = '';

                            if (
                                in_array($wallet->getId(), $aNoAutobidPlaced)
                                && $oAutobidSettingsManager->isOn($wallet->getIdClient())
                                && $wallet->getAvailableBalance() < $aAutobiders[$wallet->getId()]
                            ) {
                                $sAutobidInsufficientBalance = '
                                    <table width=\'100%\' border=\'1\' cellspacing=\'0\' cellpadding=\'5\' bgcolor="d8b5ce" bordercolor="b20066">
                                        <tr>
                                            <td align="center" style="color: #b20066">' . $insufficientBalance . '</td>
                                        </tr>
                                    </table>';
                            }
                            $varMail['autobid_insufficient_balance'] = $sAutobidInsufficientBalance;
                            $varMail['prenom_p']                     = $wallet->getIdClient()->getPrenom();
                            $varMail['motif_virement']               = $wallet->getWireTransferPattern();

                            $message = $messageProvider->newMessage('nouveau-projet', $varMail);
                            $message->setTo($aLender['email']);
                            $mailer->send($message);
                            ++$iEmails;
                        }
                        $clients_gestion_mails_notif->create();
                    }
                }
            }
            $oLogger->info('Emails sent: ' . $iEmails, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]);
        }
    }
}
