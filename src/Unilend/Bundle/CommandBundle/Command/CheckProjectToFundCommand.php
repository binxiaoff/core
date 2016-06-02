<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\librairies\Cache;
use Unilend\core\Loader;
use Unilend\Service\Simulator\EntityManager;

class CheckProjectToFundCommand extends ContainerAwareCommand
{
    private $sRootPath;
    private $aConfig;

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
        $sRootDir        = $this->getContainer()->getParameter('kernel.root_dir');
        $this->sRootPath = $sRootDir . '/../';
        $this->aConfig   = Loader::loadConfig();

        /** @var EntityManager $oEntityManager */
        $oEntityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \projects $oProject */
        $oProject = $oEntityManager->getRepository('projects');
        /** @var \Unilend\Service\ProjectManager $oProjectManager */
        $oProjectManager = $this->getContainer()->get('unilend.service.project_manager');
        /** @var bool $bHasProjectPublished */
        $bHasProjectPublished = false;

        $aProjectToFund = $oProject->selectProjectsByStatus(\projects_status::AUTO_BID_PLACED, "AND p.date_publication_full <= NOW()", '', array(), '', '', false);

        foreach ($aProjectToFund as $aProject) {
            if ($oProject->get($aProject['id_project'])) {
                $bHasProjectPublished = true;
                $oProjectManager->publish($oProject);
                $this->zipProjectAttachments($oProject);
                $this->sendNewProjectEmail($oProject);
            }
        }
        if ($bHasProjectPublished) {
            /** @var \Cache\Adapter\Memcache\MemcacheCachePool $oCachePool */
            $oCachePool = $this->getContainer()->get('memcache.default');
            /** @todo Decide on the New maneer to manage Cache in controller side */
            $oCachePool->deleteItem(Cache::LIST_PROJECTS . '_' . \projects_status::EN_FUNDING);
        }
    }

    /**
     * @param \projects
     */
    private function zipProjectAttachments(\projects $projects)
    {
        /** @var EntityManager $oEntityManager */
        $oEntityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \companies $companies */
        $companies = $oEntityManager->getRepository('companies');
        /** @var \attachment $oAttachment */
        $oAttachment = $oEntityManager->getRepository('attachment');
        /** @var \attachment_type $oAttachmentType */
        $oAttachmentType = $oEntityManager->getRepository('attachment_type');

        $companies->get($projects->id_company, 'id_company');

        $sPathNoZip = $this->sRootPath . 'protected/sftp_groupama_nozip/';
        $sPath      = $this->sRootPath . 'protected/sftp_groupama/';

        if (false === is_dir($sPath)) {
            mkdir($sPath);
        }

        if (false === is_dir($sPathNoZip)) {
            mkdir($sPathNoZip);
        }

        if (false === is_dir($sPathNoZip . $companies->siren)) {
            mkdir($sPathNoZip . $companies->siren);
        }

        /** @var \attachment_helper $oAttachmentHelper */
        $oAttachmentHelper = Loader::loadLib('attachment_helper', array($oAttachment, $oAttachmentType, $this->sRootPath));
        $aAttachments      = $projects->getAttachments();

        $this->copyAttachment($oAttachmentHelper, $aAttachments, \attachment_type::CNI_PASSPORTE_DIRIGEANT, 'CNI-#', $companies->siren, $sPathNoZip);
        $this->copyAttachment($oAttachmentHelper, $aAttachments, \attachment_type::CNI_PASSPORTE_VERSO, 'CNI-VERSO-#', $companies->siren, $sPathNoZip);

        $this->copyAttachment($oAttachmentHelper, $aAttachments, \attachment_type::KBIS, 'KBIS-#', $companies->siren, $sPathNoZip);

        $this->copyAttachment($oAttachmentHelper, $aAttachments, \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_1, 'CNI-25-1-#', $companies->siren, $sPathNoZip);
        $this->copyAttachment($oAttachmentHelper, $aAttachments, \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_1, 'CNI-25-1-VERSO-#', $companies->siren, $sPathNoZip);

        $this->copyAttachment($oAttachmentHelper, $aAttachments, \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_2, 'CNI-25-2-#', $companies->siren, $sPathNoZip);
        $this->copyAttachment($oAttachmentHelper, $aAttachments, \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_2, 'CNI-25-2-VERSO-#', $companies->siren, $sPathNoZip);

        $this->copyAttachment($oAttachmentHelper, $aAttachments, \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_3, 'CNI-25-3-#', $companies->siren, $sPathNoZip);
        $this->copyAttachment($oAttachmentHelper, $aAttachments, \attachment_type::CNI_BENEFICIAIRE_EFFECTIF_VERSO_3, 'CNI-25-3-VERSO-#', $companies->siren, $sPathNoZip);

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

    private function copyAttachment(\attachment_helper $oAttachmentHelper, $aAttachments, $sAttachmentType, $sPrefix, $sSiren, $sPathNoZip)
    {
        if (false === isset($aAttachments[$sAttachmentType]['path'])) {
            return;
        }
        $sFromPath  = $oAttachmentHelper->getFullPath(\attachment::PROJECT, $sAttachmentType) . $aAttachments[$sAttachmentType]['path'];
        $aPathInfo  = pathinfo($sFromPath);
        $sExtension = isset($aPathInfo['extension']) ? $aPathInfo['extension'] : '';
        $sNewName   = $sPrefix . $sSiren . '.' . $sExtension;

        copy($sFromPath, $sPathNoZip . $sSiren . '/' . $sNewName);
    }

    private function deleteOldFiles()
    {
        $path     = $this->sRootPath . 'protected/sftp_groupama/';
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
     * @param \projects $projects
     */
    private function sendNewProjectEmail(\projects $projects)
    {
        /** @var EntityManager $oEntityManager */
        $oEntityManager = $this->getContainer()->get('unilend.service.entity_manager');
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
        $oEntityManager->getRepository('clients_status');//For class constants
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var \settings $settings */
        $settings = $oEntityManager->getRepository('settings');

        /** @var Logger $oLogger */
        $oLogger = $this->getContainer()->get('monolog.logger.console');
        $oLogger->info('Send email for Project ID: ' . $projects->id_project, array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $projects->id_project));

        $companies->get($projects->id_company, 'id_company');
        $env = $this->aConfig['env'];
        $settings->get('Facebook', 'type');
        $sFacebookLink = $settings->value;
        $settings->get('Twitter', 'type');
        $sTwitterLink = $settings->value;

        $varMail = array(
            'surl'            => $this->aConfig['url'][$env]['default'],
            'url'             => $this->aConfig['url'][$env]['default'],
            'nom_entreprise'  => $companies->name,
            'projet-p'        => $this->aConfig['url'][$env]['default'] . '/projects/detail/' . $projects->slug,
            'montant'         => $ficelle->formatNumber($projects->amount, 0),
            'duree'           => $projects->period,
            'gestion_alertes' => $this->aConfig['url'][$env]['default'] . '/profile',
            'lien_fb'         => $sFacebookLink,
            'lien_tw'         => $sTwitterLink
        );

        $iOffset = 0;
        $iLimit  = 100;

        while ($aLenders = $clients->selectPreteursByStatus(\clients_status::VALIDATED, 'c.status = 1', 'c.id_client ASC', $iOffset, $iLimit)) {
            $iEmails = 0;
            $iOffset += $iLimit;

            $oLogger->info('Lenders retrieved: ' . count($aLenders), array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $projects->id_project));

            foreach ($aLenders as $aLender) {
                $notifications->type       = \notifications::TYPE_NEW_PROJECT;
                $notifications->id_lender  = $aLender['id_lender'];
                $notifications->id_project = $projects->id_project;
                $notifications->create();

                $clients_gestion_mails_notif->id_client       = $aLender['id_client'];
                $clients_gestion_mails_notif->id_notif        = \clients_gestion_type_notif::TYPE_NEW_PROJECT;
                $clients_gestion_mails_notif->id_notification = $notifications->id_notification;
                $clients_gestion_mails_notif->id_project      = $projects->id_project;
                $clients_gestion_mails_notif->date_notif      = $projects->date_publication_full;

                if ($clients_gestion_notifications->getNotif($aLender['id_client'], \clients_gestion_type_notif::TYPE_NEW_PROJECT, 'immediatement')) {
                    $clients_gestion_mails_notif->immediatement = 1;
                    $varMail['prenom_p']                        = $aLender['prenom'];
                    $varMail['motif_virement']                  = $clients->getLenderPattern($aLender['id_client']);

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('nouveau-projet', $varMail);
                    $message->setTo($aLender['email']);
                    $mailer = $this->getContainer()->get('mailer');
                    /** @todo check the mailer transport config : actually the error : "sh: 1: /usr/sbin/sendmail: not found" when I execute the command */
                    $mailer->send($message);
                    ++$iEmails;
                }
                $clients_gestion_mails_notif->create();
            }
            $oLogger->info('Emails sent: ' . $iEmails);
        }
    }
}
