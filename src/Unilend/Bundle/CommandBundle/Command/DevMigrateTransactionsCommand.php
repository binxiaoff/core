<?php


namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Service\LoanManager;

class DevMigrateTransactionsCommand extends ContainerAwareCommand
{
    /** @var  Connection */
    private $dataBaseConnection;

    protected function configure()
    {
        $this
            ->setName('dev:migrate:transactions')
            ->setDescription('Migrate transactions into operations')
            ->addArgument('limit', InputArgument::REQUIRED, 'limit');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $limit  = $input->getArgument('limit');

        /** @var Connection $dataBaseConnection */
        $this->dataBaseConnection = $this->getContainer()->get('database_connection');
        /** @var \transactions $transactions */
        $transactions          = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('transactions');
        $transactionsToMigrate = $transactions->select('status = 1 AND NOT EXISTS(SELECT id_transaction FROM transaction_treated WHERE transaction_treated.id_transaction = transactions.id_transaction)', 'date_transaction ASC, id_transaction ASC', null, $limit);
        $transactionCount      = 0;

        $this->dataBaseConnection->beginTransaction();
        try {
            foreach ($transactionsToMigrate as $transaction) {

                //var_dump($transaction['id_transaction']);
                switch($transaction['type_transaction']) {
                    case \transactions_types::TYPE_LENDER_SUBSCRIPTION:
                    case \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT:
                    case \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT:
                        $this->migrateLenderProvision($transaction);
                        break;
                    case \transactions_types::TYPE_LENDER_LOAN:
                        if (0 > $transaction['montant']) {
                            $this->migrateBid($transaction);
                            break;
                        } else {
                            $this->migrateRefusedBid($transaction);
                            break;
                        }
                    case \transactions_types::TYPE_WELCOME_OFFER:
                        $this->migrateWelcomeOffer($transaction);
                        break;
                    case \transactions_types::TYPE_WELCOME_OFFER_CANCELLATION:
                        $this->migrateWelcomeOfferCancellation($transaction);
                        break;
                    case \transactions_types::TYPE_LENDER_WITHDRAWAL:
                        $this->migrateLenderWithdrawal($transaction);
                        break;
                    case \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL:
                    case \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT:
                    case \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT:
                        $this->migrateCapitalRepayments($transaction);
                        break;
                    case \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS:
                        $this->migrateInterestRepayment($transaction);
                        break;
                    case \transactions_types::TYPE_BORROWER_REPAYMENT:
                    case \transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT:
                    case \transactions_types::TYPE_REGULATION_BANK_TRANSFER:
                    case \transactions_types::TYPE_RECOVERY_BANK_TRANSFER:
                        $this->migrateBorrowerRepayment($transaction);
                        break;
                    case \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT:
                        $this->createLoans($transaction);
                        $this->migrateUnilendProjectCommission($transaction);
                        $this->migrateFundsToBorrower($transaction);
                        break;
                    case \transactions_types::TYPE_FISCAL_BANK_TRANSFER:
                        $this->migrateTaxWithdrawal($transaction);
                        break;
                    case \transactions_types::TYPE_UNILEND_BANK_TRANSFER:
                        $this->migrateUnilendWithdrawal($transaction);
                        break;
                    case \transactions_types::TYPE_LENDER_BALANCE_TRANSFER:
                        $this->migrateBalanceTransfer($transaction); //TODO
                        break;
                    case \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION:
                        $this->migrateBorrowerProvisionCancel($transaction);
                        break;
                    case \transactions_types::TYPE_UNILEND_REPAYMENT:
                    case \transactions_types::TYPE_REGULATION_COMMISSION:
                    case \transactions_types::TYPE_LENDER_REGULATION:
                    case \transactions_types::TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER:
                        'no migration necessary';
                        break;
                    default:
                        throw new \InvalidArgumentException('Unsupported transaction type : ' . $transaction['type_transaction']);
                        break;
                }

                $this->dataBaseConnection->executeQuery('INSERT INTO transaction_treated (id_transaction) VALUE (' . $transaction['id_transaction'] . ')');
                $transactionCount += 1;
            }

            $this->dataBaseConnection->commit();
            $output->writeln('Number of transactions migrated : ' . $transactionCount);
        } catch (\Exception $exception){
            $this->dataBaseConnection->rollBack();
            throw $exception;
        }
    }

