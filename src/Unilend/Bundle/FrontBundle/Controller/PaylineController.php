<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\AutoBidSettingsManager;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\core\Loader;

class PaylineController extends Controller
{
    /**
     * @Route("/notification_payline", name="payline_callback")
     *
     */
    public function callbackAction()
    {
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \transactions $transaction */
        $transaction = $entityManager->getRepository('transactions');
        /** @var \backpayline $backPayline */
        $backPayline = $entityManager->getRepository('backpayline');
        /** @var \lenders_accounts $lendersAccount */
        $lendersAccount = $entityManager->getRepository('lenders_accounts');
        /** @var \wallets_lines $walletLine */
        $walletLine = $entityManager->getRepository('wallets_lines');
        /** @var \bank_lines $bankLine */
        $bankLine = $entityManager->getRepository('bank_lines');

        $params['version'] = '3';
        // GET TOKEN
        if (isset($_POST['token'])) {
            $params['token'] = $_POST['token'];
        } elseif (isset($_GET['token'])) {
            $params['token'] = $_GET['token'];
        } else {
            die;
        }

        require_once $this->getParameter('kernel.root_dir') . '/../librairies/payline/include.php';

        $payline  = new \paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);
        $response = $payline->getWebPaymentDetails($params);

        if (isset($response)) {
            if (false === $backPayline->exist($params['token'], 'token')) {
                $backPayline->code      = $response['result']['code'];
                $backPayline->token     = $params['token'];
                $backPayline->id        = $response['transaction']['id'];
                $backPayline->date      = $response['transaction']['date'];
                $backPayline->amount    = $response['payment']['amount'];
                $backPayline->serialize = serialize($response);
                $backPayline->create();
            }

            if ($response['result']['code'] == '00000') {
                if ($transaction->get($response['order']['ref'], 'status = ' . \transactions::STATUS_PENDING . ' AND id_transaction')) {
                    $transaction->id_backpayline   = $backPayline->id_backpayline;
                    $transaction->montant          = $response['payment']['amount'];
                    $transaction->id_langue        = 'fr';
                    $transaction->date_transaction = date('Y-m-d H:i:s');
                    $transaction->status           = \transactions::STATUS_VALID;
                    $transaction->type_paiement    = ($response['extendedCard']['type'] == 'VISA' ? '0' : ($response['extendedCard']['type'] == 'MASTERCARD' ? '3' : ''));
                    $transaction->update();

                    // On recupere le lender
                    $lendersAccount->get($transaction->id_client, 'id_client_owner');
                    $lendersAccount->status = 1;
                    $lendersAccount->update();

                    // On enrgistre la transaction dans le wallet
                    $walletLine->id_lender                = $lendersAccount->id_lender_account;
                    $walletLine->type_financial_operation = 30; // alimentation preteur
                    $walletLine->id_transaction           = $transaction->id_transaction;
                    $walletLine->status                   = 1;
                    $walletLine->type                     = 1;
                    $walletLine->amount                   = $response['payment']['amount'];
                    $walletLine->create();

                    // Transaction physique donc on enregistre aussi dans la bank lines
                    $bankLine->id_wallet_line    = $walletLine->id_wallet_line;
                    $bankLine->id_lender_account = $lendersAccount->id_lender_account;
                    $bankLine->status            = 1;
                    $bankLine->amount            = $response['payment']['amount'];
                    $bankLine->create();
                }
            }
        }

        return new Response();
    }
}
