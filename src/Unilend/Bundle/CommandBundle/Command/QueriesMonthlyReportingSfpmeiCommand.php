<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;

class QueriesMonthlyReportingSfpmeiCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('queries:monthly_reporting_sfpmei')
            ->setDescription('Create monthly reporting file for SFPMEI')
            ->addArgument(
                'day',
                InputArgument::OPTIONAL,
                'last day of the month to export (format: Y-m-d)'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date    = $input->getArgument('day');
        $endDate = null;

        if (false === empty($date)) {
            if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)) {
                $endDate = \DateTime::createFromFormat('Y-m-d', $date);
            }
        } else {
            $endDate = new \DateTime('Last day of last month');
        }

        if (null === $endDate) {
            $output->writeln('<error>Wrong date format ("Y-m-d" expected)</error>');
            return;
        }
        $startDate = new \DateTime('First day of' . $endDate->format('F Y'));

        $entityManager                 = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationRepository           = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $projectRepository             = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $wireTransferInRepository      = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions');
        $repaymentRepository           = $entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $clientStatusHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory');
        $companiesRepository           = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies');

        $failedCreditCardProvision = $entityManager->getRepository('UnilendCoreBusinessBundle:Backpayline')->getCountFailedTransactionsBetweenDates($startDate, $endDate);
        $creditCardProvisions      = [];
        $wireTransferInProvisions  = [];
        foreach ($operationRepository->getLenderProvisionIndicatorsBetweenDates($startDate, $endDate) as $indicator) {
            switch ($indicator['provisionType']) {
                case 'creditCard':
                    $creditCardProvisions = $indicator;
                    break;
                case 'wireTransferIn':
                    $wireTransferInProvisions = $indicator;
                    break;
                default;
                    break;
            }
        }
        $lenderWithdrawIndicators             = $operationRepository->getLenderWithdrawIndicatorsBetweenDates($startDate, $endDate);

        $fundingIndicators                    = [
            'totalAmount'     => $projectRepository->getIndicatorBetweenDates('SUM(p.amount) AS amount', $startDate, $endDate, ProjectsStatus::REMBOURSEMENT)['amount'],
            'number'          => $projectRepository->getIndicatorBetweenDates('COUNT(p.id_project) AS number', $startDate, $endDate, ProjectsStatus::REMBOURSEMENT)['number'],
            'avgPeriod'       => $projectRepository->getIndicatorBetweenDates('AVG(p.period) AS avgPeriod', $startDate, $endDate, ProjectsStatus::REMBOURSEMENT)['avgPeriod'],
            'avgInterestRate' => $projectRepository->getIndicatorBetweenDates('AVG(p.interest_rate) AS avgInterestRate', $startDate, $endDate, ProjectsStatus::REMBOURSEMENT)['avgInterestRate']
        ];
        $projectsInRepayment                  = $projectRepository->findProjectsInRepaymentAtDate($endDate);
        $remainingDueCapitalRunningRepayments = $operationRepository->getRemainingDueCapitalForProjects($endDate, array_column($projectsInRepayment, 'id_project'));
        $repaymentsInMonth                    = $repaymentRepository->findRepaidRepaymentsBetweenDates($startDate, $endDate);
        $sumRepaymentsInMonth                 = $repaymentRepository->getSumRepaidRepaymentsBetweenDates($startDate, $endDate);
        $repaymentFinishedProjects            = $projectRepository->findProjectsHavingHadStatusBetweenDates([
            ProjectsStatus::REMBOURSE,
            ProjectsStatus::REMBOURSEMENT_ANTICIPE
        ], $startDate, $endDate);
        $rejectWireTransfersIn                = $wireTransferInRepository->getRejectedDirectDebitIndicatorsBetweenDates($startDate, $endDate);
        $regularizationWireTransfers          = $wireTransferInRepository->getBorrowerProvisionRegularizationIndicatorsBetweenDates($startDate, $endDate);
        $lateRepayments                       = $entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')->getLateRepaymentIndicators($endDate);
        $projectsInDebtCollection             = $projectRepository->findProjectsWithDebtCollectionMissionBetweenDates(new \DateTime('January 2013'), $endDate);
        $remainingDueCapitalInDebtCollection  = $operationRepository->getRemainingDueCapitalForProjects($endDate, array_column($projectsInDebtCollection, 'id_project'));
        $projectsInCollectiveProceeding       = $projectRepository->findProjectsHavingHadCompanyStatusInCollectiveProceeding(new \DateTime('January 2013'), $endDate);
        $companiesInCollectiveProceeding      = $companiesRepository->getCountCompaniesInCollectiveProceedingBetweenDates($startDate, $endDate);
        $newlyRiskAnalysisProjects            = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory')->getCountProjectsInRiskReviewBetweenDates($startDate, $endDate);
        $newlyPresentedProjects               = $projectRepository->getIndicatorBetweenDates('COUNT(p.id_project) AS newProjects', $startDate, $endDate, ProjectsStatus::EN_FUNDING)['newProjects'];
        $totalNewLenders                      = $clientStatusHistoryRepository->countLendersValidatedBetweenDatesByType($startDate, $endDate, [
            Clients::TYPE_PERSON,
            Clients::TYPE_PERSON_FOREIGNER,
            Clients::TYPE_LEGAL_ENTITY,
            Clients::TYPE_LEGAL_ENTITY_FOREIGNER
        ]);
        $newLendersPerson                     = $clientStatusHistoryRepository->countLendersValidatedBetweenDatesByType($startDate, $endDate, [
            Clients::TYPE_PERSON,
            Clients::TYPE_PERSON_FOREIGNER
        ]);
        $newLenderLegalEntity                 = $clientStatusHistoryRepository->countLendersValidatedBetweenDatesByType($startDate, $endDate, [
            Clients::TYPE_LEGAL_ENTITY,
            Clients::TYPE_LEGAL_ENTITY_FOREIGNER
        ]);
        $lenderWithProvision                  = $operationRepository->getLenderProvisionIndicatorsBetweenDates($startDate, $endDate, false, true)[0]['numberLenders'];
        $totalLenders                         = $clientStatusHistoryRepository->countLendersValidatedBetweenDatesByType(new \DateTime('January 2013'), $endDate);
        $totalLendersPerson                   = $clientStatusHistoryRepository->countLendersValidatedBetweenDatesByType(new \DateTime('January 2013'), $endDate, [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER]);
        $totalLendersLegalEntity              = $clientStatusHistoryRepository->countLendersValidatedBetweenDatesByType(new \DateTime('January 2013'), $endDate, [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER]);
        $lendersWithProvisionAndNoValidBid    = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findLendersWithProvisionButWithoutAcceptedBidBetweenDates(new \DateTime('January 2013'), $endDate);
        $totalLenderProvisionIndicators       = $operationRepository->getLenderProvisionIndicatorsBetweenDates(new \DateTime('January 2013'), $endDate, false)[0];

        $document = new \PHPExcel();
        $document->getDefaultStyle()->getFont()->setName('Arial');
        $document->getDefaultStyle()->getFont()->setSize(11);
        $activeSheet = $document->setActiveSheetIndex(0);

        $activeSheet->setCellValue('A1', 'Mois');
        $activeSheet->setCellValue('B1', $endDate->format('Y-m'));

        $indicators = [
            5  => ['Nombre de demandes d’autorisations CB échouées' => $failedCreditCardProvision],
            6  => ['Nombre de chargements CB' => $creditCardProvisions['numberProvisions']],
            7  => ['Montant de chargements CB' => $creditCardProvisions['totalAmount']],
            8  => ['Chargement CB moyen' => $creditCardProvisions['averageAmount']],
            10 => ['Nombre de chargements par virement' => $wireTransferInProvisions['numberProvisions']],
            11 => ['Montant de chargements par virement' => $wireTransferInProvisions['totalAmount']],
            12 => ['Chargement par virement moyen' => $wireTransferInProvisions['averageAmount']],
            13 => ['Total Chargements' => bcadd($wireTransferInProvisions['totalAmount'], $creditCardProvisions['totalAmount'], 2)],
            18 => ['Nombre des retraits prêteurs (virement sur leur compte)' => $lenderWithdrawIndicators['numberWithdraw']],
            19 => ['Montant des retraits prêteurs (virement sur leur compte)' => $lenderWithdrawIndicators['totalAmount']],
            22 => ['Nombre de financements réalisés' => $fundingIndicators['number']],
            23 => ['Montant de financements réalisés' => $fundingIndicators['totalAmount']],
            24 => ['Durée moyenne des financements réalisés (mois)' => $fundingIndicators['avgPeriod']],
            25 => ['Taux d\'intérêt moyen (arithmétique) des financements réalisés (%)' => $fundingIndicators['avgInterestRate']],
            27 => ['Nombre de financements en cours' => count($projectsInRepayment)],
            28 => ['Montant initial des financements en cours' => array_sum(array_column($projectsInRepayment, 'amount'))],
            29 => ['Montant restant dû des financements en cours' => $remainingDueCapitalRunningRepayments],
            31 => ['Nombre de remboursements mensuels des emprunteurs' => count($repaymentsInMonth)],
            32 => ['Montant de remboursements mensuels des emprunteurs' => $sumRepaymentsInMonth],
            34 => ['Nombre de financements clos' => count($repaymentFinishedProjects)],
            35 => ['Montant de financements clos' => array_sum(array_column($repaymentFinishedProjects, 'amount'))],
            37 => ['Nombre de rejets de prélèvements emprunteurs' => $rejectWireTransfersIn['number']],
            38 => ['Montant des rejets de prélèvements emprunteurs' => $rejectWireTransfersIn['amount']],
            40 => ['Nombre de rejets régularisés' => $regularizationWireTransfers['number']],
            41 => ['Montant de rejets régularisés' => $regularizationWireTransfers['amount']],
            42 => ['Nombre d\'échéances en recouvrement interne (au dernier jour du mois)' => $lateRepayments['projectCount']],
            43 => ['Montant des échéances en recouvrement interne' => $lateRepayments['lateAmount']],
            44 => ['Nombre cumulé de financements envoyés à la société de recouvrement (MCS = DdT)' => count($projectsInDebtCollection)],
            45 => ['Montant cumulé (capital) des financements envoyés à la société de recouvrement' => array_sum(array_column($projectsInDebtCollection, 'amount'))],
            46 => ['Montant restant dû des financements envoyés à la société de recouvrement' => $remainingDueCapitalInDebtCollection],
            47 => ['Nombre cumulé de financements en procédure collective (entreprises)' => $companiesInCollectiveProceeding],
            48 => ['Montant cumulé (capital) de financements en procédure collective (montant financé)' => array_sum(array_column($projectsInCollectiveProceeding, 'amount'))],
            50 => ['Nombre de projets analysés par le Comité des Risques' => $newlyRiskAnalysisProjects],
            51 => ['Nombre de projets présentés aux prêteurs en ligne' => $newlyPresentedProjects],
            54 => ['Nombre de prêteurs ouverts au cours du mois (KYC validé)' => $totalNewLenders],
            55 => ['Personnes physiques' => $newLendersPerson],
            56 => ['Personnes morales' => $newLenderLegalEntity],
            57 => ['Nombre de prêteurs chargés au cours du mois' => $lenderWithProvision],
            58 => ['Nombre moyen de chargements par prêteur' => bcdiv(bcadd($creditCardProvisions['numberProvisions'], $wireTransferInProvisions['numberProvisions'], 2), $lenderWithProvision, 2)],
            60 => ['Nombre de prêteurs ouverts (KYC validé / statut en ligne)' => $totalLenders],
            61 => ['Personnes physiques' => $totalLendersPerson],
            62 => ['Personnes morales' => $totalLendersLegalEntity],
            63 => ['Nombre de prêteurs ayant enregistré au moins un chargement' => $totalLenderProvisionIndicators['numberLenders']],
            64 => ['dont ceux n’ayant eu aucune enchère acceptée' => count($lendersWithProvisionAndNoValidBid)]
        ];

        foreach ($indicators as $row => $indicator) {
            foreach ($indicator as $name => $value) {
                $activeSheet->setCellValue('A' . $row, $name);
                $activeSheet->setCellValueExplicit('B' . $row, empty($value) ? 0 : $value, \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            }
        }

        $filePath = $this->getContainer()->getParameter('path.protected') . '/queries/' . 'reporting_mensuel_sfpmei_' . $endDate->format('Ymd') . '.xlsx';

        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = \PHPExcel_IOFactory::createWriter($document, 'Excel2007');
        $writer->save(str_replace(__FILE__, $filePath ,__FILE__));
    }
}