    private function newOperation(array $operation)
    {
        $query = 'INSERT INTO operation (id_type, id_wallet_debtor, id_wallet_creditor, amount, id_project, id_loan, id_payment_schedule, id_repayment_schedule, id_backpayline, id_welcome_offer, id_wire_transfer_out, id_wire_transfer_in, id_transfer, added) VALUES (:idType, :idWalletDebtor, :idWalletCreditor, :amount, :idProject, :idLoan, :idPaymentSchedule, :idRepaymentSchedule, :idBackPayline, :idWelcomeOffer, :idWireTransferOut, :idWireTransferIn, :idTransfer, :added)';

        $this->dataBaseConnection->executeQuery($query, [
            'idType'              => $operation['id_type'],
            'idWalletDebtor'      => isset($operation['id_wallet_debtor']) ? $operation['id_wallet_debtor'] : null,
            'idWalletCreditor'    => isset($operation['id_wallet_creditor']) ? $operation['id_wallet_creditor'] : null,
            'amount'              => $operation['amount'],
            'idProject'           => isset($operation['id_project']) ? $operation['id_project'] : null,
            'idLoan'              => isset($operation['id_loan']) ? $operation['id_loan'] : null,
            'idPaymentSchedule'   => isset($operation['id_payment_schedule']) ? $operation['id_payment_schedule'] : null,
            'idRepaymentSchedule' => isset($operation['id_repayment_schedule']) ? $operation['id_repayment_schedule'] : null,
            'idBackPayline'       => isset($operation['id_backpayline']) ? $operation['id_backpayline'] : null,
            'idWelcomeOffer'      => isset($operation['id_welcome_offer']) ? $operation['id_welcome_offer'] : null,
            'idWireTransferOut'   => isset($operation['id_wire_transfer_out']) ? $operation['id_wire_transfer_out'] : null,
            'idWireTransferIn'    => isset($operation['id_wire_transfer_in']) ? $operation['id_wire_transfer_in'] : null,
            'idTransfer'          => isset($operation['id_transfer']) ? $operation['id_transfer'] : null,
            'added'               => $operation['added'],
        ]);
        return $this->dataBaseConnection->lastInsertId();
    }

    private function debitAvailableBalance(array &$wallet, array $operation)
    {
        $balance                     = bcsub($wallet['available_balance'], $operation['amount'], 2);
        $wallet['available_balance'] = $balance;
        $this->updateWalletBalance($wallet, $operation);
    }

    private function debitCommittedBalance(array &$wallet, array $operation)
    {
        $balance                     = bcsub($wallet['committed_balance'], $operation['amount'], 2);
        $wallet['committed_balance'] = $balance;
        $this->updateWalletBalance($wallet, $operation);
    }

    private function creditCommittedBalance(array &$wallet, array $operation)
    {
        $balance                     = bcadd($wallet['committed_balance'], $operation['amount'], 2);
        $wallet['committed_balance'] = $balance;
        $this->updateWalletBalance($wallet, $operation);
    }

    private function creditAvailableBalance(array &$wallet, array $operation)
    {
        $balance                     = bcadd($wallet['available_balance'], $operation['amount'], 2);
        $wallet['available_balance'] = $balance;
        $this->updateWalletBalance($wallet, $operation);
    }

    private function updateWalletBalance(array $wallet, array $operation)
    {
        $query = 'UPDATE wallet SET available_balance = :availableBalance, committed_balance = :committedBalance, updated = :added WHERE id = :walletId';
        $this->dataBaseConnection->executeQuery($query, [
            'availableBalance' => $wallet['available_balance'],
            'committedBalance' => $wallet['committed_balance'],
            'added'            => $operation['added'],
            'walletId'         => $wallet['id']
        ]);
    }

    private function saveWalletBalanceHistory(
        array &$wallet,
        array $operation = null,
        array $bid = null,
        array $loan = null
    ) {
        $query = 'INSERT INTO wallet_balance_history (id_wallet, available_balance, committed_balance, id_operation, id_bid, id_loan, added) 
                          VALUES (:walletId, :availableBalance, :committedBalance, :operationId, :bidId, :loanId, :added)';
        $this->dataBaseConnection->executeQuery($query, [
            'walletId' => $wallet['id'],
            'availableBalance' => $wallet['available_balance'],
            'committedBalance' => $wallet['committed_balance'],
            'operationId' => isset($operation['id']) ? $operation['id'] : null,
            'bidId' => (false === empty($bid['id_bid'])) ? $bid['id_bid'] : null,
            'loanId' => (false === empty($loan['id_loan'])) ? $loan['id_loan'] : null,
            'added' => isset($operation['added']) ? $operation['added'] : (isset($bid['added']) ? $bid['added'] : $loan['added'])
        ]);
    }

