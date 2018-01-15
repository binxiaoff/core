<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Attachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsGestionTypeNotif;
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
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectLifecycleManager $projectLifecycleManager */
        $projectLifecycleManager = $this->getContainer()->get('unilend.service.project_lifecycle_manager');
        /** @var bool $bHasProjectPublished */
        $bHasProjectPublished = false;

        // One project each execution, to avoid the memory issue.
        $aProjectToFund = $oProject->selectProjectsByStatus([\projects_status::AUTO_BID_PLACED], "AND p.date_publication <= NOW()", [], '', 1, false);
        $oLogger->info('Number of projects to publish: ' . count($aProjectToFund), ['class' => __CLASS__, 'function' => __FUNCTION__]);

        foreach ($aProjectToFund as $aProject) {
            if ($oProject->get($aProject['id_project'])) {
                $oLogger->info('Publishing the project ' . $aProject['id_project'], ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $aProject['id_project']]);

                try {
                    $bHasProjectPublished = true;
                    $this->insertNewProjectNotification($oProject, $entityManagerSimulator);
                    $projectLifecycleManager->publish($oProject);

                    if ($oProjectManager->isFunded($oProject)) {
                        /** @var MailerManager $mailerManager */
                        $mailerManager = $this->getContainer()->get('unilend.service.email_manager');
                        $mailerManager->sendFundedToBorrower($oProject);
                    }

                    $this->zipProjectAttachments($oProject, $entityManagerSimulator);
                } catch (\Exception $exception) {
                    $oLogger->critical('An exception occurred during publishing of project ' . $oProject->id_project . ' with message: ' . $exception->getMessage(), [
                        'method' => __METHOD__,
                        'file'   => $exception->getFile(),
                        'line'   => $exception->getLine()
                    ]);
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
    private function insertNewProjectNotification(\projects $project, EntityManagerSimulator $entityManagerSimulator): void
    {
        /** @var \clients $clientData */
        $clientData = $entityManagerSimulator->getRepository('clients');

        /** @var WalletRepository $walletRepository */
        $walletRepository = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet');
        /** @var NotificationsRepository $notificationsRepository */
        $notificationsRepository = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Notifications');

        $notificationManager = $this->getContainer()->get('unilend.service.notification_manager');
        $productManager      = $this->getContainer()->get('unilend.service_product.product_manager');
        $logger              = $this->getContainer()->get('monolog.logger.console');

        $offset = 0;
        $limit  = 100;
        $logger->info('Send new project notification for project: ' . $project->id_project, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]);

        while ($lenders = $clientData->selectPreteursByStatus(\clients_status::VALIDATED, 'c.status = ' . Clients::STATUS_ONLINE, 'c.id_client ASC', $offset, $limit)) {
            $notificationSent = 0;
            $offset           += $limit;
            $logger->info('Lenders retrieved: ' . count($lenders), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]);

            foreach ($lenders as $lender) {
                $wallet                 = $walletRepository->getWalletByType($lender['id_client'], WalletType::LENDER);
                $isClientEligible       = $productManager->isClientEligible($wallet->getIdClient(), $project);
                $newProjectNotification = null;

                if ($isClientEligible) {
                    $notificationSent++;
                    $newProjectNotification = $notificationsRepository->findOneBy(['idNotification' => Notifications::TYPE_NEW_PROJECT, 'idLender' => $wallet, 'idProject' => $project->id_project]);
                }

                if (null !== $newProjectNotification && $isClientEligible) {
                    try {
                        $notificationManager->createEmailNotification(
                            $newProjectNotification->id_notification,
                            ClientsGestionTypeNotif::TYPE_NEW_PROJECT,
                            $wallet->getIdClient()->getIdClient(),
                            null,
                            $project->id_project,
                            null,
                            true
                        );
                    } catch (OptimisticLockException $exception) {
                        $logger->warning(
                            'Could not insert the new project notification for client ' . $wallet->getIdClient()->getIdClient() . '. Exception: ' . $exception->getMessage(),
                            ['method' => __METHOD__, 'id_project' => $project->id_project, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                        );
                    }
                }
            }
            $logger->info('Notifications sent: ' . $notificationSent, ['method' => __METHOD__, 'id_project' => $project->id_project]);
        }
    }
}
