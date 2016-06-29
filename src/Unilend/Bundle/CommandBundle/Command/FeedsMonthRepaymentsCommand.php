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

        $headers       = "id_client;id_lender_account;type;iso_pays;exonere;annees_exoneration;id_project;id_loan;type_loan;ordre;montant;capital;interets;prelevements_obligatoires;retenues_source;csg;prelevements_sociaux;contributions_additionnelles;prelevements_solidarite;crds;date_echeance;date_echeance_reel;status_remb_preteur;date_echeance_emprunteur;date_echeance_emprunteur_reel;\n";
        $dayCSV        = '';

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

        foreach ($aResult as $aRow) {
            $dayCSV .= implode(';', $aRow) . "\n";
        }

        file_put_contents($dayFilePath . '/' . $dayFileName, $dayCSV);

        $outputFile = fopen($monthFilePath . $monthFileName, 'w');
        fwrite($outputFile, $headers);
        foreach (glob($dayFilePath . '/echeances_*.csv') as $sFile) {
            fwrite($outputFile, file_get_contents($sFile));
        }
        fclose($outputFile);
    }
}
