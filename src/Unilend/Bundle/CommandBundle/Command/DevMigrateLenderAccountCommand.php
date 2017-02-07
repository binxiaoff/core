<?php


namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Repository\BankAccountRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\BankAccountManager;

class DevMigrateLenderAccountCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('dev:migrate:lenders_accounts')->setDescription('Migrate lenders_accounts data to wallet and bank_account');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var BankAccountRepository $bankAccountRepository */
        $bankAccountRepository = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:BankAccount');

        /** @var Connection $dataBaseConnection */
        $dataBaseConnection = $this->getContainer()->get('database_connection');

        $query = 'SELECT
              w.id,
              w.id_client,
              la.bic,
              la.iban,
              (SELECT added FROM clients_status_history WHERE id_client_status = 6 and id_client = w.id_client ORDER BY added DESC, id_client_status_history DESC LIMIT 1) AS validation_date
            FROM wallet w
              INNER JOIN lenders_accounts la ON w.id_client = la.id_client_owner
            WHERE w.id_type = 1
            AND NOT EXISTS(SELECT * FROM bank_account WHERE id_client = w.id_client)';

        $bankInformation = $dataBaseConnection->executeQuery($query)->fetchAll(\PDO::FETCH_ASSOC);

        try {
            foreach ($bankInformation as $bankInfo) {
                $dataBaseConnection->beginTransaction();

                $bankAccount = $bankAccountRepository->saveBankAccount($bankInfo['id_client'], $bankInfo['bic'], $bankInfo['iban']);
                if (false === empty($bankInfo['validation_date'])) {
                    $validationDate = new \DateTime($bankInfo['validation_date']);
                    $bankAccount->setStatus(BankAccount::STATUS_VALIDATED);
                    $bankAccount->setAdded($validationDate);
                    $this->getContainer()->get('doctrine.orm.entity_manager')->flush();
                }
                $dataBaseConnection->commit();
                var_dump($bankAccount->getId());
            }

        } catch (\Exception $exception) {
            $dataBaseConnection->rollBack();
            throw $exception;
        }
    }
}