    /**
     * @param array $transaction
     */
    private function migrateLenderProvision(array $transaction)
    {
        $lenderWallet = $this->getWallet($transaction['id_client']);

        if (false === $lenderWallet) {
            return;
        }

        if (false === empty($transaction['id_backpayline'])) {
            $this->migratePayline($transaction, $lenderWallet);
        }

        $operation['id_type']             = 1;
        $operation['id_wallet_creditor']  = $lenderWallet['id'];
        $operation['amount']              = round(bcdiv($transaction['montant'], 100, 4), 2);
        $operation['id_backpayline']      = (false === empty($transaction['id_backpayline']) ? $transaction['id_backpayline'] : null);
        $operation['id_wire_transfer_in'] = (false === empty($transaction['id_virement']) ? $transaction['id_virement'] : null);
        $operation['added']               = $transaction['date_transaction'];
        $operation['id']                  = $this->newOperation($operation);

        $this->creditAvailableBalance($lenderWallet, $operation);
        $this->saveWalletBalanceHistory($lenderWallet, $operation);
    }

    private function migratePayline(array $transaction, array $wallet)
    {
        /** @var \backpayline $backPayline */
        $backPayline = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('backpayline');
        if ($backPayline->get($transaction['id_backpayline'])) {
            $backPayline->id_wallet = $wallet['id'];
            $backPayline->serialize_do_payment = $transaction['serialize_payline'];
            $backPayline->update();
        }
    }

    private function migrateRefusedLoan($clientId, $loanId)
    {
        /** @var \loans $loanEntity */
        $loanEntity = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('loans');

        if ($loanEntity->get($loanId)){
            $borrowerWallet = $this->getBorrowerWallet($loanEntity->id_project);
            $lenderWallet   = $this->getWallet($clientId);

            $operation['id_type']            = 5;
            $operation['id_wallet_creditor'] = $lenderWallet['id'];
            $operation['id_wallet_debtor']   = $borrowerWallet['id'];
            $operation['id_loan']            = $loanEntity->id_loan;
            $operation['id_project']         = $loanEntity->id_project;
            $operation['amount']             = abs(round(bcdiv($loanEntity->amount, 100, 4), 2));
            $operation['added']              = $loanEntity->updated;
            $operation['id']                 = $this->newOperation($operation);

            $this->debitCommittedBalance($borrowerWallet, $operation);
            $this->saveWalletBalanceHistory($borrowerWallet, $operation);

            $this->creditAvailableBalance($lenderWallet, $operation);
            $this->saveWalletBalanceHistory($lenderWallet, $operation);
        }
    }

    private function migrateBid(array $transaction)
    {
        /** @var \wallets_lines $walletLines */
        $walletLines = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('wallets_lines');
        /** @var \bids $bidEntity */
        $bidEntity = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('bids');

        if (
            $walletLines->get($transaction['id_transaction'], 'id_transaction')
            && $bidEntity->get($walletLines->id_wallet_line, 'id_lender_wallet_line')
        ) {
            $lenderWallet = $this->getWallet($transaction['id_client']);

            $bid['id_bid'] = $bidEntity->id_bid;
            $bid['added']  = $bidEntity->added;
            $amount        = abs(round(bcdiv($transaction['montant'], 100, 4), 2));

            $availableBalance = bcsub($lenderWallet['available_balance'], $amount, 2);
            $committedBalance = bcadd($lenderWallet['committed_balance'], $amount, 2);

            $lenderWallet['available_balance'] = $availableBalance;
            $lenderWallet['committed_balance'] = $committedBalance;

            $this->updateWalletBalance($lenderWallet, $bid);
            $this->saveWalletBalanceHistory($lenderWallet, null, $bid);
        } else {
            $this->getContainer()->get('logger')->error('Bid could not be found for transaction : ' . $transaction['id_transaction']);
        }
    }

    private function createLoan($clientId, array $loan, array $transaction)
    {
        if (false === empty($loan['id_transfer'])) {
            /** @var LoanManager $loanManager */
            $loanManager = $this->getContainer()->get('unilend.service.loan_manager');
            $loanEntity = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('loans');
            $loanEntity->get($loan['id_loan']);
            /** @var \lenders_accounts $lenderAccount */
            $lenderAccount = $loanManager->getFirstOwner($loanEntity);
            $lenderWallet = $this->getWallet($lenderAccount->id_client_owner);
        } else {
            $lenderWallet   = $this->getWallet($clientId);
        }

        $borrowerWallet = $this->getBorrowerWallet($loan['id_project']);

        $operation['id_type']            = 4;
        $operation['id_wallet_creditor'] = $borrowerWallet['id'];
        $operation['id_wallet_debtor']   = $lenderWallet['id'];
        $operation['id_loan']            = $loan['id_loan'];
        $operation['id_project']         = $loan['id_project'];
        $operation['amount']             = abs(round(bcdiv($loan['amount'], 100, 4), 2));
        $operation['added']              = $transaction['added'];
        $operation['id']                 = $this->newOperation($operation);

        $this->debitCommittedBalance($lenderWallet, $operation);
        $this->saveWalletBalanceHistory($lenderWallet, $operation);

        $this->creditAvailableBalance($borrowerWallet, $operation);
        $this->saveWalletBalanceHistory($borrowerWallet, $operation);
    }

