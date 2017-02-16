<?php


namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Service\LoanManager;
use Unilend\librairies\CacheKeys;

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

                if ($transaction['id_transaction'] == 1460178) {
                    $this->dataBaseConnection->executeQuery('INSERT INTO transaction_treated (id_transaction) VALUE (' . $transaction['id_transaction'] . ')');
                    continue;
                }

                if ($transaction['id_transaction'] == 2771937){
                    $transaction['montant'] = '88880';
                }

                if ($transaction['id_transaction'] == 16952103) {
                    $amount =  $this->calculateOperationAmount($transaction['montant']);
                    $this->lenderRegulation($transaction['id_client'], $amount, $transaction['date_transaction']);

                    $lenderWallet = $this->getClientWallet($transaction['id_client']);

                    /** @var \bids $bidEntity */
                    $bidEntity = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('bids');
                    $bid       = $bidEntity->select('id_lender_wallet_line = 8277690')[0];
                    $amount    = $this->calculateOperationAmount($transaction['montant']);

                    $availableBalance = bcsub($lenderWallet['available_balance'], $amount, 2);
                    $committedBalance = bcadd($lenderWallet['committed_balance'], $amount, 2);

                    $lenderWallet['available_balance'] = $availableBalance;
                    $lenderWallet['committed_balance'] = $committedBalance;

                    $this->updateWalletBalance($lenderWallet, $bid);
                    $this->saveWalletBalanceHistory($lenderWallet, null, $bid);

                    $this->migrateBid($transaction);
                    $this->dataBaseConnection->executeQuery('INSERT INTO transaction_treated (id_transaction) VALUE (' . $transaction['id_transaction'] . ')');
                    continue;
                }

                if ($transaction['id_transaction'] == 364887) {
                    $amount =  $this->calculateOperationAmount($transaction['montant']);
                    $this->lenderRegulation($transaction['id_client'], $amount, $transaction['date_transaction']);
                    $this->dataBaseConnection->executeQuery('INSERT INTO transaction_treated (id_transaction) VALUE (' . $transaction['id_transaction'] . ')');
                    continue;
                }

                if (in_array($transaction['id_transaction'], [1667967, 1667964])){
                    $amount =  $this->calculateOperationAmount($transaction['montant']);
                    $this->lenderRegulation($transaction['id_client'], $amount, $transaction['date_transaction']);
                    $this->dataBaseConnection->executeQuery('INSERT INTO transaction_treated (id_transaction) VALUE (' . $transaction['id_transaction'] . ')');
                    continue;
                }

                if ($transaction['id_transaction'] == 2264291) {
                    $this->lenderRegulation($transaction['id_client'], '39.63', $transaction['date_transaction']);
                }

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
                    case \transactions_types::TYPE_LENDER_REGULATION:
                        $this->migrateLenderWithdrawal($transaction);
                        break;
                    case \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL:
                    case \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT:
                        $this->migrateCapitalRepayments($transaction);
                        break;
                    case \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT:
                        $this->migrateRecoveryToLender($transaction);
                        break;
                    case \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS:
                        $this->migrateInterestRepayment($transaction);
                        break;
                    case \transactions_types::TYPE_BORROWER_REPAYMENT:
                    case \transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT:
                    case \transactions_types::TYPE_REGULATION_BANK_TRANSFER:
                        $this->migrateBorrowerRepayment($transaction);
                        break;
                    case \transactions_types::TYPE_RECOVERY_BANK_TRANSFER:
                        $this->migrateBorrowerRepayment($transaction);
                        $this->migrateRecoveryCommissionToBorrower($transaction);
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
                        $this->migrateBalanceTransfer($transaction);
                        break;
                    case \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION:
                        $this->migrateBorrowerProvisionCancel($transaction);
                        break;
                    case \transactions_types::TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER:
                        $this->migrateWelcomeOfferProvision($transaction);
                        break;
                    case \transactions_types::TYPE_UNILEND_REPAYMENT:
                        $this->migrateUnilendRepaymentCommission($transaction);
                        break;
                    case \transactions_types::TYPE_REGULATION_COMMISSION:
                        $this->insertIntoNonTreatedTransactions($transaction, 'transaction type not migrated', 1);
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

    private function creditAvailableBalance(array &$wallet, array $operation)
    {
        $balance                     = bcadd($wallet['available_balance'], $operation['amount'], 2);
        $wallet['available_balance'] = $balance;
        $this->updateWalletBalance($wallet, $operation);
    }

    private function updateWalletBalance(array $wallet, array $operation)
    {
        $query = 'UPDATE wallet SET available_balance = :availableBalance, committed_balance = :committedBalance, updated = :updated WHERE id = :walletId';
        $this->dataBaseConnection->executeQuery($query, [
            'availableBalance' => $wallet['available_balance'],
            'committedBalance' => $wallet['committed_balance'],
            'updated'          => $operation['added'],
            'walletId'         => $wallet['id']
        ]);
    }

    private function saveWalletBalanceHistory(array &$wallet, array $operation = null, array $bid = null, array $loan = null)
    {
        $query = 'INSERT INTO wallet_balance_history (id_wallet, available_balance, committed_balance, id_operation, id_bid, id_loan, id_autobid, id_project, added) 
                          VALUES (:walletId, :availableBalance, :committedBalance, :operationId, :bidId, :loanId, :autobidId, :projectId, :added)';
        $this->dataBaseConnection->executeQuery($query, [
            'walletId'         => $wallet['id'],
            'availableBalance' => $wallet['available_balance'],
            'committedBalance' => $wallet['committed_balance'],
            'operationId'      => isset($operation['id']) ? $operation['id'] : null,
            'bidId'            => empty($bid['id_bid']) ? null : $bid['id_bid'],
            'loanId'           => empty($operation['id_loan']) ? empty($loan['id_loan']) ? null : $loan['id_loan'] : $operation['id_loan'],
            'autobidId'        => empty($bid['id_autobid']) ? null : $bid['id_autobid'],
            'projectId'        => empty($operation['id_project'])? (empty($bid['id_project']) ? (empty($loan['id_project']) ? null : $loan['id_project']) : $bid['id_project']) : $operation['id_project'],
            'added'            => isset($operation['added']) ? $operation['added'] : (isset($bid['added']) ? $bid['added'] : $loan['added'])
        ]);
    }

    /**
     * @param array $transaction
     */
    private function migrateLenderProvision(array $transaction)
    {
        $lenderWallet = $this->getClientWallet($transaction['id_client']);

        if (false === $lenderWallet) {
            $this->insertIntoNonTreatedTransactions($transaction, 'lender wallet not found');
            return;
        }

        if (false === empty($transaction['id_backpayline'])) {
            $this->migratePayline($transaction, $lenderWallet);
        }

        $operation['id_type']             = $this->getOperationType('lender_provision');
        $operation['id_wallet_creditor']  = $lenderWallet['id'];
        $operation['amount']              = $this->calculateOperationAmount($transaction['montant']);
        $operation['id_backpayline']      = empty($transaction['id_backpayline']) ? null : $transaction['id_backpayline'];
        $operation['id_wire_transfer_in'] = empty($transaction['id_virement']) ? null : $transaction['id_virement'];
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

    private function migrateBid(array $transaction)
    {
        /** @var \wallets_lines $walletLines */
        $walletLines = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('wallets_lines');
        /** @var \bids $bidEntity */
        $bidEntity = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('bids');

        if (
            $walletLines->get($transaction['id_transaction'], 'id_transaction')
            && $bidEntity->exist($walletLines->id_wallet_line, 'id_lender_wallet_line')
        ) {
            $lenderWallet = $this->getClientWallet($transaction['id_client']);

            $bid               = $bidEntity->select('id_lender_wallet_line = ' . $walletLines->id_wallet_line)[0];
            $amount            = $this->calculateOperationAmount($transaction['montant']);

            $availableBalance = bcsub($lenderWallet['available_balance'], $amount, 2);
            $committedBalance = bcadd($lenderWallet['committed_balance'], $amount, 2);

            $lenderWallet['available_balance'] = $availableBalance;
            $lenderWallet['committed_balance'] = $committedBalance;

            $this->updateWalletBalance($lenderWallet, $bid);
            $this->saveWalletBalanceHistory($lenderWallet, null, $bid);
        } else {
            $this->insertIntoNonTreatedTransactions($transaction, 'Bid could not be found');
        }
    }

    private function createLoan($clientId, array $loan, array $transaction)
    {
        if (false === empty($loan['id_transfer'])) {
            /** @var LoanManager $loanManager */
            $loanManager = $this->getContainer()->get('unilend.service.loan_manager');
            $loanEntity  = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('loans');
            $loanEntity->get($loan['id_loan']);
            /** @var \lenders_accounts $lenderAccount */
            $lenderAccount = $loanManager->getFirstOwner($loanEntity);
            $lenderWallet  = $this->getClientWallet($lenderAccount->id_client_owner);
        } else {
            $lenderWallet = $this->getClientWallet($clientId);
        }

        $borrowerWallet = $this->getBorrowerWallet($loan['id_project']);

        if (false === $borrowerWallet) {
            $this->insertIntoNonTreatedTransactions($transaction, 'borrower wallet not found');
            return;
        }

        $operation['id_type']            = $this->getOperationType('lender_loan');
        $operation['id_wallet_creditor'] = $borrowerWallet['id'];
        $operation['id_wallet_debtor']   = $lenderWallet['id'];
        $operation['id_loan']            = $loan['id_loan'];
        $operation['id_project']         = $loan['id_project'];
        $operation['amount']             = $this->calculateOperationAmount($loan['amount']);
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

        $wallet = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($wallet)){
            return false;
        }

        return $wallet[0];
    }

    private function getClientWallet($clientId)
    {
        $query     = 'SELECT * FROM wallet where id_client = :clientId';
        $statement = $this->dataBaseConnection->executeQuery($query, ['clientId' => $clientId]);

        $wallet = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($wallet)){
            return false;
        }

        return $wallet[0];
    }

    private function migrateWelcomeOffer(array $transaction)
    {
        $clientWallet  = $this->getClientWallet($transaction['id_client']);

        if (false === $clientWallet) {
            $this->insertIntoNonTreatedTransactions($transaction, 'lender wallet not found');
            return;
        }

        $promotionWallet = $this->getWalletByLabel('unilend_promotional_operation');

        $operation['id_type']            = $this->getOperationType('unilend_promotional_operation');
        $operation['id_wallet_creditor'] = $clientWallet['id'];
        $operation['id_wallet_debtor']   = $promotionWallet['id'];
        $operation['amount']             = $this->calculateOperationAmount($transaction['montant']);
        $operation['id_welcome_offer']   = $transaction['id_offre_bienvenue_detail'];
        $operation['added']              = $transaction['date_transaction'];
        $operation['id']                 = $this->newOperation($operation);

        $this->debitAvailableBalance($promotionWallet, $operation);
        $this->saveWalletBalanceHistory($promotionWallet, $operation);

        $this->creditAvailableBalance($clientWallet, $operation);
        $this->saveWalletBalanceHistory($clientWallet, $operation);
    }

    private function migrateWelcomeOfferCancellation(array $transaction)
    {
        if (empty($transaction['montant'])) {
            $this->insertIntoNonTreatedTransactions($transaction, 'welcome offer cancellation with amount 0', $status = 1);
        }

        $clientWallet  = $this->getClientWallet($transaction['id_client']);
        if (false === $clientWallet) {
            $this->insertIntoNonTreatedTransactions($transaction, 'lender wallet not found');
            return;
        }

        $promotionWallet = $this->getWalletByLabel('unilend_promotional_operation');

        $operation['id_type']            = $this->getOperationType('unilend_promotional_operation_cancel');
        $operation['id_wallet_debtor']   = $clientWallet['id'];
        $operation['id_wallet_creditor'] = $promotionWallet['id'];
        $operation['amount']             = $this->calculateOperationAmount($transaction['montant']);
        $operation['id_welcome_offer']   = $transaction['id_offre_bienvenue_detail'];
        $operation['added']              = $transaction['date_transaction'];
        $operation['id']                 = $this->newOperation($operation);

        $this->debitAvailableBalance($clientWallet, $operation);
        $this->saveWalletBalanceHistory($clientWallet, $operation);

        $this->creditAvailableBalance($promotionWallet, $operation);
        $this->saveWalletBalanceHistory($promotionWallet, $operation);

    }

    private function migrateLenderWithdrawal(array $transaction)
    {
        $wallet = $this->getClientWallet($transaction['id_client']);
        if (false === $wallet) {
            $this->insertIntoNonTreatedTransactions($transaction, 'lender wallet not found');
            return;
        }

        if (\transactions_types::TYPE_LENDER_REGULATION == $transaction['type_transaction'] && $transaction['id_client'] == 330 ) {
            $wallet['committed_balance'] = bcadd($wallet['committed_balance'], 50, 2);
        }

        /** @var \virements $wireTransferOut */
        $wireTransferOut = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('virements');
        $wireTransferOut->get($transaction['id_transaction'], 'id_transaction');

        $operation['id_type']              = $this->getOperationType('lender_withdraw');
        $operation['id_wallet_debtor']     = $wallet['id'];
        $operation['amount']               = $this->calculateOperationAmount($transaction['montant']);
        $operation['id_wire_transfer_out'] = empty($wireTransferOut->id_virement) ? null : $wireTransferOut->id_virement;
        $operation['added']                = $transaction['date_transaction'];
        $operation['id']                   = $this->newOperation($operation);

        $this->debitAvailableBalance($wallet, $operation);
        $this->saveWalletBalanceHistory($wallet, $operation);
    }

    private function migrateRefusedBid(array $transaction)
    {
        $lenderWallet = $this->getClientWallet($transaction['id_client']);

        if (false === $lenderWallet) {
            $this->insertIntoNonTreatedTransactions($transaction, 'lender wallet not found');
            return;
        }

        $amount                            = $this->calculateOperationAmount($transaction['montant']);
        $availableBalance                  = bcadd($lenderWallet['available_balance'], $amount, 2);
        $committedBalance                  = bcsub($lenderWallet['committed_balance'], $amount, 2);
        $lenderWallet['available_balance'] = $availableBalance;
        $lenderWallet['committed_balance'] = $committedBalance;

        /** @var \loans $loans */
        $loans = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('loans');
        $loan  = [];

        if ($loans->get($transaction['id_loan_remb'])) {
            $loan['id_loan']    = $loans->id_loan;
            $loan['id_project'] = $loans->id_project;
            $loan['added']      = $transaction['added'];
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

        $bid['id_bid']     = empty($bids->id_bid) ? null : $bids->id_bid;
        $bid['id_project'] = empty($bids->id_project) ? empty($transaction['id_project']) ? null: $transaction['id_project'] : $bids->id_project;
        $bid['added']      = $transaction['added'];

        $this->updateWalletBalance($lenderWallet, $transaction);
        $this->saveWalletBalanceHistory($lenderWallet, null, $bid, $loan);
    }

    private function migrateCapitalRepayments(array $transaction)
    {
        $lenderWallet = $this->getClientWallet($transaction['id_client']);

        if (false === $lenderWallet) {
            $this->insertIntoNonTreatedTransactions($transaction, 'lender wallet not found');
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
        if (false === $borrowerWallet) {
            $this->insertIntoNonTreatedTransactions($transaction, 'borrower wallet not found');
            return;
        }

        $operation['id_type']               = $this->getOperationType('capital_repayment');
        $operation['id_wallet_creditor']    = $lenderWallet['id'];
        $operation['id_wallet_debtor']      = $borrowerWallet['id'];
        $operation['id_repayment_schedule'] = empty($transaction['id_echeancier']) ? null: $transaction['id_echeancier'];
        $operation['id_project']            = $idProject;
        $operation['amount']                = $this->calculateOperationAmount($transaction['montant']);
        $operation['added']                 = $transaction['date_transaction'];
        $operation['id']                    = $this->newOperation($operation);

        $this->debitAvailableBalance($borrowerWallet, $operation);
        $this->saveWalletBalanceHistory($borrowerWallet, $operation);

        $this->creditAvailableBalance($lenderWallet, $operation);
        $this->saveWalletBalanceHistory($lenderWallet, $operation);
    }

    private function migrateBorrowerRepayment(array $transaction)
    {
        if (false === empty($transaction['id_project'])) {
            $idProject = $transaction['id_project'];
        }

        /** @var \receptions $directDebit */
        $directDebit = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('receptions');

        if (false === empty($transaction['id_prelevement'])){
            $directDebit->get($transaction['id_prelevement']);
        }

        if (false === empty($transaction['id_virement'])) {
            $directDebit->get($transaction['id_virement']);
        }

        $idProject = isset($idProject) ? $idProject : $directDebit->id_project;
        $borrowerWallet = $this->getClientWallet($transaction['id_client']);

        if (false === $borrowerWallet && isset($idProject)) {
            $borrowerWallet = $this->getBorrowerWallet($idProject);
        }

        if (empty($borrowerWallet)) {
            $this->insertIntoNonTreatedTransactions($transaction, 'borrower wallet not found');
            return;
        }

        $operation['id_type']             = $this->getOperationType('borrower_provision');
        $operation['id_wallet_creditor']  = $borrowerWallet['id'];
        $operation['id_project']          = empty($idProject) ? null : $idProject;
        $operation['id_wire_transfer_in'] = empty($transaction['id_virement']) ? null : $transaction['id_virement'];
        $operation['amount']              = $this->calculateOperationAmount($transaction['montant']);
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

        $borrowerWallet = $this->getClientWallet($transaction['id_client']);

        if (false === $borrowerWallet) {
            $this->insertIntoNonTreatedTransactions($transaction, 'borrower wallet not found');
            return;
        }

        $operation['id_type']              = $this->getOperationType('borrower_withdraw');
        $operation['amount']               = $this->calculateOperationAmount($transaction['montant']);
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
                WHERE id_loan NOT IN (SELECT id_loan FROM operation WHERE id_type = 4 AND id_project = :idProject) AND loans.id_project = :idProject';

        $statement = $this->dataBaseConnection->executeQuery($query, ['idProject' => $transaction['id_project']]);
        while ($loan = $statement->fetch(\PDO::FETCH_ASSOC)){
            $lenderAccount->get($loan['id_lender']);
            $this->createLoan($lenderAccount->id_client_owner, $loan, $transaction);
        }
    }

    private function migrateUnilendProjectCommission(array $transaction)
    {
        $unilendWallet  = $this->getWalletByLabel('unilend');
        $borrowerWallet = $this->getClientWallet($transaction['id_client']);

        if (false === $borrowerWallet) {
            $this->insertIntoNonTreatedTransactions($transaction, 'borrower wallet not found');
            return;
        }

        $operation['id_type']            = $this->getOperationType('borrower_commission');
        $operation['amount']             = $this->calculateOperationAmount($transaction['montant_unilend']);
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

    private function migrateUnilendRepaymentCommission(array $transaction)
    {
        $previousCommissionTransaction = $this->checkIfCommissionHasAlreadyBeenMigrated($transaction);
        if (false === empty($previousCommissionTransaction)) {
            $this->insertIntoNonTreatedTransactions($transaction, 'Commission already migrated with transaction : ' . $previousCommissionTransaction, 1);
            return;
        }

        $unilendWallet = $this->getWalletByLabel('unilend');

        /** @var \echeanciers_emprunteur $borrowerRepaymentSchedule */
        $borrowerRepaymentSchedule = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('echeanciers_emprunteur');
        $borrowerRepaymentSchedule->get($transaction['id_echeancier_emprunteur']);
        $borrowerWallet = $this->getBorrowerWallet($borrowerRepaymentSchedule->id_project);

        if (false === $borrowerWallet) {
            $this->insertIntoNonTreatedTransactions($transaction, 'borrower wallet not found');
            return;
        }

        $operation['id_type']            = $this->getOperationType('borrower_commission');
        $operation['amount']             = $this->calculateOperationAmount(bcadd($borrowerRepaymentSchedule->commission, $borrowerRepaymentSchedule->tva, 2));
        $operation['id_project']         = $borrowerRepaymentSchedule->id_project;
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

        $lenderWallet = $this->getClientWallet($transaction['id_client']);
        if (false === $lenderWallet) {
            $this->insertIntoNonTreatedTransactions($transaction, 'lender wallet not found');
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

        if (false === $borrowerWallet) {
            $this->insertIntoNonTreatedTransactions($transaction, 'borrower wallet not found');
            return;
        }

        $operation['id_type']               = $this->getOperationType('gross_interest_repayment');
        $operation['id_wallet_creditor']    = $lenderWallet['id'];
        $operation['id_wallet_debtor']      = $borrowerWallet['id'];
        $operation['id_repayment_schedule'] = (false === empty($transaction['id_echeancier'])) ? $transaction['id_echeancier'] : null;
        $operation['id_project']            = $idProject;
        $operation['amount']                = $this->calculateOperationAmount(bcadd($transaction['montant'], $totalTax, 4));
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
                        $operation['id_type'] = $this->getOperationType('tax_prelevements_obligatoires');
                        $walletLabel          = 'tax_prelevements_obligatoires';
                        break;
                    case \tax_type::TYPE_CSG:
                        $operation['id_type'] = $this->getOperationType('tax_csg');
                        $walletLabel          = 'tax_csg';
                        break;
                    case \tax_type::TYPE_SOCIAL_DEDUCTIONS:
                        $operation['id_type'] = $this->getOperationType('tax_prelevements_sociaux');
                        $walletLabel          = 'tax_prelevements_sociaux';
                        break;
                    case \tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS:
                        $operation['id_type'] = $this->getOperationType('tax_contributions_additionnelles');
                        $walletLabel          = 'tax_contributions_additionnelles';
                        break;
                    case \tax_type::TYPE_SOLIDARITY_DEDUCTIONS:
                        $operation['id_type'] = $this->getOperationType('tax_prelevements_de_solidarite');
                        $walletLabel          = 'tax_prelevements_de_solidarite';
                        break;
                    case \tax_type::TYPE_CRDS:
                        $operation['id_type'] = $this->getOperationType('tax_crds');
                        $walletLabel          = 'tax_crds';
                        break;
                    case \tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE:
                        $operation['id_type'] = $this->getOperationType('tax_retenues_a_la_source');
                        $walletLabel          = 'tax_retenues_a_la_source';
                        break;
                    default :
                        $this->getContainer()->get('monolog.logger.migration')->error('Unknown tax_type for transaction : ' . $transaction['id_transaction']);
                        return;
                }

                $taxWallet = $this->getWalletByLabel($walletLabel);

                $operation['amount']                = $this->calculateOperationAmount($tax['amount']);
                $operation['id_repayment_schedule'] = $transaction['id_echeancier'];
                $operation['id_wallet_creditor']    = $taxWallet['id'];
                $operation['id_wallet_debtor']      = $lenderWallet['id'];
                $operation['added']                 = $transaction['date_transaction'];
                $operation['id']                    = $this->newOperation($operation);

                $this->debitAvailableBalance($lenderWallet, $operation);
                $this->saveWalletBalanceHistory($lenderWallet, $operation);

                $this->creditAvailableBalance($taxWallet, $operation);
                $this->saveWalletBalanceHistory($taxWallet, $operation);

            }
        }
    }

    private function getWalletByLabel($label)
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

        $taxWallet                     = $this->getWalletByLabel('tax_prelevements_obligatoires');
        $operation['id_type']          = $this->getOperationType('tax_prelevements_obligatoires_withdraw');
        $operation['amount']           = $taxWallet['available_balance'];
        $operation['id_wallet_debtor'] = $taxWallet['id'];
        $operation['added']            = $transaction['date_transaction'];
        $operation['id']               = $this->newOperation($operation);

        $totalTaxAmount = bcsub($totalTaxAmount, $operation['amount'], 2);

        $this->debitAvailableBalance($taxWallet, $operation);
        $this->saveWalletBalanceHistory($taxWallet, $operation);

        unset($taxWallet, $operation);

        $taxWallet                     = $this->getWalletByLabel('tax_csg');
        $operation['id_type']          = $this->getOperationType('tax_csg_withdraw');
        $operation['amount']           = $taxWallet['available_balance'];
        $operation['id_wallet_debtor'] = $taxWallet['id'];
        $operation['added']            = $transaction['date_transaction'];
        $operation['id']               = $this->newOperation($operation);

        $totalTaxAmount = bcsub($totalTaxAmount, $operation['amount'], 2);

        $this->debitAvailableBalance($taxWallet, $operation);
        $this->saveWalletBalanceHistory($taxWallet, $operation);
        unset($taxWallet, $operation);

        $taxWallet                     = $this->getWalletByLabel('tax_prelevements_sociaux');
        $operation['id_type']          = $this->getOperationType('tax_prelevements_sociaux_withdraw');
        $operation['amount']           = $taxWallet['available_balance'];
        $operation['id_wallet_debtor'] = $taxWallet['id'];
        $operation['added']            = $transaction['date_transaction'];
        $operation['id']               = $this->newOperation($operation);

        $totalTaxAmount = bcsub($totalTaxAmount, $operation['amount'], 2);

        $this->debitAvailableBalance($taxWallet, $operation);
        $this->saveWalletBalanceHistory($taxWallet, $operation);
        unset($taxWallet, $operation);

        $taxWallet                     = $this->getWalletByLabel('tax_contributions_additionnelles');
        $operation['id_type']          = $this->getOperationType('tax_contributions_additionnelles_withdraw');
        $operation['amount']           = $taxWallet['available_balance'];
        $operation['id_wallet_debtor'] = $taxWallet['id'];
        $operation['added']            = $transaction['date_transaction'];
        $operation['id']               = $this->newOperation($operation);

        $totalTaxAmount = bcsub($totalTaxAmount, $operation['amount'], 2);

        $this->debitAvailableBalance($taxWallet, $operation);
        $this->saveWalletBalanceHistory($taxWallet, $operation);
        unset($taxWallet, $operation);

        $taxWallet                     = $this->getWalletByLabel('tax_prelevements_de_solidarite');
        $operation['id_type']          = $this->getOperationType('tax_prelevements_de_solidarite_withdraw');
        $operation['amount']           = $taxWallet['available_balance'];
        $operation['id_wallet_debtor'] = $taxWallet['id'];
        $operation['added']            = $transaction['date_transaction'];
        $operation['id']               = $this->newOperation($operation);

        $totalTaxAmount = bcsub($totalTaxAmount, $operation['amount'], 2);

        $this->debitAvailableBalance($taxWallet, $operation);
        $this->saveWalletBalanceHistory($taxWallet, $operation);
        unset($taxWallet, $operation);

        $taxWallet                     = $this->getWalletByLabel('tax_crds');
        $operation['id_type']          = $this->getOperationType('tax_crds_withdraw');
        $operation['amount']           = $taxWallet['available_balance'];
        $operation['id_wallet_debtor'] = $taxWallet['id'];
        $operation['added']            = $transaction['date_transaction'];
        $operation['id']               = $this->newOperation($operation);

        $totalTaxAmount = bcsub($totalTaxAmount, $operation['amount'], 2);

        $this->debitAvailableBalance($taxWallet, $operation);
        $this->saveWalletBalanceHistory($taxWallet, $operation);
        unset($taxWallet, $operation);

        $taxWallet                     = $this->getWalletByLabel('tax_retenues_a_la_source');
        $operation['id_type']          = $this->getOperationType('tax_retenues_a_la_source_withdraw');
        $operation['amount']           = $taxWallet['available_balance'];
        $operation['id_wallet_debtor'] = $taxWallet['id'];
        $operation['added']            = $transaction['date_transaction'];
        $operation['id']               = $this->newOperation($operation);

        $totalTaxAmount = bcsub($totalTaxAmount, $operation['amount'], 2);

        $this->debitAvailableBalance($taxWallet, $operation);
        $this->saveWalletBalanceHistory($taxWallet, $operation);
        unset($taxWallet, $operation);

        if (0 < abs($totalTaxAmount)) {
            $this->getContainer()->get('monolog.logger.migration')->error('Monthly tax amounts do not match for transaction : ' . $transaction['id_transaction'] . ' - difference of ' . $totalTaxAmount);
        }
    }

    private function migrateUnilendWithdrawal(array $transaction)
    {
        $wallet = $this->getWalletByLabel('unilend');

        /** @var \virements $wireTransferOut */
        $wireTransferOut = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('virements');
        $wireTransferOut->get($transaction['id_transaction'], 'id_transaction');

        $operation['id_type']              = $this->getOperationType('unilend_withdraw');
        $operation['id_wallet_debtor']     = $wallet['id'];
        $operation['amount']               = $this->calculateOperationAmount($transaction['montant']);
        $operation['id_wire_transfer_out'] = false === empty($wireTransferOut->id_virement) ? $wireTransferOut->id_virement :  null;
        $operation['added']                = $transaction['date_transaction'];
        $operation['id']                   = $this->newOperation($operation);

        $this->debitAvailableBalance($wallet, $operation);
        $this->saveWalletBalanceHistory($wallet, $operation);
    }

    private function migrateBalanceTransfer(array $transaction)
    {
        /** @var \transfer $transfer */
        $transfer = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('transfer');
        $transfer->get($transaction['id_transfer']);

        if (0 > $transaction['montant']) {
            $debtorWallet   = $this->getClientWallet($transaction['id_client']);
            $creditorWallet = $this->getClientWallet($transfer->id_client_receiver);
            $operation['id_type']              = $this->getOperationType('lender_transfer');
            $operation['id_wallet_debtor']     = $debtorWallet['id'];
            $operation['id_wallet_creditor']   = $creditorWallet['id'];
            $operation['amount']               = $this->calculateOperationAmount($transaction['montant']);
            $operation['id_transfer']          = $transfer->id_transfer;
            $operation['added']                = $transaction['date_transaction'];
            $operation['id']                   = $this->newOperation($operation);

            $this->debitAvailableBalance($debtorWallet, $operation);
            $this->saveWalletBalanceHistory($debtorWallet, $operation);
            $this->creditAvailableBalance($creditorWallet, $operation);
            $this->saveWalletBalanceHistory($creditorWallet, $operation);
        }
    }

    private function migrateBorrowerProvisionCancel(array $transaction)
    {
        /** @var \receptions $directDebit */
        $directDebit = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('receptions');
        $directDebit->get($transaction['id_prelevement']);

        $borrowerWallet = $this->getClientWallet($transaction['id_client']);
        if (false === $borrowerWallet) {
            $this->insertIntoNonTreatedTransactions($transaction, 'borrower wallet not found');
            return;
        }

        $operation['id_type']              = $this->getOperationType('borrower_provision_cancel');
        $operation['amount']               = $this->calculateOperationAmount($transaction['montant']);
        $operation['id_wallet_debtor']     = $borrowerWallet['id'];
        $operation['added']                = $transaction['added'];
        $operation['id_project']           = empty($directDebit->id_project) ? null : $directDebit->id_project;
        $operation['id_wire_transfer_in']  = empty($directDebit->id_reception) ? null : $directDebit->id_reception;
        $operation['id']                   = $this->newOperation($operation);

        $this->debitAvailableBalance($borrowerWallet, $operation);
        $this->saveWalletBalanceHistory($borrowerWallet, $operation);
    }

    private function getOperationType($label)
    {
        $query = 'SELECT id FROM operation_type WHERE label = :label';
        $statement = $this->dataBaseConnection->executeQuery($query, ['label' => $label]);

        return $statement->fetchColumn();
    }

    private function migrateWelcomeOfferProvision(array $transaction)
    {
        $promotionWallet = $this->getWalletByLabel('unilend_promotional_operation');

        $operation['id_type']             = $this->getOperationType('unilend_promotional_operation_provision');
        $operation['id_wallet_creditor']  = $promotionWallet['id'];
        $operation['amount']              = $this->calculateOperationAmount($transaction['montant']);
        $operation['id_wire_transfer_in'] = empty($transaction['id_virement']) ? null : $transaction['id_virement'];
        $operation['added']               = $transaction['date_transaction'];
        $operation['id']                  = $this->newOperation($operation);

        $this->creditAvailableBalance($promotionWallet, $operation);
        $this->saveWalletBalanceHistory($promotionWallet, $operation);
    }

    private function calculateOperationAmount($amount)
    {
        return abs(round(bcdiv($amount, 100, 4), 2));
    }

    private function migrateRecoveryCommissionToBorrower(array $transaction)
    {
        $collectorWallet = $this->getWalletByLabel('debt_collector');
        $borrowerWallet  = $this->getClientWallet($transaction['id_client']);

        $operation['id_type']             = $this->getOperationType('collection_commission_provision');
        $operation['id_wallet_debtor']    = $collectorWallet['id'];
        $operation['id_wallet_creditor']  = $borrowerWallet['id'];
        $operation['amount']              = $this->calculateOperationAmount($this->getRecoveryCommissionAmount($transaction));
        $operation['added']               = $transaction['date_transaction'];
        $operation['id']                  = $this->newOperation($operation);

        $this->debitAvailableBalance($collectorWallet, $operation);
        $this->saveWalletBalanceHistory($collectorWallet, $operation);
        $this->creditAvailableBalance($borrowerWallet, $operation);
        $this->saveWalletBalanceHistory($borrowerWallet, $operation);
    }


    private function migrateRecoveryToLender(array $transaction)
    {
        $filePrefix = $transaction['id_project'] . '_' . substr($transaction['date_transaction'], 0, 10);
        $recoveryDetails = $this->getRecoveryDetails($filePrefix);

        $netTransactionAmount = $this->calculateOperationAmount($transaction['montant']);

        $transaction['montant'] = bcmul($recoveryDetails[$transaction['id_client']]['gross_amount'], 100);
        $this->migrateCapitalRepayments($transaction);

        $collectorWallet = $this->getWalletByLabel('debt_collector');
        $lenderWallet    = $this->getClientWallet($transaction['id_client']);

        if (empty($lenderWallet)) {
            $this->insertIntoNonTreatedTransactions($transaction, 'lender wallet not found');
            return;
        }

        $operation['id_type']             = $this->getOperationType('collection_commission_lender');
        $operation['id_wallet_debtor']    = $lenderWallet['id'];
        $operation['id_wallet_creditor']  = $collectorWallet['id'];
        $operation['amount']              = $recoveryDetails[$transaction['id_client']]['commission_amount'];
        $operation['added']               = $transaction['date_transaction'];
        $operation['id']                  = $this->newOperation($operation);

        $this->debitAvailableBalance($lenderWallet, $operation);
        $this->saveWalletBalanceHistory($lenderWallet, $operation);
        $this->creditAvailableBalance($collectorWallet, $operation);
        $this->saveWalletBalanceHistory($collectorWallet, $operation);

        if (bcsub($recoveryDetails[$transaction['id_client']]['gross_amount'], $recoveryDetails[$transaction['id_client']]['commission_amount'], 2) != $netTransactionAmount) {
            $this->getContainer()->get('monolog.logger.migration')->error('Recovery payment amounts do not match: ' . $recoveryDetails[$transaction['id_client']]['gross_amount'] . ' - ' .  $recoveryDetails[$transaction['id_client']]['commission_amount'] . ' != ' . $netTransactionAmount . PHP_EOL . 'clientid = ' . $transaction['id_client'] . ' - project : ' . $transaction['id_project'] . ' - date : ' . $transaction['date_transaction'] );
        }
    }


    private function getRecoveryDetails($filePrefix)
    {
        $cachePool = $this->getContainer()->get('memcache.default');

        $cachedItem = $cachePool->getItem('recovery_' . $filePrefix);
        if (false === $cachedItem->isHit()) {
            $recoveryDetails = $this->getRecoveryFileContents($filePrefix);
            $cachedItem->set($recoveryDetails)->expiresAfter(CacheKeys::SHORT_TIME);
            $cachePool->save($cachedItem);

            return $recoveryDetails;
        } else {
            return $cachedItem->get();
        }
    }

    private function getRecoveryFileContents($filePrefix)
    {
        $recoveryDetails = [];
        $fileName        = $this->getContainer()->getParameter('path.protected') . 'import/soldes_recouvrement/' . $filePrefix . '_recouvrement.csv';
        if (false === file_exists($fileName)) {
            throw new \Exception($this->getContainer()->getParameter('path.protected') . 'import/soldes_recouvrement/' . $filePrefix . '_recouvrement.csv not found');
        }
        if (false === ($handle = fopen($fileName, 'r'))) {
            throw new \Exception($this->getContainer()->getParameter('path.protected') . 'import/soldes_recouvrement/' . $filePrefix . '_recouvrement.csv cannot be opened');
        }

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $clientId                                        = $row[0];
            $recoveryDetails[$clientId]['gross_amount']      = str_replace(',', '.', $row[1]);
            $recoveryDetails[$clientId]['commission_amount'] = str_replace(',', '.', $row[2]);
        }
        fclose($handle);

        return $recoveryDetails;
    }


    private function getRecoveryCommissionAmount(array $transaction)
    {
        switch ($transaction['id_project']) {
            case 1124:
                switch(substr($transaction['date_transaction'], 0, 10)) {
                    case '2016-03-08':
                        return bcmul(62.32, 100);
                    case '2016-03-29':
                        return bcmul(382.10, 100);
                    case '2016-05-17':
                    case '2016-06-14':
                    case '2016-10-07':
                        return bcmul(366.24, 100);
                    default:
                        $this->getContainer()->get('monolog.logger.migration')->error('Recovery payment date could not be found for : ' . $transaction['id_transaction']);
                        break;
                }
                break;
            case 2900:
                switch(substr($transaction['date_transaction'], 0, 10)){
                    case '2016-03-29':
                        return bcmul(1084.25, 100);
                    case '2016-10-07':
                        return bcmul(5421.61, 100);
                    default:
                        $this->getContainer()->get('monolog.logger.migration')->error('Recovery payment date could not be found for : ' . $transaction['id_transaction']);
                        break;
                }
                break;
            case 3013:
                switch(substr($transaction['date_transaction'], 0, 10)){
                    case '2016-03-08':
                        return bcmul(343.20, 100);
                    case '2016-03-29':
                        return bcmul(117.4, 100);
                    case '2016-07-26':
                        return bcmul(156.08, 100);
                    case '2016-08-12':
                    case '2016-10-27':
                        return bcmul(78.15, 100);
                    default:
                        $this->getContainer()->get('monolog.logger.migration')->error('Recovery payment date could not be found for : ' . $transaction['id_transaction']);
                        break;
                }
                break;
            case 8544:
                switch(substr($transaction['date_transaction'], 0, 10)) {
                    case '2016-07-26':
                        return bcmul(194.08, 100);
                    case '2016-12-09':
                        return bcmul(583.91, 100);
                    case '2017-02-10':
                        return bcmul(583.91, 100);
                    default:
                        $this->getContainer()->get('monolog.logger.migration')->error('Recovery payment date could not be found for : ' . $transaction['id_transaction']);
                        break;
                }
                break;
            case 9386:
                switch (substr($transaction['date_transaction'], 0, 10)) {
                    case '2016-10-27':
                        return bcmul(946.31, 100);
                    case '2017-01-25':
                        return bcmul(946.31, 100);
                    default:
                        $this->getContainer()->get('monolog.logger.migration')->error('Recovery payment date could not be found for : ' . $transaction['id_transaction']);
                        break;
                }
                break;
            default:
                $this->getContainer()->get('monolog.logger.migration')->error('Recovery payment could not be found for project : ' . $transaction['id_project']);
                break;
        }

        return null;
    }

    private function insertIntoNonTreatedTransactions(array $transaction, $message, $status = 0)
    {
        $this->dataBaseConnection->executeQuery('INSERT INTO non_migrated_transactions (id_transaction, status, message) VALUE (:transactionId, :status, :message)',
          ['transactionId' => $transaction['id_transaction'], 'status' => $status, 'message' => $message]);
    }

    private function checkIfCommissionHasAlreadyBeenMigrated(array $transaction)
    {
        $query = 'SELECT id_transaction
                    FROM transaction_treated
                  WHERE id_transaction IN (SELECT id_transaction
                                          FROM transactions
                                          WHERE id_echeancier_emprunteur = :idEcheancierEmprunteur AND id_transaction != :idTransaction)';

        $statement = $this->dataBaseConnection->executeQuery($query, ['idEcheancierEmprunteur' => $transaction['id_echeancier_emprunteur'], 'idTransaction' => $transaction['id_transaction']]);

        return $statement->fetchColumn();
    }

    private function lenderRegulation($clientId, $amount, $date)
    {
        $unilendWallet = $this->getWalletByLabel('unilend');
        $lenderWallet  = $this->getClientWallet($clientId);

        $operation['id_type']            = $this->getOperationType('unilend_lender_regularization');
        $operation['id_wallet_debtor']   = $unilendWallet['id'];
        $operation['id_wallet_creditor'] = $lenderWallet['id'];
        $operation['amount']             = $amount;
        $operation['added']              = $date;
        $operation['id']                 = $this->newOperation($operation);

        $this->debitAvailableBalance($unilendWallet, $operation);
        $this->saveWalletBalanceHistory($unilendWallet, $operation);
        $this->creditAvailableBalance($lenderWallet, $operation);
        $this->saveWalletBalanceHistory($lenderWallet, $operation);
    }

}
