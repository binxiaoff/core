<?php


namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bridge\Doctrine\DBAL\Connection;

class DevMigrateTransactionsCommand extends ContainerAwareCommand
{
    /** @var  Connection */
    private $dataBaseConnection;

    protected function configure()
    {
        $this
            ->setName('dev:migrate:transactions')
            ->setDescription('Migrate transactions into operations');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Connection $dataBaseConnection */
        $this->dataBaseConnection  = $this->getContainer()->get('database_connection');

        /** @var \transactions $transactions */
        $transactions = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('transactions');
        $transactionsToMigrate = $transactions->select('status = 1', 'date_transaction ASC', 5000, 5000);
        $transactionCount = 0;
        foreach ($transactionsToMigrate as $transaction) {
            switch($transaction['type_transaction']) {
                case \transactions_types::TYPE_LENDER_SUBSCRIPTION:
                case \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT:
                case \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT:
                    $this->migrateLenderProvision($transaction);
                    break;
                case \transactions_types::TYPE_LENDER_LOAN:
                    if (0 > $transaction['montant']) {
                        if (empty($transaction['id_project'])) {
                            $this->migrateBid($transaction);
                        } else {
                            $this->migrateBidOrLoan($transaction);
                        }
                    } else {
                        if (empty($transaction['id_loan_remb'])) {
                            $this->migrateRefusedBid($transaction);
                            break;
                        } else {
                            $this->migrateRefusedLoan($transaction);
                            break;
                        }
                    }
                    break;
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
                    $this->migrateInterestRepayments($transaction); //TODO
                    break;
                case \transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT:
                case \transactions_types::TYPE_BORROWER_REPAYMENT:
                    $this->migrateBorrowerRepayment($transaction); //TODO
                    break;
            }

            $transactionCount += 1;
        }
        $output->writeln('Number of transactions migrated : ' . $transactionCount);
    }

    private function newOperation(array $operation)
    {
        $query = 'INSERT INTO operation (id_type, id_wallet_debtor, id_wallet_creditor, amount, id_project, id_bid, id_loan, id_payment_schedule, id_repayment_schedule, id_backpayline, id_welcome_offer, id_wire_transfer_out, id_wire_transfer_in,     id_direct_debit, id_transfer, added) VALUES (:idType, :idWalletDebtor, :idWalletCreditor, :amount, :idProject, :idBid, :idLoan, :idPaymentSchedule, :idRepaymentSchedule, :idBackPayline, :idWelcomeOffer, :idWireTransferOut, :idWireTransferIn, :idDirectDebit, :idTransfer, :added)';

        $this->dataBaseConnection->executeQuery($query, [
            'idType'              => $operation['id_type'],
            'idWalletDebtor'      => isset($operation['id_wallet_debtor']) ? $operation['id_wallet_debtor'] : null,
            'idWalletCreditor'    => isset($operation['id_wallet_creditor']) ? $operation['id_wallet_creditor'] : null,
            'amount'              => $operation['amount'],
            'idProject'           => isset($operation['id_project']) ? $operation['id_project'] : null,
            'idBid'               => isset($operation['id_bid']) ? $operation['id_bid'] : null,
            'idLoan'              => isset($operation['id_loan']) ? $operation['id_loan'] : null,
            'idPaymentSchedule'   => isset($operation['id_payment_schedule']) ? $operation['id_payment_schedule'] : null,
            'idRepaymentSchedule' => isset($operation['id_repayment_schedule']) ? $operation['id_repayment_schedule'] : null,
            'idBackPayline'       => isset($operation['id_backpayline']) ? $operation['id_backpayline'] : null,
            'idWelcomeOffer'      => isset($operation['id_welcome_offer']) ? $operation['id_welcome_offer'] : null,
            'idWireTransferOut'   => isset($operation['id_wire_transfer_out']) ? $operation['id_wire_transfer_out'] : null,
            'idWireTransferIn'    => isset($operation['id_wire_transfer_in']) ? $operation['id_wire_transfer_in'] : null,
            'idDirectDebit'       => isset($operation['id_direct_debit']) ? $operation['id_direct_debit'] : null,
            'idTransfer'          => isset($operation['id_transfer']) ? $operation['id_transfer'] : null,
            'added'               => $operation['added'],
        ]);
        return $this->dataBaseConnection->lastInsertId();
    }

