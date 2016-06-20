<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bridge\Doctrine\DBAL\Connection;

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

        /** @var Connection $bdd */
        $bdd = $this->getContainer()->get('doctrine.dbal.default_connection');

        $previousDay = $input->getArgument('day');
        if (false === empty($previousDay) && 1 === preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $previousDay)) {
            $previousDay = \DateTime::createFromFormat('Y-m-d', $previousDay);
        } else {
            $previousDay = new \DateTime();
            $previousDay->sub(new \DateInterval('P1D'));
        }

        $output->writeln('Generating repayment file for ' . $previousDay->format('Y-m-d'));

        $headers       = "id_client;id_lender_account;type;iso_pays;exonere;debut_exoneration;fin_exoneration;id_project;id_loan;type_loan;ordre;montant;capital;interets;prelevements_obligatoires;retenues_source;csg;prelevements_sociaux;contributions_additionnelles;prelevements_solidarite;crds;date_echeance;date_echeance_reel;status_remb_preteur;date_echeance_emprunteur;date_echeance_emprunteur_reel;\n";
        $dayCSV        = '';
        $query         = '
            SELECT
                c.id_client,
                la.id_lender_account,
                c.type,
                IFNULL(
                    (
                        SELECT p.iso
                        FROM lenders_imposition_history lih
                        JOIN pays_v2 p ON p.id_pays = lih.id_pays
                        WHERE lih.added <= e.date_echeance_reel
                        AND lih.id_lender = e.id_lender
                        ORDER BY lih.added DESC
                        LIMIT 1
                    ), "FR"
                ) AS iso_pays,
                la.exonere,
                la.debut_exoneration,
                la.fin_exoneration,
                e.id_project,
                e.id_loan,
                l.id_type_contract,
                e.ordre,
                REPLACE(e.montant, ".", ","),
                REPLACE(e.capital, ".", ","),
                REPLACE(e.interets, ".", ","),
                REPLACE(e.prelevements_obligatoires, ".", ","),
                REPLACE(e.retenues_source, ".", ","),
                REPLACE(e.csg, ".", ","),
                REPLACE(e.prelevements_sociaux, ".", ","),
                REPLACE(e.contributions_additionnelles, ".", ","),
                REPLACE(e.prelevements_solidarite, ".", ","),
                REPLACE(e.crds, ".", ","),
                e.date_echeance,
                e.date_echeance_reel,
                e.status,
                e.date_echeance_emprunteur,
                e.date_echeance_emprunteur_reel
            FROM echeanciers e
            LEFT JOIN loans l ON l.id_loan = e.id_loan
            LEFT JOIN lenders_accounts la ON la.id_lender_account = e.id_lender
            LEFT JOIN clients c ON c.id_client = la.id_client_owner
            LEFT JOIN clients_adresses ca ON ca.id_client = c.id_client
            LEFT JOIN pays_v2 p ON p.id_pays = ca.id_pays_fiscal
            WHERE DATE(e.date_echeance_reel) = "' . $previousDay->format('Y-m-d') . '"
                AND e.status = 1
                AND e.status_ra = 0
            ORDER BY e.date_echeance ASC';

        $sftpPath      = $this->getContainer()->getParameter('path.sftp');
        $dayFileName   = 'echeances_' . $previousDay->format('Ymd') . '.csv';
        $monthFileName = 'echeances_' . $previousDay->format('Ym') . '.csv';
        $dayFilePath   = $sftpPath . 'sfpmei/emissions/etat_fiscal/' . $previousDay->format('Ym');
        $monthFilePath = $sftpPath . 'sfpmei/emissions/etat_fiscal/';

        if (false === is_dir($dayFilePath)) {
            mkdir($dayFilePath);
        }

        $result = $bdd->query($query);
        while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
            $dayCSV .= implode(';', $row) . "\n";
        }

        $output->writeln($result->rowCount() . ' rows exported');

        file_put_contents($dayFilePath . '/' . $dayFileName, $dayCSV);

        $outputFile = fopen($monthFilePath . $monthFileName, 'w');
        fwrite($outputFile, $headers);
        foreach (glob($dayFilePath . '/echeances_*.csv') as $sFile) {
            fwrite($outputFile, file_get_contents($sFile));
        }
        fclose($outputFile);
    }
}
