<?php

namespace Unilend\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Entity\{Partner, ProjectsStatus, Users, UsersHistory};
use Unilend\Service\ProjectRequestManager;

class CreateProjectsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('projects:create')
            ->setDescription('Create projects from a given list of SIREN');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager           = $this->getContainer()->get('doctrine.orm.entity_manager');
        $projectRequestManager   = $this->getContainer()->get('unilend.service.project_request_manager');
        $partnerManager          = $this->getContainer()->get('unilend.service.partner_manager');
        $projectStatusRepository = $entityManager->getRepository(ProjectsStatus::class);
        $slackManager            = $this->getContainer()->get('unilend.service.slack_manager');
        $messageProvider         = $this->getContainer()->get('unilend.swiftmailer.message_provider');

        $fileSystem              = $this->getContainer()->get('filesystem');
        $bulkCompanyCheckManager = $this->getContainer()->get('unilend.service.eligibility.bulk_company_check_manager');

        $now            = new \DateTime();
        $outputFilePath = $bulkCompanyCheckManager->getProjectCreationOutputDir() . $now->format('Y-m') . DIRECTORY_SEPARATOR;

        foreach ($bulkCompanyCheckManager->getSirenListForProjectCreation() as $fileName => $sirenList) {
            $user = $bulkCompanyCheckManager->getUploadUser($fileName);

            if (null === $user) {
                $user = $entityManager->getRepository(Users::class)->find(Users::USER_ID_CRON);
            }
            $createdProjects = [];
            try {
                $excel       = new \PHPExcel();
                $activeSheet = $excel->setActiveSheetIndex(0);

                $activeSheet->setCellValue('A1', 'SIREN');
                $activeSheet->setCellValue('B1', 'ID Projet');
                $activeSheet->setCellValue('C1', 'Statut Projet');
                $activeSheet->setCellValue('D1', 'ID Client emprunteur');
                $activeSheet->setCellValue('E1', 'ID Société');
                $rowIndex = 2;

                foreach ($sirenList as $inputRow) {
                    $siren = isset($inputRow[0]) ? $inputRow[0] : null;
                    $output->writeln('Processing siren: ' . $siren);
                    if (1 !== preg_match('/^([0-9]{9})$/', $siren)) {
                        continue;
                    }

                    $amount  = ProjectRequestManager::DEFAULT_PROJECT_AMOUNT;
                    if (isset($inputRow[1]) && filter_var(FILTER_VALIDATE_INT, $inputRow[1])) {
                        $amount = filter_var(FILTER_VALIDATE_INT, $inputRow[1]);
                    }
                    if (isset($inputRow[2]) && filter_var(FILTER_VALIDATE_INT, $inputRow[2])) {
                        $partner = $entityManager->getRepository(Partner::class)->find(filter_var(FILTER_VALIDATE_INT, $inputRow[2]));
                    }
                    $partner = empty($partner) ? $partnerManager->getDefaultPartner() : $partner;

                    $project = $projectRequestManager->newProject($user, $partner, ProjectsStatus::STATUS_REQUESTED, $amount, $siren);
                    $company = $project->getIdCompany();
                    $createdProjects[] = $project->getIdProject();

                    $columnIndex = 'A';
                    $activeSheet->setCellValue($columnIndex . $rowIndex, $siren);
                    $columnIndex++;
                    $projectRequestManager->checkProjectRisk($project, $user->getIdUser());
                    $activeSheet->setCellValue($columnIndex . $rowIndex, $project->getIdProject());
                    $columnIndex++;
                    $projectRequestManager->checkProjectRisk($project, $user->getIdUser());
                    $activeSheet->setCellValue($columnIndex . $rowIndex, $projectStatusRepository->findOneBy(['status' => $project->getStatus()])->getLabel());
                    $columnIndex++;
                    $activeSheet->setCellValue($columnIndex . $rowIndex, $company->getIdClientOwner()->getIdClient());
                    $columnIndex++;
                    $activeSheet->setCellValue($columnIndex . $rowIndex, $company->getIdCompany());
                    $rowIndex++;
                }

                $outputFileName = 'Creation-projets_' . (new \DateTime())->format('Ymd_His') . '.xlsx';

                if (false === is_dir($outputFilePath)) {
                    $fileSystem->mkdir($outputFilePath);
                }

                /** @var \PHPExcel_Writer_Excel2007 $writer */
                $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
                $writer->save($outputFilePath . $outputFileName);
                $message = 'Le fichier: *' . $fileName . '* a bien été traité. Vous trouverez le détail dans le fichier de sortie: *' . $outputFileName . '*';

                $serialize = serialize(['created_projects' => $createdProjects]);
                /** @var \users_history $userHistoryData */
                $userHistoryData = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('users_history');
                $userHistoryData->histo(UsersHistory::FORM_ID_BULK_PROJECT_CREATION, UsersHistory::FROM_NAME_BULK_PROJECT_CREATION, $user->getIdUser(), $serialize);
            } catch (\Exception $exception) {
                $this->getContainer()->get(
                    'monolog.logger.console')->warning('Error while processing siren list of file : ' . $fileName . ' Error: ' . $exception->getMessage(),
                    ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'created_projects' => $createdProjects]
                );
                $message = 'Une erreur s\'est produite lors du traitement du fichier : *' . $fileName . '*.' . "\n" . 'Le fichier résultat n\'a pas été créé.';
                $message .= empty($createdProjects) ? '' : ' Voici la liste des dossiers créés: ' . implode(', ', $createdProjects);
            }
            if (false === empty($user->getSlack())) {
                $slackManager->sendMessage($message, $user->getSlack());
            }
            if (false === empty($user->getEmail())) {
                $templateMessage = $messageProvider->newMessage('resultat-creation-liste-dossiers', ['details' => $message]);
                try {
                    $templateMessage->setTo($user->getEmail());
                    if (isset($outputFileName)) {
                        $templateMessage->attach(\Swift_Attachment::fromPath($outputFilePath . $outputFileName));
                    }
                    $mailer = $this->getContainer()->get('mailer');
                    $mailer->send($templateMessage);
                } catch (\Exception $exception) {
                    $this->getContainer()->get('monolog.logger.console')->warning(
                        'Could not send email : resultat-creation-liste-dossiers - Exception: ' . $exception->getMessage(),
                        ['id_mail_template' => $templateMessage->getTemplateId(), 'email address' => $user->getEmail(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                    );
                }
            }
        }
    }
}
