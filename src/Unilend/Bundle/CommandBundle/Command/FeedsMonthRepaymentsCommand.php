<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Box\Spout\Common\Type;
use Box\Spout\Writer\CSV\Writer;
use Box\Spout\Writer\WriterFactory;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FeedsMonthRepaymentsCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('unilend:feeds_out:monthly_repayments:generate')
            ->setDescription('Extract lender repayments of the month')
            ->addArgument(
                'month',
                InputArgument::OPTIONAL,
                'Month of the lender repayments to export (format: Y-m)'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $month = $input->getArgument('month');
        if (false === empty($month) && 1 === preg_match('/^[0-9]{4}-[0-9]{2}$/', $month)) {
            $month = \DateTime::createFromFormat('Y-m', $month);
        } else {
            $month = new \DateTime();
            $month->modify('first day of last month');
        }

        $monthFilePath = $this->getContainer()->getParameter('path.sftp') . 'sfpmei/emissions/etat_fiscal/echeances_' . $month->format('Ym') . '.csv';

        $output->writeln('Generating repayment file for ' . $month->format('Y-m'));

        $header = [
            'id_client',
            // fiscal information
            'type',
            'resident_fiscal',
            'taxed_at_source',
            'exonere',
            'annees_exoneration',
            // loan information
            'id_project',
            'id_loan',
            'type_loan',
            // theoretical repayment schedule information
            'ordre',
            'capital',
            'interets',
            'status_echeance',
            'date_echeance',
            'date_echeance_emprunteur',
            //repayment information
            'type_remboursement',
            'capital_rembourse',
            'interets_rembourse',
            'date_rembourse',
            'date_echeance_emprunteur_reel',
            // tax
            'prelevements_obligatoires',
            'retenues_source',
            'csg',
            'prelevements_sociaux',
            'contributions_additionnelles',
            'prelevements_solidarite',
            'crds'
        ];

        try {
            $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
            $result        = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory')->getMonthlyRepayments($month);

            /** @var Writer $writer */
            $writer = WriterFactory::create(Type::CSV);
            $writer->setFieldDelimiter(';')
                ->openToFile($monthFilePath)
                ->addRow($header)
                ->addRows($result)
                ->close();
        } catch (\Exception $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->getContainer()->get('monolog.logger.console');
            $logger->error('Could not get repayment schedule including tax on ' . $month->format('Y-m') . '. Exception message: ' . $exception->getMessage(),
                ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]);
            return;
        }
    }
}
