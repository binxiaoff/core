<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class CheckPaylineMoneyTransfersCommand extends ContainerAwareCommand
{

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('check:payline')
            ->setDescription('Loops over transactions and compares them to payline feed');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \transactions $transactions */
        $transactions = $entityManager->getRepository('transactions');
        /** @var \backpayline $backpayline */
        $backpayline = $entityManager->getRepository('backpayline');
        /** @var \lenders_accounts $lenders_accounts */
        $lenders_accounts = $entityManager->getRepository('lenders_accounts');
        /** @var \wallets_lines $wallets_lines */
        $wallets_lines = $entityManager->getRepository('wallets_lines');
        /** @var \bank_lines $bank_lines */
        $bank_lines = $entityManager->getRepository('bank_lines');

        require_once $this->getContainer()->getParameter('path.payline') . 'include.php';

        $date = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
        $date = date('Y-m-d', $date);

        $listTran = $transactions->select('type_transaction = ' . \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT . ' AND status = ' . \transactions::STATUS_PENDING . ' AND LEFT(date_transaction, 10) = "' . $date . '"');

        /** @var \paylineSDK $payline */
        $payline = new \paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);

        foreach ($listTran as $t) {
            $array_payline = unserialize($t['serialize_payline']);
            $token         = $array_payline['token'];
            $array         = array();

            $array['token']   = $token;
            $array['version'] = '3';
            $response         = $payline->getWebPaymentDetails($array);

            if (isset($response)) {
                if (false === $backpayline->exist($array['token'], 'token')) {
                    $backpayline->code      = $response['result']['code'];
                    $backpayline->token     = $array['token'];
                    $backpayline->id        = $response['transaction']['id'];
                    $backpayline->date      = $response['transaction']['date'];
                    $backpayline->amount    = $response['payment']['amount'];
                    $backpayline->serialize = serialize($response);
                    $backpayline->create();
                }

                if ($response['result']['code'] == '00000') {
                    if ($transactions->get($response['order']['ref'], 'status = ' . \transactions::STATUS_PENDING . ' AND id_transaction')) {
                        $transactions->id_backpayline   = $backpayline->id_backpayline;
                        $transactions->montant          = $response['payment']['amount'];
                        $transactions->id_langue        = 'fr';
                        $transactions->date_transaction = date('Y-m-d H:i:s');
                        $transactions->status           = \transactions::STATUS_VALID;
                        $transactions->type_paiement    = ($response['extendedCard']['type'] == 'VISA' ? '0' : ($response['extendedCard']['type'] == 'MASTERCARD' ? '3' : ''));
                        $transactions->update();

                        $lenders_accounts->get($transactions->id_client, 'id_client_owner');
                        $lenders_accounts->status = 1;
                        $lenders_accounts->update();

                        $wallets_lines->id_lender                = $lenders_accounts->id_lender_account;
                        $wallets_lines->type_financial_operation = \wallets_lines::TYPE_MONEY_SUPPLY;
                        $wallets_lines->id_transaction           = $transactions->id_transaction;
                        $wallets_lines->status                   = \wallets_lines::STATUS_VALID;
                        $wallets_lines->type                     = \wallets_lines::PHYSICAL;
                        $wallets_lines->amount                   = $response['payment']['amount'];
                        $wallets_lines->create();

                        $bank_lines->id_wallet_line    = $wallets_lines->id_wallet_line;
                        $bank_lines->id_lender_account = $lenders_accounts->id_lender_account;
                        $bank_lines->status            = 1;
                        $bank_lines->amount            = $response['payment']['amount'];
                        $bank_lines->create();
                    }
                }
            }
        }
    }
}
