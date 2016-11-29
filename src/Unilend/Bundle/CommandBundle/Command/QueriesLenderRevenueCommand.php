<?php


namespace Unilend\Bundle\CommandBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bridge\Doctrine\DBAL\Connection;

class QueriesLenderRevenueCommand extends ContainerAwareCommand
{

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('queries:lender_revenue')
            ->setDescription('Extract revenue information for all lenders in a given year')
            ->addArgument(
                'year',
                InputArgument::REQUIRED,
                'year to export'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $year = $input->getArgument('year');
        /** @var Connection $dataBaseConnection */
        $dataBaseConnection  = $this->getContainer()->get('database_connection');
        /** @var \clients $clients */
        $clients  = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('clients');
        $filePath = $this->getContainer()->getParameter('path.protected') . '/' . 'requete_revenus' . date('Ymd') . '.csv';

        if (file_exists($filePath)){
            unlink($filePath);
        }

        /** @var \DateTime $yesterday */
        $yesterday = new \DateTime('NOW - 1 day');
        $yesterdayFilePath = $this->getContainer()->getParameter('path.protected') . '/' . 'requete_revenus' . $yesterday->format('Ymd') . '.csv';

        if (file_exists($yesterdayFilePath)){
            unlink($yesterdayFilePath);
        }

        $this->fillTemporaryTransactionsTable($dataBaseConnection, $year);

        /** @var \PHPExcel $csvFile */
        $csvFile     = new \PHPExcel();
        $activeSheet = $csvFile->setActiveSheetIndex(0);
        $row         = 1;

        $activeSheet->setCellValueByColumnAndRow(0, $row, 'Code Entreprise');
        $activeSheet->setCellValueByColumnAndRow(1, $row, 'CodeBénéficiaire');
        $activeSheet->setCellValueByColumnAndRow(2, $row, 'CodeV');
        $activeSheet->setCellValueByColumnAndRow(3, $row, 'Date');
        $activeSheet->setCellValueByColumnAndRow(4, $row, 'Montant');
        $activeSheet->setCellValueByColumnAndRow(5, $row, 'Monnaie');
        $activeSheet->setCellValueByColumnAndRow(6, $row, 'id_client');

        $row += 1;
        $this->addLoans($dataBaseConnection, $activeSheet, $clients, $year, $row);

        foreach ($this->getAllClientsWithTransactions($dataBaseConnection) as $client) {
            $clients->get($client['id_client']);
            $this->fillTemporaryLenderImpositionHistory($dataBaseConnection, $clients);
            $this->addRevenueBasedLines($dataBaseConnection, $activeSheet, $clients, $year, $row);
        }
        /** @var \PHPExcel_Writer_CSV $writer */
        $writer = \PHPExcel_IOFactory::createWriter($csvFile, 'CSV');
        $writer->setUseBOM(true);
        $writer->setDelimiter(';');
        $writer->save(str_replace(__FILE__, $filePath ,__FILE__));

        $this->emptyTemporaryLenderImpositionHistory($dataBaseConnection);
        $this->emptyTemporaryRepaymentTable($dataBaseConnection);
    }