    private function getBorrowerWallet($projectId)
    {
        $query     = 'SELECT * FROM wallet w
                        INNER JOIN companies co ON w.id_client = co.id_client_owner
                        INNER JOIN projects p ON p.id_company = co.id_company
                      WHERE p.id_project =  :projectId AND w.id_type = 2';
        $statement = $this->dataBaseConnection->executeQuery($query, ['projectId' => $projectId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC)[0];
    }

    private function getWallet($clientId)
    {
        $query     = 'SELECT * FROM wallet where id_client = :clientId';
        $statement = $this->dataBaseConnection->executeQuery($query, ['clientId' => $clientId]);

        $wallet = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($wallet)){
            $this->getContainer()->get('logger')->error('Could not find wallet for client ' . $clientId);
            return false;
        }

        return $wallet[0];
    }

    private function getUnilendWallet()
    {
        $query     = 'SELECT * FROM wallet where id_type = 3';
        $statement = $this->dataBaseConnection->executeQuery($query);

        return $statement->fetchAll(\PDO::FETCH_ASSOC)[0];
    }

    private function migrateWelcomeOffer(array $transaction)
    {
        $clientWallet  = $this->getWallet($transaction['id_client']);

        if (false === $clientWallet) {
            return;
        }

        $unilendWallet = $this->getUnilendWallet();

        $operation['id_type']            = 27;
        $operation['id_wallet_creditor'] = $clientWallet['id'];
        $operation['id_wallet_debtor']   = $unilendWallet['id'];
        $operation['amount']             = round(bcdiv($transaction['montant'], 100, 4), 2);
        $operation['id_welcome_offer']   = $transaction['id_offre_bienvenue_detail'];
        $operation['added']              = $transaction['date_transaction'];
        $operation['id']                 = $this->newOperation($operation);

        $this->creditAvailableBalance($clientWallet, $operation);
        $this->saveWalletBalanceHistory($clientWallet, $operation);

        $this->debitAvailableBalance($unilendWallet, $operation);
        $this->saveWalletBalanceHistory($unilendWallet, $operation);
    }

    private function migrateWelcomeOfferCancellation(array $transaction)
    {
        $clientWallet  = $this->getWallet($transaction['id_client']);
        if (false === $clientWallet) {
            return;
        }

        $unilendWallet = $this->getUnilendWallet();

        $operation['id_type']            = 28;
        $operation['id_wallet_debtor']   = $clientWallet['id'];
        $operation['id_wallet_creditor'] = $unilendWallet['id'];
        $operation['amount']             = abs(round(bcdiv($transaction['montant'], 100, 4), 2));
        $operation['id_welcome_offer']   = $transaction['id_offre_bienvenue_detail'];
        $operation['added']              = $transaction['date_transaction'];
        $operation['id']                 = $this->newOperation($operation);

        $this->creditAvailableBalance($unilendWallet, $operation);
        $this->saveWalletBalanceHistory($unilendWallet, $operation);
        $this->debitAvailableBalance($clientWallet, $operation);
        $this->saveWalletBalanceHistory($clientWallet, $operation);
    }

    private function migrateLenderWithdrawal(array $transaction)
    {
        $wallet = $this->getWallet($transaction['id_client']);
        if (false === $wallet) {
            return;
        }

        /** @var \virements $wireTransferOut */
        $wireTransferOut = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('virements');
        $wireTransferOut->get($transaction['id_transaction'], 'id_transaction');

        $operation['id_type']              = 3;
        $operation['id_wallet_debtor']     = $wallet['id'];
        $operation['amount']               = abs(round(bcdiv($transaction['montant'], 100, 4), 2));
        $operation['id_wire_transfer_out'] = false === empty($wireTransferOut->id_virement) ? $wireTransferOut->id_virement :  null;
        $operation['added']                = $transaction['date_transaction'];
        $operation['id']                   = $this->newOperation($operation);

        $this->debitAvailableBalance($wallet, $operation);
        $this->saveWalletBalanceHistory($wallet, $operation);
    }

