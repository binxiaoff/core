<?php


namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager;

class DevMigrateLenderAccountCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('dev:migrate:lenders_accounts')
            ->setDescription('Migrate lenders_accounts data to wallet and bank_account');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Connection $dataBaseConnection */
        $dataBaseConnection  = $this->getContainer()->get('database_connection');

        $date = new \DateTime('NOW');
        $now = $date->format('Y-m-d H:i:s');

        $query =    'SELECT
                      w.id,
                      w.id_client,
                      la.bic,
                      la.iban
                    FROM wallet w
                      INNER JOIN lenders_accounts la ON w.id_client = la.id_client_owner
                      WHERE w.id_type = 1';

        $statement = $dataBaseConnection->executeQuery($query);

        while ($wallet = $statement->fetch(\PDO::FETCH_ASSOC)) {

            $dataBaseConnection->beginTransaction();
            try {
                $idBankAccount = $this->saveBankAccount($dataBaseConnection, $wallet['id_client'], $wallet['bic'], $wallet['iban'], $now);
                $this->createBankAccountUsage($dataBaseConnection, $wallet['id'], $idBankAccount, $now);
                $dataBaseConnection->commit();

            } catch (\Exception $exception) {
                $dataBaseConnection->rollBack();
                throw $exception;
            }
        }
        $statement->closeCursor();
    }


    private function saveBankAccount(Connection $dataBaseConnection, $idClient, $bic, $iban, $now)
    {
        $query = 'INSERT INTO bank_account (id_client, bic, iban, added, updated) 
                    VALUES (:idClient, :bic, :iban, :now, :now)';
        $dataBaseConnection->executeQuery($query, ['idClient' => $idClient, 'bic' => $bic, 'iban' => $iban, 'now' => $now]);
        return $dataBaseConnection->lastInsertId();
    }

    private function createBankAccountUsage(Connection $dataBaseConnection, $idWallet, $idBankAccount, $now)
    {
        $query = 'INSERT INTO bank_account_usage (id_wallet, id_bank_account, id_usage_type, added, updated) 
                    VALUES (:idWallet, :idBankAccount, 1, :now, :now)';
        $dataBaseConnection->executeQuery($query, ['idWallet' => $idWallet, 'idBankAccount' => $idBankAccount, 'now' => $now]);
    }

}