    /**
     * @param Connection $dataBaseConnection
     * @param \PHPExcel_Worksheet $activeSheet
     * @param \clients $clients
     * @param int $year
     * @param int $row
     */
    private function addLoans(Connection $dataBaseConnection, \PHPExcel_Worksheet &$activeSheet, \clients $clients, $year, &$row)
    {
        $query = '
          SELECT
            c.id_client,
            SUM(lo.amount) AS montant
          FROM loans lo
            INNER JOIN
            (
              SELECT psh.id_project, MIN(psh.added) as first_added
              FROM projects_status_history psh
                INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
              WHERE ps.status = ' . \projects_status::REMBOURSEMENT . '
              GROUP BY psh.id_project
              HAVING YEAR(first_added) = :year
            ) p ON p.id_project = lo.id_project
            INNER JOIN lenders_accounts la ON la.id_lender_account = lo.id_lender
            INNER JOIN clients c ON la.id_client_owner = c.id_client
            GROUP BY c.id_client';

        $data = $dataBaseConnection->executeQuery($query, ['year' => $year])->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($data as $record) {
            $clients->get($record['id_client'], 'id_client');
            $this->addCommonCellValues($activeSheet, $row, $year, $clients);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '117');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format(($record['montant'] / 100), 2, ',', ''));
            $row += 1;
        }
    }

    /**
     * @param Connection $dataBaseConnection
     * @param \PHPExcel_Worksheet $activeSheet
     * @param \clients $clients
     * @param int $year
     * @param int $row
     */
    private function addRevenueBasedLines(Connection $dataBaseConnection, \PHPExcel_Worksheet &$activeSheet, \clients $clients, $year, &$row)
    {
        $query = '
              SELECT
                ROUND(SUM(t_interets.montant + (SELECT SUM(amount) FROM tax WHERE tax.id_transaction = t_interets.id_transaction)) /100, 2) AS sum53,
                ROUND(SUM(retenues_source.amount)/ 100, 2) AS sum2,
                ROUND(SUM(prelevements_obligatoires.amount)/ 100, 2) AS sum54,
                ROUND(SUM(IF(c.type IN (' . implode(',', [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER ]) . ') AND tlih.id_pays = 1, t_interets.montant + (SELECT SUM(tax.amount) FROM tax WHERE id_transaction = t_interets.id_transaction), 0))/100, 2) AS sum66,
                ROUND(SUM(IF(c.type IN (' . implode(',', [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER ]) . ') AND tlih.id_pays IN (6,14,21,31,41,50,52,60,61,65,70,79,84,87,98,103,104,111,139,142,143,148,150,151,165,166,171), t_interets.montant, 0))/100, 2) AS sum81,
                ROUND(SUM(IF(c.type IN (' . implode(',', [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER ]) . ') AND tlih.id_pays IN (6,14,21,31,41,50,52,60,61,65,70,79,84,87,98,103,104,111,139,142,143,148,150,151,165,166,171), t_capital.montant, 0))/100, 2) AS sum82,
                ROUND(SUM(IF(t_capital.type_transaction = ' . \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT . ', t_capital.montant / 0.844, t_capital.montant))/100, 2) AS sum118
              FROM clients c
                INNER JOIN temporary_lender_repayment_transactions t_capital ON c.id_client = t_capital.id_client AND t_capital.type_transaction IN (' . implode(',', [\transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL, \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT ]) . ')
                LEFT JOIN temporary_lender_repayment_transactions t_interets ON t_capital.id_echeancier = t_interets.id_echeancier AND t_interets.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . '
                LEFT JOIN tax retenues_source ON retenues_source.id_transaction = t_interets.id_transaction AND retenues_source.id_tax_type = ' . \tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE . ' 
                LEFT JOIN tax prelevements_obligatoires ON prelevements_obligatoires.id_transaction = t_interets.id_transaction AND prelevements_obligatoires.id_tax_type = ' . \tax_type::TYPE_INCOME_TAX . '
                LEFT JOIN temporary_lender_imposition_history tlih ON t_interets.id_transaction = tlih.id_transaction AND c.id_client = tlih.id_client
             WHERE c.id_client = :clientId';

        $data = $dataBaseConnection->executeQuery($query, ['clientId' => $clients->id_client])->fetchAll(\PDO::FETCH_ASSOC)[0];

        if ($data['sum53'] > 0) {
            $this->addCommonCellValues($activeSheet, $row, $year, $clients);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '53');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($data['sum53'], 2, ',', ''));
            $row += 1;
        }

        if ($data['sum2'] > 0) {
            $this->addCommonCellValues($activeSheet, $row, $year, $clients);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '2');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($data['sum2'], 2, ',', ''));
            $row += 1;
        }

        if ($data['sum54'] > 0) {
            $this->addCommonCellValues($activeSheet, $row, $year, $clients);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '54');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($data['sum54'], 2, ',', ''));
            $row += 1;
        }

        if ($data['sum66'] > 0) {
            $this->addCommonCellValues($activeSheet, $row, $year, $clients);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '66');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($data['sum66'], 2, ',', ''));
            $row += 1;
        }

        if ($data['sum81'] > 0) {
            $this->addCommonCellValues($activeSheet, $row, $year, $clients);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '81');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($data['sum81'], 2, ',', ''));
            $row += 1;
        }

        if ($data['sum82'] > 0) {
            $this->addCommonCellValues($activeSheet, $row, $year, $clients);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '82');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($data['sum82'], 2, ',', ''));
            $row += 1;
        }

        if ($data['sum118'] > 0) {
            $this->addCommonCellValues($activeSheet, $row, $year, $clients);
            $activeSheet->setCellValueByColumnAndRow(2, $row, '118');
            $activeSheet->setCellValueByColumnAndRow(4, $row, number_format($data['sum118'], 2, ',', ''));
            $row += 1;
        }
    }

    /**
     * @param \PHPExcel_Worksheet $activeSheet
     * @param int $row
     * @param int $year
     * @param \clients $clients
     */
    private function addCommonCellValues(\PHPExcel_Worksheet &$activeSheet, $row, $year, \clients $clients)
    {
        $commonValues = [
            'CodeEntreprise' => 1, //official code of SFPMEI
            'Date'           => '31/12/' . $year,
            'Monnaie'        => 'EURO',
        ];

        $activeSheet->setCellValueByColumnAndRow(0, $row, $commonValues['CodeEntreprise']);
        $activeSheet->setCellValueByColumnAndRow(1, $row, $clients->getLenderPattern($clients->id_client));
        $activeSheet->setCellValueByColumnAndRow(3, $row, $commonValues['Date']);
        $activeSheet->setCellValueByColumnAndRow(5, $row, $commonValues['Monnaie']);
        $activeSheet->setCellValueByColumnAndRow(6, $row, $clients->id_client);
    }

    private function fillTemporaryTransactionsTable(Connection $dataBaseConnection, $year)
    {
        $dataBaseConnection->executeQuery('TRUNCATE temporary_lender_repayment_transactions');
        $dataBaseConnection->executeQuery('INSERT INTO temporary_lender_repayment_transactions (id_transaction, id_client, type_transaction, id_echeancier, montant, date_transaction)
          SELECT
            id_transaction,
            id_client,
            type_transaction,
            id_echeancier,
            montant,
            date_transaction
          FROM transactions
          WHERE LEFT(date_transaction, 4) = ' . $year . '
                AND type_transaction IN (' . implode(',', [
                \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS,
                \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL,
                \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT
            ]) . ')');
    }

    private function emptyTemporaryRepaymentTable(Connection $dataBaseConnection)
    {
        $dataBaseConnection->executeQuery('TRUNCATE temporary_lender_repayment_transactions');
    }

    private function fillTemporaryLenderImpositionHistory(Connection $dataBaseConnection, \clients $clients)
    {
        $dataBaseConnection->executeQuery('INSERT INTO temporary_lender_imposition_history (id_client, id_transaction, id_pays)
                                            SELECT
                                              t.id_client,
                                              t.id_transaction,
                                              (SELECT id_pays FROM lenders_imposition_history WHERE id_lender = la.id_lender_account AND added <= t.date_transaction ORDER BY added DESC LIMIT 1) AS id_pays
                                            FROM temporary_lender_repayment_transactions t
                                              INNER JOIN lenders_accounts la ON t.id_client = la.id_client_owner
                                            WHERE type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . ' AND t.id_client = ' . $clients->id_client);
    }

    private function emptyTemporaryLenderImpositionHistory(Connection $dataBaseConnection)
    {
        $dataBaseConnection->executeQuery('TRUNCATE temporary_lender_imposition_history');
    }

    private function getAllClientsWithTransactions(Connection $dataBaseConnection)
    {
        return $dataBaseConnection->executeQuery('SELECT id_client FROM temporary_lender_repayment_transactions GROUP BY id_client')->fetchAll(\PDO::FETCH_ASSOC);
    }
}