    private function migrateRefusedBid(array $transaction)
    {
        $lenderWallet = $this->getWallet($transaction['id_client']);

        if (false === $lenderWallet) {
            return;
        }

        $amount                            = abs(round(bcdiv($transaction['montant'], 100, 4), 2));
        $availableBalance                  = bcadd($lenderWallet['available_balance'], $amount, 2);
        $committedBalance                  = bcsub($lenderWallet['committed_balance'], $amount, 2);
        $lenderWallet['available_balance'] = $availableBalance;
        $lenderWallet['committed_balance'] = $committedBalance;

        /** @var \loans $loans */
        $loans = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('loans');
        $loan  = [];

        if ($loans->get($transaction['id_loan_remb'])) {
            $loan['id']    = $loans->id_loan;
            $loan['added'] = $transaction['added'];
        }

        /** @var \bids $bids */
        $bids  = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('bids');
        $bid   = [];

        if (false === $bids->get($transaction['id_bid_remb'])) {
            /** @var \wallets_lines $walletLines */
            $walletLines = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('wallets_lines');
            $walletLines->get($transaction['id_transaction'], 'id_transaction');
            $bids->get($walletLines->id_bid_remb);
        }

        $bid['id']    = (false === empty($bids->id_bid)) ? $bids->id_bid : null;
        $bid['added'] = $transaction['added'];

        $this->updateWalletBalance($lenderWallet, $transaction);
        $this->saveWalletBalanceHistory($lenderWallet, null, $bid, $loan);
    }

    private function migrateCapitalRepayments(array $transaction)
    {
        $lenderWallet = $this->getWallet($transaction['id_client']);

        if (false === $lenderWallet) {
            return;
        }

        if (empty($transaction['id_project']) && false === empty($transaction['id_echeancier'])) {
            /** @var \echeanciers $repaymentSchedule */
            $repaymentSchedule = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('echeanciers');
            $repaymentSchedule->get($transaction['id_echeancier']);
            $idProject = $repaymentSchedule->id_project;
        } else {
            $idProject = $transaction['id_project'];
        }

        $borrowerWallet = $this->getBorrowerWallet($idProject);

        $operation['id_type']               = 11;
        $operation['id_wallet_creditor']    = $lenderWallet['id'];
        $operation['id_wallet_debtor']      = $borrowerWallet['id'];
        $operation['id_repayment_schedule'] = (false === empty($transaction['id_echeancier'])) ? $transaction['id_echeancier'] : null;
        $operation['id_project']            = $idProject;
        $operation['amount']                = round(bcdiv($transaction['montant'], 100, 4), 2);
        $operation['added']                 = $transaction['date_transaction'];
        $operation['id']                    = $this->newOperation($operation);

        $this->creditAvailableBalance($lenderWallet, $operation);
        $this->saveWalletBalanceHistory($lenderWallet, $operation);

        $this->debitAvailableBalance($borrowerWallet, $operation);
        $this->saveWalletBalanceHistory($borrowerWallet, $operation);
    }

    private function migrateBorrowerRepayment(array $transaction)
    {
        if (false === empty($transaction['id_project'])) {
            $idProject = $transaction['id_project'];
        } else if(false === empty($transaction['id_prelevement'])){
            /** @var \receptions $directDebit */
            $directDebit = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('receptions');
            $directDebit->get($transaction['id_prelevement']);
            $idProject = $directDebit->id_project;
        }

        $borrowerWallet = $this->getBorrowerWallet($idProject);

        $operation['id_type']             = 6;
        $operation['id_wallet_creditor']  = $borrowerWallet['id'];
        $operation['id_project']          = $idProject;
        $operation['id_wire_transfer_in'] = (false === empty($transaction['id_virement'])) ? $transaction['id_virement'] : null;
        $operation['amount']              = round(bcdiv($transaction['montant'], 100, 4), 2);
        $operation['added']               = $transaction['date_transaction'];
        $operation['id']                  = $this->newOperation($operation);

        $this->creditAvailableBalance($borrowerWallet, $operation);
        $this->saveWalletBalanceHistory($borrowerWallet, $operation);
    }