    private function debitAvailableBalance(array &$wallet, array &$operation)
    {
        $balance                     = bcsub($wallet['committed_balance'], $operation['amount'], 2);
        $wallet['committed_balance'] = $balance;
        $this->updateWalletBalance($wallet, $operation);
    }

    private function debitCommittedBalance(array &$wallet, array &$operation)
    {
        $balance                     = bcsub($wallet['available_balance'], $operation['amount'], 2);
        $wallet['available_balance'] = $balance;
        $this->updateWalletBalance($wallet, $operation);
    }

    private function creditCommittedBalance(array &$wallet, array &$operation)
    {
        $balance                     = bcadd($wallet['committed_balance'], $operation['amount'], 2);
        $wallet['committed_balance'] = $balance;
        $this->updateWalletBalance($wallet, $operation);
    }

    private function creditAvailableBalance(array &$wallet, array &$operation)
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

    private function saveWalletBalanceHistory(array &$wallet, array $operation = null, array $bid = null)
    {
        $query = 'INSERT INTO wallet_balance_history (id_wallet, available_balance, committed_balance, id_operation, id_bid, added) 
                          VALUES (:walletId, :availableBalance, :committedBalance, :operationId, :bidId, :added)';
        $this->dataBaseConnection->executeQuery($query, [
            'walletId'         => $wallet['id'],
            'availableBalance' => $wallet['available_balance'],
            'committedBalance' => $wallet['committed_balance'],
            'operationId'      => isset($operation['id']) ? $operation['id'] : null,
            'bidId'            => isset($bid['id_bid']) ? $bid['id_bid'] : null,
            'added'            => isset($operation['added']) ? $operation['added'] : $bid['added']
        ]);
    }

