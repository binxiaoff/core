<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\CompanyBalanceSheetManager;
use Unilend\Bundle\CoreBusinessBundle\Service\CompanyFinanceCheck;
use Unilend\Bundle\CoreBusinessBundle\Service\CompanyScoringCheck;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\WSClientBundle\Entity\Altares\CompanyRating;

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
        $filePath       = $this->getContainer()->getParameter('path.sftp');
        $inputFileName  = $input->getArgument('file');
        $outputFilename = 'processed_' . $inputFileName;

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');
        /** @var \companies $company */
        $company = $entityManager->getRepository('companies');

        if (file_exists($filePath . $outputFilename)) {
            unlink($filePath . $outputFilename);
        }

        if ($inputFileName && file_exists($filePath . $inputFileName)) {
            if (false !== $inputHandler = fopen($filePath . $inputFileName, 'r')) {
                $outputHandler = fopen($filePath . $outputFilename, 'a');
                fputcsv($outputHandler, ['project ID', 'Rejection reason']);

                while ($row = fgetcsv($inputHandler)) {
                    if ($project->get($row[0])) {
                        if ($company->get($project->id_company)) {
                            $reason = $this->checkCompany($company, $project);
                            fputcsv($outputHandler, [$project->id_project, $reason]);
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

    /**
     * @param \companies $company
     * @param \projects $project
     * @return bool
     */
    private function checkCompany(\companies &$company, \projects &$project)
    {
        /** @var ProjectManager $projectManager */
        $projectManager = $this->getContainer()->get('unilend.service.project_manager');
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \company_rating_history $companyRatingHistory */
        $companyRatingHistory = $entityManager->getRepository('company_rating_history');
        /** @var \Psr\Log\LoggerInterface $logger */
        $logger                           = $this->getContainer()->get('logger');
        $logContext                       = ['class' => __CLASS__, 'function' => __FUNCTION__];
        $companyRatingHistory->id_company = $project->id_company;
        $companyRatingHistory->id_user    = \users::USER_ID_CRON;
        $companyRatingHistory->action     = \company_rating_history::ACTION_WS;
        $companyRatingHistory->create();

        /** @var \company_rating $companyRating */
        $companyRating = $entityManager->getRepository('company_rating');

        if (false === empty($project->id_company_rating_history)) {
            foreach ($companyRating->getHistoryRatingsByType($project->id_company_rating_history) as $rating => $value) {
                if (false === in_array($rating, \company_rating::$ratingTypes)) {
                    $companyRating->id_company_rating_history = $companyRatingHistory->id_company_rating_history;
                    $companyRating->type                      = $rating;
                    $companyRating->value                     = $value;
                    $companyRating->create();
                }
            }
        }
        $project->balance_count             = '0000-00-00' === $company->date_creation ? 0 : \DateTime::createFromFormat('Y-m-d', $company->date_creation)->diff(new \DateTime())->y;
        $project->id_company_rating_history = $companyRatingHistory->id_company_rating_history;
        $project->update();

        /** @var CompanyFinanceCheck $companyFinanceCheck */
        $companyFinanceCheck = $this->getContainer()->get('unilend.service.company_finance_check');

        if (false === $companyFinanceCheck->isCompanySafe($company, $rejectionReason)) {
            $projectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::NOTE_EXTERNE_FAIBLE, $project, 0, $rejectionReason);

            return $rejectionReason;
        }

        if (true === $companyFinanceCheck->hasCodinfPaymentIncident($company->siren, $rejectionReason)) {
            $projectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::NOTE_EXTERNE_FAIBLE, $project, 0, $rejectionReason);

            return $rejectionReason;
        }
        /** @var CompanyScoringCheck $companyScoringCheck */
        $companyScoringCheck = $this->getContainer()->get('unilend.service.company_scoring_check');
        /** @var CompanyRating $altaresScore */
        $altaresScore = $companyScoringCheck->getAltaresScore($company->siren);

        if (true === $companyScoringCheck->isAltaresScoreLow($altaresScore, $companyRatingHistory, $companyRating, $rejectionReason)) {
            $projectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::NOTE_EXTERNE_FAIBLE, $project, 0, $rejectionReason);

            return $rejectionReason;
        }
        /** @var \Unilend\Bundle\WSClientBundle\Entity\Altares\BalanceSheetList $balanceSheetList */
        $balanceSheetList = $companyFinanceCheck->getBalanceSheets($company->siren);

        if (null !== $balanceSheetList) {
            $logger->info('Last balance sheet date: ' . $balanceSheetList->getLastBalanceSheet()->getCloseDate()->format('Y-m-d H:i:s') .
                ' Number of days left: ' . (new \DateTime())->diff($balanceSheetList->getLastBalanceSheet()->getCloseDate())->days, $logContext);

            /** @var CompanyBalanceSheetManager $companyBalanceSheetManager */
            $companyBalanceSheetManager = $this->getContainer()->get('unilend.service.company_balance_sheet_manager');
            $companyBalanceSheetManager->setCompanyBalance($company, $project, $balanceSheetList);
        }

        if (null !== $balanceSheetList && (new \DateTime())->diff($balanceSheetList->getLastBalanceSheet()->getCloseDate())->days <= \company_balance::MAX_COMPANY_BALANCE_DATE) {
            if (true === $companyFinanceCheck->hasNegativeCapitalStock($balanceSheetList, $company->siren, $rejectionReason)) {
                $projectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::NOTE_EXTERNE_FAIBLE, $project, 0, $rejectionReason);

                return $rejectionReason;
            }

            if (true === $companyFinanceCheck->hasNegativeRawOperatingIncomes($balanceSheetList, $company->siren, $rejectionReason)) {
                $projectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::NOTE_EXTERNE_FAIBLE, $project, 0, $rejectionReason);

                return $rejectionReason;
            }
        }

        if (false === $companyScoringCheck->isXerfiUnilendOk($company->code_naf, $companyRatingHistory, $companyRating, $rejectionReason)) {
            $projectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::NOTE_EXTERNE_FAIBLE, $project, 0, $rejectionReason);

            return $rejectionReason;
        }

        if (false === $companyScoringCheck->combineAltaresScoreAndUnilendXerfi($altaresScore, $company->code_naf, $rejectionReason)) {
            $projectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::NOTE_EXTERNE_FAIBLE, $project, 0, $rejectionReason);

            return $rejectionReason;
        }

        if (true === $companyScoringCheck->isInfolegaleScoreLow($company->siren, $companyRatingHistory, $companyRating, $rejectionReason)) {
            $projectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::NOTE_EXTERNE_FAIBLE, $project, 0, $rejectionReason);

            return $rejectionReason;
        }

        if (false === $companyScoringCheck->combineEulerGradeUnilendXerfiAltaresScore($altaresScore, $company, $companyRatingHistory, $companyRating, $rejectionReason)) {
            $projectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::NOTE_EXTERNE_FAIBLE, $project, 0, $rejectionReason);

            return $rejectionReason;
        }

        if (true === $companyFinanceCheck->hasInfogreffePrivileges($company->siren, $rejectionReason)) {
            $projectManager->addProjectStatus(\users::USER_ID_CRON, \projects_status::NOTE_EXTERNE_FAIBLE, $project, 0, $rejectionReason);

            return $rejectionReason;
        }

        return true;
    }
}