    private function migrateFundsToBorrower(array $transaction)
    {
        /** @var \virements $wireTransferOut */
        $wireTransferOut = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('virements');
        $wireTransferOut->get($transaction['id_transaction'], 'id_transaction');

        $borrowerWallet = $this->getWallet($transaction['id_client']);

        if (false === $borrowerWallet) {
            return;
        }

        $operation['id_type']              = 10;
        $operation['amount']               = abs(round(bcdiv($transaction['montant'], 100, 4), 2));
        $operation['id_project']           = $transaction['id_project'];
        $operation['added']                = $transaction['added'];
        $operation['id_wire_transfer_out'] = $wireTransferOut->id_virement;
        $operation['id_wallet_debtor']     = $borrowerWallet['id'];
        $operation['id']                   = $this->newOperation($operation);

        $this->debitAvailableBalance($borrowerWallet, $operation);
        $this->saveWalletBalanceHistory($borrowerWallet, $operation);
    }

    private function createLoans(array $transaction)
    {
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('lenders_accounts');

        $query = 'SELECT * FROM loans
                WHERE id_loan NOT IN (SELECT id_loan FROM operation WHERE id_type = 4) AND loans.id_project = :idProject';

        $statement = $this->dataBaseConnection->executeQuery($query, ['idProject' => $transaction['id_project']]);
        while ($loan = $statement->fetch(\PDO::FETCH_ASSOC)){
            $lenderAccount->get($loan['id_lender']);
            $this->createLoan($lenderAccount->id_client_owner, $loan, $transaction);
        }
    }

    private function migrateUnilendProjectCommission(array $transaction)
    {
        $unilendWallet = $this->getUnilendWallet();
        $borrowerWallet = $this->getWallet($transaction['id_client']);

        if (false === $borrowerWallet) {
            return;
        }

        $operation['id_type']            = 8;
        $operation['amount']             = round(bcdiv($transaction['montant_unilend'], 100, 4), 2);
        $operation['id_project']         = $transaction['id_project'];
        $operation['id_wallet_creditor'] = $unilendWallet['id'];
        $operation['id_wallet_debtor']   = $borrowerWallet['id'];
        $operation['added']              = $transaction['date_transaction'];
        $operation['id']                 = $this->newOperation($operation);

        $this->debitAvailableBalance($borrowerWallet, $operation);
        $this->saveWalletBalanceHistory($borrowerWallet, $operation);

        $this->creditAvailableBalance($unilendWallet, $operation);
        $this->saveWalletBalanceHistory($unilendWallet, $operation);
    }

    private function migrateInterestRepayment(array $transaction)
    {
        /** @var \tax $tax */
        $tax       = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('tax');
        $paidTaxes = $tax->select('id_transaction = ' . $transaction['id_transaction']);
        $totalTax  = array_sum(array_column($paidTaxes, 'amount'));

        $lenderWallet = $this->getWallet($transaction['id_client']);
        if (false === $lenderWallet) {
            return;
        }

        if (empty($transaction['id_project']) && false === empty($transaction['id_echeancier'])) {
            /** @var \echeanciers $repaymentSchedule */
            $repaymentSchedule = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('echeanciers');
            $repaymentSchedule->get($transaction['id_echeancier']);
            $idProject = $repaymentSchedule->id_project;
        } else {
            $idProject = $transaction['id_project'];
        }

        $borrowerWallet = $this->getBorrowerWallet($idProject);

        $operation['id_type']               = 12;
        $operation['id_wallet_creditor']    = $lenderWallet['id'];
        $operation['id_wallet_debtor']      = $borrowerWallet['id'];
        $operation['id_repayment_schedule'] = (false === empty($transaction['id_echeancier'])) ? $transaction['id_echeancier'] : null;
        $operation['id_project']            = $idProject;
        $operation['amount']                = round(bcdiv(bcadd($transaction['montant'], $totalTax), 100, 4), 2);
        $operation['added']                 = $transaction['date_transaction'];
        $operation['id']                    = $this->newOperation($operation);

        $this->debitAvailableBalance($borrowerWallet, $operation);
        $this->saveWalletBalanceHistory($borrowerWallet, $operation);

        $this->creditAvailableBalance($lenderWallet, $operation);
        $this->saveWalletBalanceHistory($lenderWallet, $operation);

        foreach ($paidTaxes as $tax) {
            if (0 < $tax['amount']) {
                switch ($tax['id_tax_type']) {
                    case \tax_type::TYPE_INCOME_TAX:
                        $operation['id_type'] = 21;
                        $walletLabel          = 'tax_prelevements_obligatoires';
                        break;
                    case \tax_type::TYPE_CSG:
                        $operation['id_type'] = 17;
                        $walletLabel          = 'tax_csg';
                        break;
                    case \tax_type::TYPE_SOCIAL_DEDUCTIONS:
                        $operation['id_type'] = 23;
                        $walletLabel          = 'tax_prelevements_sociaux';
                        break;
                    case \tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS:
                        $operation['id_type'] = 13;
                        $walletLabel          = 'tax_contributions_additionnelles';
                        break;
                    case \tax_type::TYPE_SOLIDARITY_DEDUCTIONS:
                        $operation['id_type'] = 19;
                        $walletLabel          = 'tax_prelevements_de_solidarite';
                        break;
                    case \tax_type::TYPE_CRDS:
                        $operation['id_type'] = 15;
                        $walletLabel          = 'tax_crds';
                        break;
                    case \tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE:
                        $operation['id_type'] = 25;
                        $walletLabel          = 'tax_retenues_a_la_source';
                        break;
                    default :
                        $this->getContainer()->get('logger')->error('Unknown tax_type for transaction : ' . $transaction['id_transaction']);
                        return;
                }

                $taxWallet = $this->getTaxWallet($walletLabel);

                $operation['amount']                = abs(round(bcdiv($tax['amount'], 100, 4), 2));
                $operation['id_repayment_schedule'] = $transaction['id_echeancier'];
                $operation['id_wallet_creditor']    = $taxWallet['id'];
                $operation['id_wallet_debtor']      = $lenderWallet['id'];
                $operation['added']                 = $transaction['date_transaction'];
                $operation['id']                    = $this->newOperation($operation);

                $this->creditAvailableBalance($taxWallet, $operation);
                $this->saveWalletBalanceHistory($taxWallet, $operation);

                $this->debitAvailableBalance($lenderWallet, $operation);
                $this->saveWalletBalanceHistory($lenderWallet, $operation);
            }
        }
    }

