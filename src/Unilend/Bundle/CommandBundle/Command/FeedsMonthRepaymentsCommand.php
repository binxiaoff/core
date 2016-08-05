<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class FeedsMonthRepaymentsCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('feeds:month_repayments')
            ->setDescription('Extract lender repayments of the month')
            ->addArgument(
                'day',
                InputArgument::OPTIONAL,
                'Day of the lender repayments to export (format: Y-m-d)'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '1G');

        $previousDay = $input->getArgument('day');
        if (false === empty($previousDay) && 1 === preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $previousDay)) {
            $previousDay = \DateTime::createFromFormat('Y-m-d', $previousDay);
        } else {
            $previousDay = new \DateTime();
            $previousDay->sub(new \DateInterval('P1D'));
        }

        $output->writeln('Generating repayment file for ' . $previousDay->format('Y-m-d'));

        $aHeader = array (
            0 => 'id_client',
            1 => 'id_lender_account',
            2 => 'type',
            3 => 'iso_pays',
            4 => 'taxed_at_source',
            5 => 'exonere',
            6 => 'annees_exoneration',
            7 => 'id_project',
            8 => 'id_loan',
            9 => 'type_loan',
            10 => 'ordre',
            11 => 'montant',
            12 => 'capital',
            13 => 'interets',
            14 => 'prelevements_obligatoires',
            15 => 'retenues_source',
            16 => 'csg',
            17 => 'prelevements_sociaux',
            18 => 'contributions_additionnelles',
            19 => 'prelevements_solidarite',
            20 => 'crds',
            21 => 'date_echeance',
            22 => 'date_echeance_reel',
            23 => 'status_remb_preteur',
            24 => 'date_echeance_emprunteur',
            25 => 'date_echeance_emprunteur_reel'
        );

        $sftpPath      = $this->getContainer()->getParameter('path.sftp');
        $dayFileName   = 'echeances_' . $previousDay->format('Ymd') . '.csv';
        $monthFileName = 'echeances_' . $previousDay->format('Ym') . '.csv';
        $dayFilePath   = $sftpPath . 'sfpmei/emissions/etat_fiscal/' . $previousDay->format('Ym');
        $monthFilePath = $sftpPath . 'sfpmei/emissions/etat_fiscal/';

        if (false === is_dir($dayFilePath)) {
            mkdir($dayFilePath);
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \echeanciers $repayment */
        $repayment = $entityManager->getRepository('echeanciers');

        try {
            $aResult = $repayment->getTaxState($previousDay);
        } catch (\Exception $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->getContainer()->get('monolog.logger.console');
            $logger->error('Could not get tax state on date : ' . $previousDay->format('Y-m-d') . '. Exception message: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__]);
            return;
        }

        /** @var \PHPExcel $oDocument */
        $document     = new \PHPExcel();
        /** @var \PHPExcel_Worksheet $oActiveSheet */
        $activeSheet = $document->setActiveSheetIndex(0);

        foreach ($aHeader as $iIndex => $sColumn) {
            $activeSheet->setCellValueByColumnAndRow($iIndex, 1, $sColumn);
        }

        foreach ($aResult as $iRowIndex => $aRow) {
            $iColIndex = 0;
            foreach ($aRow as $ColValue) {
                $activeSheet->setCellValueByColumnAndRow($iColIndex++, $iRowIndex + 2, $ColValue);
            }
        }
        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = \PHPExcel_IOFactory::createWriter($document, 'CSV');
        $writer->setDelimiter(';')->save($dayFilePath . '/' . $dayFileName);
        // Add the content of the daily file we generated at the en of the monthly file
        $outputFile = fopen($monthFilePath . $monthFileName, 'w');

        foreach (glob($dayFilePath . '/echeances_*.csv') as $sFile) {
            fwrite($outputFile, file_get_contents($sFile));
        }
        fclose($outputFile);
    }
}
