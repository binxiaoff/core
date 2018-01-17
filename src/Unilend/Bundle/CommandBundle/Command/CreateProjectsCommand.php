<?php

namespace Unilend\Bundle\CommandBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Partner;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\UsersHistory;

class CreateProjectsCommand extends ContainerAwareCommand
{
    const DEFAULT_PROJECT_AMOUNT = 10000;

    protected function configure()
    {
        $this->setName('projects:create')
            ->setDescription('Create projects from a given list of SIREN');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager           = $this->getContainer()->get('doctrine.orm.entity_manager');
        $companyManager          = $this->getContainer()->get('unilend.service.company_manager');
        $projectRequestManager   = $this->getContainer()->get('unilend.service.project_request_manager');
        $partnerManager          = $this->getContainer()->get('unilend.service.partner_manager');
        $projectStatusRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus');
        $slackManager            = $this->getContainer()->get('unilend.service.slack_manager');
        $messageProvider         = $this->getContainer()->get('unilend.swiftmailer.message_provider');

        $fileSystem              = $this->getContainer()->get('filesystem');
        $bulkCompanyCheckManager = $this->getContainer()->get('unilend.service.eligibility.bulk_company_check_manager');

        $now            = new \DateTime();
        $outputFilePath = $bulkCompanyCheckManager->getProjectCreationOutputDir() . $now->format('Y-m') . DIRECTORY_SEPARATOR;

        foreach ($bulkCompanyCheckManager->getSirenListForProjectCreation() as $fileName => $sirenList) {
            $user = $bulkCompanyCheckManager->getUploadUser($fileName);

            if (null === $user) {
                $user = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_CRON);
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
                    $company = $companyManager->createBorrowerBlankCompany($siren, $user->getIdUser());
                    $amount  = self::DEFAULT_PROJECT_AMOUNT;
                    if (isset($inputRow[1]) && filter_var(FILTER_VALIDATE_INT, $inputRow[1])) {
                        $amount = filter_var(FILTER_VALIDATE_INT, $inputRow[1]);
                    }
                    if (isset($inputRow[2]) && filter_var(FILTER_VALIDATE_INT, $inputRow[2])) {
                        $partner = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->find(filter_var(FILTER_VALIDATE_INT, $inputRow[2]));
                    }
                    $partner = empty($partner) ? $partnerManager->getDefaultPartner() : $partner;

                    $project           = $this->createProject($company, $user, $amount, $partner);
                    $createdProjects[] = $project->id_project;

                    $columnIndex = 'A';
                    $activeSheet->setCellValue($columnIndex . $rowIndex, $siren);
                    $columnIndex++;
                    $projectRequestManager->checkProjectRisk($project, $user->getIdUser());
                    $activeSheet->setCellValue($columnIndex . $rowIndex, $project->id_project);
                    $columnIndex++;
                    $projectRequestManager->checkProjectRisk($project, $user->getIdUser());
                    $activeSheet->setCellValue($columnIndex . $rowIndex, $projectStatusRepository->findOneBy(['status' => $project->status])->getLabel());
                    $columnIndex++;
                    $activeSheet->setCellValue($columnIndex . $rowIndex, $company->getIdClientOwner()->getIdClient());
                    $columnIndex++;
                    $activeSheet->setCellValue($columnIndex . $rowIndex, $company->getIdCompany());
                    $rowIndex++;
                }
                $fileInfo       = pathinfo($fileName);
                $outputFileName = $fileInfo['filename'] . '_output_' . $now->getTimestamp() . '.xlsx';

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

    /**
     * @param Companies $company
     * @param Users     $user
     * @param int       $amount
     * @param Partner   $partner
     *
     * @return \projects
     */
    private function createProject(Companies $company, Users $user, $amount, $partner)
    {
        /** @var \projects $project */
        $project                                       = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('projects');
        $project->id_company                           = $company->getIdCompany();
        $project->amount                               = $amount;
        $project->id_partner                           = $partner->getId();
        $project->create_bo                            = 1;
        $project->ca_declara_client                    = 0;
        $project->resultat_exploitation_declara_client = 0;
        $project->fonds_propres_declara_client         = 0;
        $project->status                               = ProjectsStatus::INCOMPLETE_REQUEST;
        $project->commission_rate_funds                = \projects::DEFAULT_COMMISSION_RATE_FUNDS;
        $project->commission_rate_repayment            = \projects::DEFAULT_COMMISSION_RATE_REPAYMENT;
        $project->create();

        $projectStatusManager = $this->getContainer()->get('unilend.service.project_status_manager');
        $projectStatusManager->addProjectStatus($user->getIdUser(), ProjectsStatus::INCOMPLETE_REQUEST, $project);

        return $project;
    }
}