    private function getTaxWallet($label)
    {
        $query = 'SELECT wallet.*
                    FROM wallet 
                  INNER JOIN wallet_type ON wallet.id_type = wallet_type.id
                    WHERE wallet_type.label = :walletLabel';

        $statement = $this->dataBaseConnection->executeQuery($query, ['walletLabel' => $label]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC)[0];
    }

    private function migrateTaxWithdrawal(array $transaction)
    {
        $totalTaxAmount = abs(round(bcdiv($transaction['montant'], 100, 4), 2));

        $taxWallet                     = $this->getTaxWallet('tax_prelevements_obligatoires');
        $operation['id_type']          = 22;
        $operation['amount']           = $taxWallet['available_balance'];
        $operation['id_wallet_debtor'] = $taxWallet['id'];
        $operation['added']            = $transaction['date_transaction'];
        $operation['id']               = $this->newOperation($operation);

        $totalTaxAmount -= $operation['amount'];

        $this->debitAvailableBalance($taxWallet, $operation);
        $this->saveWalletBalanceHistory($taxWallet, $operation);

        unset($taxWallet, $operation);

        $taxWallet                     = $this->getTaxWallet('tax_csg');
        $operation['id_type']          = 18;
        $operation['amount']           = $taxWallet['available_balance'];
        $operation['id_wallet_debtor'] = $taxWallet['id'];
        $operation['added']            = $transaction['date_transaction'];
        $operation['id']               = $this->newOperation($operation);

        $totalTaxAmount -= $operation['amount'];

        $this->debitAvailableBalance($taxWallet, $operation);
        $this->saveWalletBalanceHistory($taxWallet, $operation);
        unset($taxWallet, $operation);

        $taxWallet                     = $this->getTaxWallet('tax_prelevements_sociaux');
        $operation['id_type']          = 24;
        $operation['amount']           = $taxWallet['available_balance'];
        $operation['id_wallet_debtor'] = $taxWallet['id'];
        $operation['added']            = $transaction['date_transaction'];
        $operation['id']               = $this->newOperation($operation);

        $totalTaxAmount -= $operation['amount'];

        $this->debitAvailableBalance($taxWallet, $operation);
        $this->saveWalletBalanceHistory($taxWallet, $operation);
        unset($taxWallet, $operation);

        $taxWallet                     = $this->getTaxWallet('tax_contributions_additionnelles');
        $operation['id_type']          = 14;
        $operation['amount']           = $taxWallet['available_balance'];
        $operation['id_wallet_debtor'] = $taxWallet['id'];
        $operation['added']            = $transaction['date_transaction'];
        $operation['id']               = $this->newOperation($operation);

        $totalTaxAmount -= $operation['amount'];

        $this->debitAvailableBalance($taxWallet, $operation);
        $this->saveWalletBalanceHistory($taxWallet, $operation);
        unset($taxWallet, $operation);

        $taxWallet                     = $this->getTaxWallet('tax_prelevements_de_solidarite');
        $operation['id_type']          = 20;
        $operation['amount']           = $taxWallet['available_balance'];
        $operation['id_wallet_debtor'] = $taxWallet['id'];
        $operation['added']            = $transaction['date_transaction'];
        $operation['id']               = $this->newOperation($operation);

        $totalTaxAmount -= $operation['amount'];

        $this->debitAvailableBalance($taxWallet, $operation);
        $this->saveWalletBalanceHistory($taxWallet, $operation);
        unset($taxWallet, $operation);

        $taxWallet                     = $this->getTaxWallet('tax_crds');
        $operation['id_type']          = 16;
        $operation['amount']           = $taxWallet['available_balance'];
        $operation['id_wallet_debtor'] = $taxWallet['id'];
        $operation['added']            = $transaction['date_transaction'];
        $operation['id']               = $this->newOperation($operation);

        $totalTaxAmount -= $operation['amount'];

        $this->debitAvailableBalance($taxWallet, $operation);
        $this->saveWalletBalanceHistory($taxWallet, $operation);
        unset($taxWallet, $operation);

        $taxWallet                     = $this->getTaxWallet('tax_retenues_a_la_source');
        $operation['id_type']          = 26;
        $operation['amount']           = $taxWallet['available_balance'];
        $operation['id_wallet_debtor'] = $taxWallet['id'];
        $operation['added']            = $transaction['date_transaction'];
        $operation['id']               = $this->newOperation($operation);

        $totalTaxAmount -= $operation['amount'];

        $this->debitAvailableBalance($taxWallet, $operation);
        $this->saveWalletBalanceHistory($taxWallet, $operation);
        unset($taxWallet, $operation);

        if (0 < abs($totalTaxAmount)) {
            $this->getContainer()->get('logger')->error('Monthly tax amounts do not match for transaction : ' . $transaction['id_transaction'] . ' - difference of ' . $totalTaxAmount);
        }
    }

