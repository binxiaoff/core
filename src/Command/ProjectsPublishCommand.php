<?php

namespace Unilend\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{Input\InputInterface, Output\OutputInterface};
use Unilend\Entity\{Attachment, AttachmentType, Projects};
use Unilend\librairies\CacheKeys;

class ProjectsPublishCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $hasProjectPublished     = false;
        $logger                  = $this->getContainer()->get('monolog.logger.console');
        $entityManager           = $this->getContainer()->get('doctrine.orm.entity_manager');
        $projectRepository       = $entityManager->getRepository(Projects::class);
        $projectLifecycleManager = $this->getContainer()->get('unilend.service.project_lifecycle_manager');
        $projectLifecycleManager->setLogger($logger);

        // One project each execution, to avoid the memory issue.
        $projectToPublish = $projectRepository->findPublish(1);

        $logger->info('Number of projects to publish: ' . count($projectToPublish), ['class' => __CLASS__, 'function' => __FUNCTION__]);

        /** @var Projects $project */
        foreach ($projectToPublish as $project) {
            $output->writeln('Project : ' . $project->getTitle());

            $logger->info('Publishing the project ' . $project->getIdProject(), [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__
            ]);

            try {
                $hasProjectPublished = true;
                $projectLifecycleManager->publish($project);

                $this->zipProjectAttachments($project);
            } catch (\Exception $exception) {
                $logger->critical('An exception occurred during publishing of project ' . $project->getIdProject() . ' with message: ' . $exception->getMessage(), [
                    'id_project' => $project->getIdProject(),
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__,
                    'file'       => $exception->getFile(),
                    'line'       => $exception->getLine()
                ]);
            }
        }

        if ($hasProjectPublished) {
            $cacheDriver = $entityManager->getConfiguration()->getResultCacheImpl();
            $cacheDriver->delete(CacheKeys::LIST_PROJECTS);
        }
    }

    /**
     * @param Projects $project
     */
    private function zipProjectAttachments(Projects $project): void
    {
        $company   = $project->getIdCompany();
        $noZipPath = $this->getContainer()->getParameter('path.sftp') . 'groupama_nozip/';
        $path      = $this->getContainer()->getParameter('path.sftp') . 'groupama/';

        if (false === is_dir($path)) {
            mkdir($path);
        }

        if (false === is_dir($noZipPath)) {
            mkdir($noZipPath);
        }

        if (false === is_dir($noZipPath . $company->getSiren())) {
            mkdir($noZipPath . $company->getSiren());
        }

        $attachments = $project->getAttachments();

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

            $this->copyAttachment($attachment, $prefix, $company->getSiren(), $noZipPath);
        }

        $zip = new \ZipArchive();

        if (is_dir($noZipPath . $company->getSiren())) {
            if ($zip->open($path . $company->getSiren() . '.zip', \ZipArchive::CREATE) == true) {
                $files = scandir($noZipPath . $company->getSiren());
                unset($files[0], $files[1]);

                foreach ($files as $file) {
                    $zip->addFile($noZipPath . $company->getSiren() . '/' . $file, $file);
                }

                $zip->close();
            }
        }

        $this->deleteOldFiles();
    }

    /**
     * @param Attachment $attachment
     * @param string     $prefix
     * @param string     $siren
     * @param string     $pathNoZip
     */
    private function copyAttachment(Attachment $attachment, string $prefix, string $siren, string $pathNoZip): void
    {
        $attachmentManager = $this->getContainer()->get('unilend.service.attachment_manager');
        $fullPath          = $attachmentManager->getFullPath($attachment);

        if (file_exists($fullPath)) {
            $pathInfo  = pathinfo($fullPath);
            $extension = isset($pathInfo['extension']) ? $pathInfo['extension'] : '';
            $newName   = $prefix . $siren . '.' . $extension;

            copy($fullPath, $pathNoZip . $siren . '/' . $newName);
        }
    }

    private function deleteOldFiles(): void
    {
        $fileSystem = $this->getContainer()->get('filesystem');
        $path       = $this->getContainer()->getParameter('path.sftp') . 'groupama/';
        $duration   = 30; // jours
        $files      = scandir($path);
        unset($files[0], $files[1]);

        foreach ($files as $file) {
            $filePath     = $path . $file;
            $time         = filemtime($filePath);
            $deletionDate = mktime(date('H', $time), date('i', $time), date('s', $time), date('n', $time), date('d', $time) + $duration, date('Y', $time));

            if (time() >= $deletionDate) {
                $fileSystem->remove($filePath);
            }
        }
    }
}
