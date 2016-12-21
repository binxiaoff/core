<?php


namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bridge\Doctrine\DBAL\Connection;


class DevCreateWalletsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('dev:migrate:create_wallet')
            ->setDescription('Create all wallets for lenders and borrowers');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Connection $dataBaseConnection */
        $dataBaseConnection  = $this->getContainer()->get('database_connection');

        $date = new \DateTime('NOW');
        $now = $date->format('Y-m-d H:i:s');

        $query =
            'SELECT
              id_client_owner AS id_client,
              id_lender_account
            FROM lenders_accounts
              WHERE id_client_owner NOT IN (SELECT id_client from wallet)
            UNION
            SELECT
              c.id_client,
              null
            FROM projects p
              INNER JOIN companies co ON p.id_company = co.id_company
              INNER JOIN clients c ON co.id_client_owner = c.id_client
            WHERE c.id_client NOT IN (SELECT id_client from wallet)
            ORDER BY id_client ASC
            LIMIT 10000';

        $statement = $dataBaseConnection->executeQuery($query);

        while ($client = $statement->fetch(\PDO::FETCH_ASSOC)) {

            $dataBaseConnection->beginTransaction();
            try {
                /** @var \clients $clientRepo */
                $clientRepo = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('clients');

                if (false === empty($client['id_lender_account'])) {
                    $lenderPattern = $clientRepo->getLenderPattern($client['id_client']);
                    $idWallet = $this->createLenderWallet($dataBaseConnection, $client['id_client'], $lenderPattern, $now);
                    $this->saveAccountMatching($dataBaseConnection, $idWallet, $client['id_lender_account']);
                } else {
                    $this->createBorrowerWallet($dataBaseConnection, $client['id_client'], $now);
                }
                $dataBaseConnection->commit();

            } catch (\Exception $exception) {
                $dataBaseConnection->rollBack();
                throw $exception;
            }
        }
        $statement->closeCursor();
    }

    private function createLenderWallet(Connection $dataBaseConnection, $idClient, $lenderPattern, $now)
    {
        $query = 'INSERT INTO wallet (id_client, id_type, wire_transfer_pattern, available_balance, committed_balance, added, updated) 
                    VALUES (:idClient, 1, :motif, 0, null, :now, :now)';
        $dataBaseConnection->executeQuery($query, ['idClient' => $idClient, 'motif' => $lenderPattern, 'now' => $now]);

        return $dataBaseConnection->lastInsertId();
    }

    private function saveAccountMatching(Connection $dataBaseConnection, $idWallet, $idLenderAccount)
    {
        $query = 'INSERT INTO account_matching (id_wallet, id_lender_account) 
                    VALUES (:idWallet, :idLenderAccount)';
        $dataBaseConnection->executeQuery($query, ['idWallet' => $idWallet, 'idLenderAccount' => $idLenderAccount]);
    }

    private function createBorrowerWallet(Connection $dataBaseConnection, $idClient, $now)
    {
        $query = 'INSERT INTO wallet (id_client, id_type, wire_transfer_pattern, available_balance, committed_balance, added, updated) 
                    VALUES (:idClient, 2, null, 0, null, :now, :now)';
        $dataBaseConnection->executeQuery($query, ['idClient' => $idClient, 'now' => $now]);
    }
}
