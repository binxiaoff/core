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
        $entityManager  = $this->get('unilend.service.entity_manager');
        /** @var \transactions $transaction */
        $transaction    = $entityManager->getRepository('transactions');
        /** @var \backpayline $backPayline */
        $backPayline    = $entityManager->getRepository('backpayline');
        /** @var \lenders_accounts $lendersAccount */
        $lendersAccount = $entityManager->getRepository('lenders_accounts');
        /** @var \wallets_lines $walletLine */
        $walletLine     = $entityManager->getRepository('wallets_lines');
        /** @var \bank_lines $bankLine */
        $bankLine       = $entityManager->getRepository('bank_lines');
        /** @var \settings $settings */
        $settings       = $entityManager->getRepository('settings');

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
                $settings->get('DebugAlertesBusiness', 'type');
                $to = $settings->value;

                if ($transaction->get($response['order']['ref'], 'status = 0 AND etat = 0 AND id_transaction')) {

                    $transaction->id_backpayline   = $backPayline->id_backpayline;
                    $transaction->montant          = $response['payment']['amount'];
                    $transaction->id_langue        = 'fr';
                    $transaction->date_transaction = date('Y-m-d H:i:s');
                    $transaction->status           = '1';
                    $transaction->etat             = '1';
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

                    ////////////////////////////
                    // Mail alert transaction //
                    ////////////////////////////
                    // subject
                    $subject = '[Alerte] BACK PAYLINE Transaction approved';

                    // message
                    $message = '
                    <html>
                    <head>
                      <title>[Alerte] BACK PAYLINE Transaction approved</title>
                    </head>
                    <body>
                      <h3>[Alerte] BACK PAYLINE Transaction approved</h3>
                      <p>Un payement payline accepet&eacute; n\'a pas &eacute;t&eacute; mis &agrave; jour dans la BDD Unilend.</p>
                      <table>
                        <tr>
                          <th>Id client : </th><td>' . $transaction->id_client . '</td>
                        </tr>
                        <tr>
                          <th>montant : </th><td>' . ($transaction->montant / 100) . '</td>
                        </tr>
                        <tr>
                          <th>serialize donnees payline : </th><td>' . serialize($response) . '</td>
                        </tr>
                      </table>
                    </body>
                    </html>
                    ';

                    // To send HTML mail, the Content-type header must be set
                    $headers = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

                    // Additional headers

                    //$headers .= 'To: equinoa <unilend@equinoa.fr>' . "\r\n";
                    $headers .= 'From: Unilend <equipeit@unilend.fr>' . "\r\n";

                    // Mail it
                    mail($to, $subject, $message, $headers);

                } else {
                    ////////////////////////////
                    // Mail alert transaction //
                    ////////////////////////////
                    // subject
                    $subject = '[Alerte] BACK PAYLINE Transaction approved DEJA TRAITE';

                    // message
                    $message = '
                    <html>
                    <head>
                      <title>[Alerte] BACK PAYLINE Transaction approved DEJA TRAITE</title>
                    </head>
                    <body>
                      <h3>[Alerte] BACK PAYLINE Transaction approved DEJA TRAITE</h3>
                      <p>Un payement payline accepet&eacute; deacute;j&agrave; &agrave; jour dans la BDD Unilend.</p>
                      <table>
                        <tr>
                          <th>Id client : </th><td>' . $transaction->id_client . '</td>
                        </tr>
                        <tr>
                          <th>montant : </th><td>' . ($transaction->montant / 100) . '</td>
                        </tr>
                        <tr>
                          <th>serialize donnees payline : </th><td>' . serialize($response) . '</td>
                        </tr>
                      </table>
                    </body>
                    </html>
                    ';

                    // To send HTML mail, the Content-type header must be set
                    $headers = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                    $headers .= 'From: Unilend <equipeit@unilend.fr>' . "\r\n";

                    // Mail it
                    mail($to, $subject, $message, $headers);
                }
            }
        }

        return new Response();
    }
}