    private function migrateLenderProvision(array $transaction)
    {
        $queryWallet = 'SELECT * FROM wallet where id_client = :clientId';
        $statement   = $this->dataBaseConnection->executeQuery($queryWallet, ['clientId' => $transaction['id_client']]);
        $wallet      = $statement->fetchAll(\PDO::FETCH_ASSOC)[0];

        if (false === empty($transaction['id_backpayline'])) {
            $this->migratePayline($transaction, $wallet);
        }

        $operation['id_type']             = 1;
        $operation['id_wallet_creditor']  = $wallet['id'];
        $operation['amount']              = round(bcdiv($transaction['montant'], 100, 4), 2);
        $operation['id_backpayline']      = (false === empty($transaction['id_backpayline']) ? $transaction['id_backpayline'] : null);
        $operation['id_wire_transfer_in'] = (false === empty($transaction['id_virement']) ? $transaction['id_virement'] : null);
        $operation['added']               = $transaction['date_transaction'];
        $operation['id']                  = $this->newOperation($operation);

        $this->creditAvailableBalance($wallet, $operation);
        $this->saveWalletBalanceHistory($wallet, $operation);
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

    private function migrateRefusedLoan(array $transaction)
    {
        /** @var \loans $loan */
        $loan = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('loans');

        if ($loan->get($transaction['id_loan_remb'])){
            $walletDebtor   = $this->getBorrowerWallet($transaction['id_project']);
            $walletCreditor = $this->getWallet($transaction['id_client']);

            $operation['id_type']            = 5;
            $operation['id_wallet_creditor'] = $walletCreditor['id'];
            $operation['id_wallet_debtor']   = $walletDebtor['id'];
            $operation['id_loan']            = $loan->id_loan;
            $operation['id_project']         = $loan->id_project;
            $operation['amount']             = abs(round(bcdiv($transaction['montant'], 100, 4), 2));
            $operation['added']              = $loan->updated;
            $operation['id']                 = $this->newOperation($operation);

            $this->creditAvailableBalance($walletCreditor, $operation);
            $this->saveWalletBalanceHistory($walletCreditor, $operation);

            $this->debitCommittedBalance($walletDebtor, $operation);
            $this->saveWalletBalanceHistory($walletDebtor, $operation);
        } else {
            $this->getContainer()->get('logger')->error('Loan could not be found for transaction : ' . $transaction['id_transaction']);
        }
    }

    private function migrateBidOrLoan(array $transaction)
    {
        /** @var \wallets_lines $walletLines */
        $walletLines = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('wallets_lines');
        /** @var \bids $bid */
        $bid = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('bids');

        if (
            $walletLines->get($transaction['id_transaction'], 'id_transaction')
            && $bid->get($walletLines->id_wallet_line, 'id_lender_wallet_line')
        ) {
            /** @var \accepted_bids $acceptedBids */
            $acceptedBids = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('accepted_bids');
            $loans        = $acceptedBids->select('id_bid = ' . $bid->id_bid);

            if (empty($loans)) {
                $this->migrateBid($transaction, $walletLines, $bid);
            } else {
                /** @var \loans $loanEntity */
                $loanEntity = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('loans');

                foreach ($loans as $loan) {
                    $loanEntity->get($loan['id_loan']);

                    $walletDebtor   = $this->getWallet($transaction['id_client']);
                    $walletCreditor = $this->getBorrowerWallet($transaction['id_project']);

                    $operation['id_type']            = 4;
                    $operation['id_wallet_creditor'] = $walletCreditor['id'];
                    $operation['id_wallet_debtor']   = $walletDebtor['id'];
                    $operation['id_loan']            = $loanEntity->id_loan;
                    $operation['id_project']         = $transaction['id_project'];
                    $operation['amount']             = abs(round(bcdiv($transaction['montant'], 100, 4), 2));
                    $operation['added']              = $loanEntity->added;
                    $operation['id']                 = $this->newOperation($operation);

                    $this->creditCommittedBalance($walletCreditor, $operation);
                    $this->saveWalletBalanceHistory($walletCreditor, $operation);

                    $this->debitCommittedBalance($walletCreditor, $operation);
                    $this->saveWalletBalanceHistory($walletDebtor, $operation);
                    $loanEntity->unsetData();
                }
            }
        } else {
            $this->getContainer()->get('logger')->error('bid cound not be found for transaction : ' . $transaction['id_transaction']);
        }
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

        return $statement->fetchAll(\PDO::FETCH_ASSOC)[0];
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
        $queryWallet = 'SELECT * FROM wallet where id_client = :clientId';
        $statement   = $this->dataBaseConnection->executeQuery($queryWallet, ['clientId' => $transaction['id_client']]);
        $wallet      = $statement->fetchAll(\PDO::FETCH_ASSOC)[0];

        /** @var \virements $wireTransferOut */
        $wireTransferOut = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('virements');
        $wireTransferOut->get($transaction['id_transaction'], 'id_transaction');

        $operation['id_type']              = 1;
        $operation['id_wallet_debtor']     = $wallet['id'];
        $operation['amount']               = round(bcdiv($transaction['montant'], 100, 4), 2);
        $operation['id_wire_transfer_out'] = $wireTransferOut->id_virement;
        $operation['added']                = $transaction['date_transaction'];
        $operation['id']                   = $this->newOperation($operation);

        $this->debitAvailableBalance($wallet, $operation);
        $this->saveWalletBalanceHistory($wallet, $operation);
    }

    private function migrateBid(array $transaction, \wallets_lines $walletLines = null, \bids $bids = null)
    {
        if (null === $walletLines) {
            /** @var \wallets_lines $walletLines */
            $walletLines = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('wallets_lines');
            $walletLines->get($transaction['id_transaction'], 'id_transaction');
        }

        if (null === $bids) {
            /** @var \bids $bids */
            $bids = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('bids');
            $bids->get($walletLines->id_wallet_line, 'id_lender_wallet_line');
        }

        if (empty($walletLines->id_wallet_line) && empty($bids->id_bid)) {
            $this->getContainer()->get('logger')->error('Bid could not be found for transaction : ' . $transaction['id_transaction']);
            return;
        }

        if (false === empty($walletLines->id_wallet_line) && empty($bids->id_bid) && $walletLines->amount > 0) {
            $this->migrateRefusedBid($transaction);
        }

        $lenderWallet = $this->getWallet($transaction['id_client']);
        $bid['id']    = $bids->id_bid;
        $bid['added'] = $bids->added;
        $amount       = abs(round(bcdiv($bids->amount, 100, 4), 2));

        $availableBalance = bcsub($lenderWallet['available_balance'], $amount, 2);
        $committedBalance = bcadd($lenderWallet['committed_balance'], $amount, 2);

        $lenderWallet['available_balance'] = $availableBalance;
        $lenderWallet['committed_balance'] = $committedBalance;

        $this->updateWalletBalance($lenderWallet, $bid);
        $this->saveWalletBalanceHistory($lenderWallet, null, $bid);
    }

    private function migrateRefusedBid(array $transaction)
    {
        /** @var \bids $bids */
        $bids   = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('bids');
        $bid    = [];
        $amount = 0;

        if (false === $bids->get($transaction['id_bid_remb'])) {
            /** @var \wallets_lines $walletLines */
            $walletLines = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('wallets_lines');
            if ($walletLines->get($transaction['id_transaction'], 'id_transaction')){
                if (false === $bids->get($walletLines->id_bid_remb)) {
                    $bid['id']    = null;
                    $bid['added'] = $transaction['added'];
                    $amount       = abs(round(bcdiv($transaction['montant'], 100, 4), 2));
                }
            }
        } else {
            $this->getContainer()->get('logger')->error('Bid not found for transaction : ' . $transaction['id_transaction']);
            return;
        }

        if (false === empty($bids->id_bid)) {
            $bid['id']    = $bids->id_bid;
            $bid['added'] = $bids->added;
            $amount       = abs(round(bcdiv($bids->amount, 100, 4), 2));
        }

        $lenderWallet = $this->getWallet($transaction['id_client']);

        $availableBalance                  = bcadd($lenderWallet['available_balance'], $amount, 2);
        $committedBalance                  = bcsub($lenderWallet['committed_balance'], $amount, 2);
        $lenderWallet['available_balance'] = $availableBalance;
        $lenderWallet['committed_balance'] = $committedBalance;

        $this->updateWalletBalance($lenderWallet, $bid);
        $this->saveWalletBalanceHistory($lenderWallet, null, $bid);
    }

    private function migrateCapitalRepayments(array $transaction)
    {
        $lenderWallet = $this->getWallet($transaction['id_client']);

        if (empty($transaction['id_project']) && false === empty($transaction['id_echeancier'])) {
            /** @var \echeanciers $repaymentSchedule */
            $repaymentSchedule = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('echeanciers');
            $repaymentSchedule->get($transaction['id_echeancier']);
            $borrowerWallet = $this->getBorrowerWallet($repaymentSchedule->id_project);
        } else {
            $borrowerWallet = $this->getBorrowerWallet($transaction['id_project']);
        }

        $operation['id_type']             = 11;
        $operation['id_wallet_creditor']  = $lenderWallet['id'];
        $operation['id_wallet_debtor']    = $borrowerWallet['id'];
        $operation['id_payment_schedule'] = $transaction['id_echeancier'];
        $operation['id_project']          = $transaction['id_project'];
        $operation['amount']              = round(bcdiv($transaction['montant'], 100, 4), 2);
        $operation['added']               = $transaction['date_transaction'];
        $operation['id']                  = $this->newOperation($operation);

        $this->creditAvailableBalance($walletCreditor, $operation);
        $this->saveWalletBalanceHistory($walletCreditor, $operation);

        $this->debitAvailableBalance($walletCreditor, $operation);
        $this->saveWalletBalanceHistory($walletDebtor, $operation);
    }

    private function migrateBorrowerRepayment(array $transaction)
    {
        $borrowerWallet = $this->getBorrowerWallet($transaction['id_project']);

        $operation['id_type']             = 6;
        $operation['id_wallet_creditor']  = $borrowerWallet['id'];
        $operation['id_project']          = $transaction['id_project'];
        $operation['id_wire_transfer_in'] = $transaction['id_virement'];
        $operation['amount']              = round(bcdiv($transaction['montant'], 100, 4), 2);
        $operation['added']               = $transaction['date_transaction'];
        $operation['id']                  = $this->newOperation($operation);

        $this->creditAvailableBalance($borrowerWallet, $operation);
        $this->saveWalletBalanceHistory($borrowerWallet, $operation);
    }
}