    private function migrateUnilendWithdrawal(array $transaction)
    {
        $wallet = $this->getUnilendWallet();

        /** @var \virements $wireTransferOut */
        $wireTransferOut = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('virements');
        $wireTransferOut->get($transaction['id_transaction'], 'id_transaction');

        $operation['id_type']              = 30;
        $operation['id_wallet_debtor']     = $wallet['id'];
        $operation['amount']               = abs(round(bcdiv($transaction['montant'], 100, 4), 2));
        $operation['id_wire_transfer_out'] = false === empty($wireTransferOut->id_virement) ? $wireTransferOut->id_virement :  null;
        $operation['added']                = $transaction['date_transaction'];
        $operation['id']                   = $this->newOperation($operation);

        $this->debitAvailableBalance($wallet, $operation);
        $this->saveWalletBalanceHistory($wallet, $operation);
    }

    private function migrateBalanceTransfer(array $transaction)
    {

    }


    private function checkAndCreateLoan(array $transaction)
    {
        $query = 'SELECT l.* FROM loans l
                  INNER JOIN lenders_accounts la ON l.id_lender = la.id_lender_account
                  WHERE l.updated <= :transactionDate AND l.status = 0 AND id_loan NOT IN (SELECT id_loan FROM operation WHERE id_type = 4) AND la.id_client_owner = :idClient';

        $loans = $this->dataBaseConnection->executeQuery($query, [
            'transactionDate' => $transaction['date_transaction'],
            'idClient'        => $transaction['id_client']
        ])->fetchAll(\PDO::FETCH_ASSOC);


        foreach ($loans as $loan) {
            $this->createLoan($transaction['id_client'], $loan);
        }
    }

    private function migrateBorrowerProvisionCancel(array $transaction)
    {
        /** @var \receptions $directDebit */
        $directDebit = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('receptions');
        $directDebit->get($transaction['id_prelevement']);

        $borrowerWallet = $this->getWallet($transaction['id_client']);
        if (false === $borrowerWallet) {
            return;
        }

        $operation['id_type']              = 7;
        $operation['amount']               = abs(round(bcdiv($transaction['montant'], 100, 4), 2));
        $operation['id_wallet_debtor']     = $borrowerWallet['id'];
        $operation['added']                = $transaction['added'];
        $operation['id_project']           = false === empty($directDebit->id_project) ? $directDebit->id_reception : null;
        $operation['id_wire_transfer_in']  = $directDebit->id_reception;
        $operation['id']                   = $this->newOperation($operation);

        $this->debitAvailableBalance($borrowerWallet, $operation);
        $this->saveWalletBalanceHistory($borrowerWallet, $operation);
    }
}
