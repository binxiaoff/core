<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class CheckRiskDataOnExistingProjectsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('unilend:check_risk_data_on_existing_projects')
            ->setDescription('Apply risk ws control on given project IDs (input is csv file with project IDs)')
            ->addArgument('file', InputOption::VALUE_REQUIRED, 'CSV file name to get projects IDs from');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Block this cron temporally in order to finalize DEV-1171
        die('Non stable version. Do not execute on prod');

        $filePath       = $this->getContainer()->getParameter('path.sftp');
        $inputFileName  = $input->getArgument('file');
        $outputFilename = 'processed_' . $inputFileName;

        if (file_exists($filePath . $outputFilename)) {
            unlink($filePath . $outputFilename);
        }

        if ($inputFileName && file_exists($filePath . $inputFileName)) {
            if (false !== $inputHandler = fopen($filePath . $inputFileName, 'r')) {
                $outputHandler = fopen($filePath . $outputFilename, 'a');
                fputcsv($outputHandler, ['project ID', 'Rejection reason']);

                /** @var EntityManager $entityManager */
                $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
                /** @var \projects $project */
                $project = $entityManager->getRepository('projects');
                /** @var \companies $company */
                $company               = $entityManager->getRepository('companies');
                $projectRequestManager = $this->getContainer()->get('unilend.service.project_request_manager');

                while ($row = fgetcsv($inputHandler)) {
                    if ($project->get($row[0])) {
                        if ($company->get($project->id_company)) {
                            $result = $projectRequestManager->checkProjectRisk($project, Users::USER_ID_CRON);
                            fputcsv($outputHandler, [$project->id_project, (isset($result['motive'])) ? $result['motive'] : 'All checks ok']);
                        }
                    } else {
                        var_dump('project ' . $row[0] . ' does not exists');
                    }
                }
                fclose($inputHandler);
                fclose($outputHandler);
            }
        }
    }
}
