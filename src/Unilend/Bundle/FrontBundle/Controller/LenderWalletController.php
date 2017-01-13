<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Service\PaylineManager;
use Unilend\Bundle\FrontBundle\Form\LenderWithdrawalType;
use Unilend\core\Loader;

class LenderWalletController extends Controller
{
    const MAX_DEPOSIT_AMOUNT = 5000;
    const MIN_DEPOSIT_AMOUNT = 20;

    /**
     * @Route("/alimentation", name="lender_wallet_deposit")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     * @return Response
     */
    public function walletDepositAction(Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');

        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        $clientData = $client->select('id_client = ' . $this->getUser()->getClientId())[0];

        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        $lenderData = $lender->select('id_client_owner = ' . $clientData['id_client'])[0];

        $depositResult = $request->query->get('depositResult', false);
        $depositAmount = filter_var($request->query->get('depositAmount', 0), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $depositCode   = filter_var($request->query->get('depositCode', 0), FILTER_SANITIZE_NUMBER_INT);

        $template = [
            'balance'          => $this->getUser()->getBalance(),
            'maxDepositAmount' => self::MAX_DEPOSIT_AMOUNT,
            'minDepositAmount' => self::MIN_DEPOSIT_AMOUNT,
            'client'           => $clientData,
            'lender'           => $lenderData,
            'lenderBankMotif'  => $client->getLenderPattern($clientData['id_client']),
            'depositResult'    => $depositResult,
            'depositAmount'    => $depositAmount,
            'depositCode'      => $depositCode,
            'showNavigation'   => $this->getUser()->getClientStatus() >= \clients_status::VALIDATED
        ];

        return $this->render('pages/lender_wallet/deposit.html.twig', $template);
    }

    /**
     * @Route("/retrait", name="lender_wallet_withdrawal")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     * @return Response
     */
    public function walletWithdrawalAction(Request $request)
    {
        if (\clients_status::VALIDATED > $this->getUser()->getClientStatus()) {
            return $this->redirectToRoute('lender_completeness');
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        $clientData = $client->select('id_client = ' . $this->getUser()->getClientId())[0];
        $lenderData = $lender->select('id_client_owner = ' . $clientData['id_client'])[0];

        $form = $this->createForm(LenderWithdrawalType::class);

        $template = [
            'balance'         => $this->getUser()->getBalance(),
            'client'          => $clientData,
            'lender'          => $lenderData,
            'lenderBankMotif' => $client->getLenderPattern($clientData['id_client']),
            'withdrawalForm'  => $form->createView(),
        ];

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $post = $form->getData();
                $this->handleWithdrawalPost($post, $request);
            } else {
                $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message'));
            }

            $token = $this->get('security.csrf.token_manager');
            $token->refreshToken(LenderWithdrawalType::CSRF_TOKEN_ID);

            //Redirection is needed to refresh the token in the form which is already generated above
            return $this->redirectToRoute('lender_wallet_withdrawal');
        }

        return $this->render('pages/lender_wallet/withdrawal.html.twig', $template);
    }

    private function handleWithdrawalPost($post, $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \transactions $transaction */
        $transaction = $entityManager->getRepository('transactions');
        /** @var \wallets_lines $walletLine */
        $walletLine = $entityManager->getRepository('wallets_lines');
        /** @var \bank_lines $bankLine */
        $bankLine = $entityManager->getRepository('bank_lines');
        /** @var \virements $bankTransfer */
        $bankTransfer = $entityManager->getRepository('virements');
        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        /** @var \offres_bienvenues_details $welcomeOfferDetails */
        $welcomeOfferDetails = $entityManager->getRepository('offres_bienvenues_details');
        /** @var \notifications $notification */
        $notification = $entityManager->getRepository('notifications');
        /** @var \clients_gestion_notifications $clientNotification */
        $clientNotification = $entityManager->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clientMailNotification */
        $clientMailNotification = $entityManager->getRepository('clients_gestion_mails_notif');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var ClientStatusManager $clientStatusManager */
        $clientStatusManager = $this->get('unilend.service.client_status_manager');

        if ($client->get($this->getUser()->getClientId(), 'id_client')) {
            /** @var \clients_history_actions $clientActionHistory */
            $clientActionHistory = $entityManager->getRepository('clients_history_actions');
            $serialize           = serialize(array('id_client' => $client->id_client, 'montant' => $post['amount'], 'mdp' => md5($post['password'])));
            $clientActionHistory->histo(3, 'retrait argent', $client->id_client, $serialize);

            if ($clientStatusManager->getLastClientStatus($client) < \clients_status::VALIDATED) {
                $this->redirectToRoute('lender_wallet_withdrawal');
            }

            $lender->get($client->id_client, 'id_client_owner');

            $amount = $post['amount'];

            /** @var UserPasswordEncoder $securityPasswordEncoder */
            $securityPasswordEncoder = $this->get('security.password_encoder');


            if (false === $securityPasswordEncoder->isPasswordValid($this->getUser(), $post['password'])) {
                $logger->info('Wrong password id_client=' . $client->id_client, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);
                $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message'));
            } else {
                if (false === is_numeric($amount)) {
                    $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message'));
                } elseif (empty($lender->bic) || empty($lender->iban)) {
                    $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message'));
                } else {
                    $sumOffres = $welcomeOfferDetails->sum('id_client = ' . $client->id_client . ' AND status = 0', 'montant');

                    if ($sumOffres > 0) {
                        $sumOffres = ($sumOffres / 100);
                    } else {
                        $sumOffres = 0;
                    }

                    if (($amount + $sumOffres) > $this->getUser()->getBalance() || $amount <= 0) {
                        $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message'));
                    }
                }
                $logger->info('Wrong parameters submitted, id_client=' . $client->id_client, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);
            }

            if ($this->get('session')->getFlashBag()->has('withdrawalErrors')) {
                $logger->error('Wrong parameters submitted, id_client=' . $client->id_client . ' Amount : ' . $post['amount'], ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);
            } else {
                $transaction->id_client        = $client->id_client;
                $transaction->montant          = '-' . ($amount * 100);
                $transaction->id_langue        = 'fr';
                $transaction->date_transaction = date('Y-m-d H:i:s');
                $transaction->status           = \transactions::STATUS_VALID; // on met en mode reglé pour ne plus avoir la somme sur le compte
                $transaction->ip_client        = $request->server->get('REMOTE_ADDR');
                $transaction->type_transaction = \transactions_types::TYPE_LENDER_WITHDRAWAL; // on signal que c'est un retrait
                $transaction->create();

                $walletLine->id_lender                = $lender->id_lender_account;
                $walletLine->type_financial_operation = \wallets_lines::TYPE_MONEY_SUPPLY;
                $walletLine->id_transaction           = $transaction->id_transaction;
                $walletLine->status                   = \wallets_lines::STATUS_VALID;
                $walletLine->type                     = 1;
                $walletLine->amount                   = '-' . ($amount * 100);
                $walletLine->create();

                $bankLine->id_wallet_line    = $walletLine->id_wallet_line;
                $bankLine->id_lender_account = $lender->id_lender_account;
                $bankLine->status            = 1;
                $bankLine->amount            = '-' . ($amount * 100);
                $bankLine->create();

                $bankTransfer->id_client      = $client->id_client;
                $bankTransfer->id_transaction = $transaction->id_transaction;
                $bankTransfer->montant        = $amount * 100;
                $bankTransfer->motif          = $client->getLenderPattern($client->id_client);
                $bankTransfer->type           = 1;
                $bankTransfer->status         = 0;
                $bankTransfer->create();

                $notification->type      = \notifications::TYPE_DEBIT;
                $notification->id_lender = $lender->id_lender_account;
                $notification->amount    = $amount * 100;
                $notification->create();

                $this->getUser()->setBalance($transaction->getSolde($client->id_client));

                $clientMailNotification->id_client       = $client->id_client;
                $clientMailNotification->id_notif        = \clients_gestion_type_notif::TYPE_DEBIT;
                $clientMailNotification->date_notif      = date('Y-m-d H:i:s');
                $clientMailNotification->id_notification = $notification->id_notification;
                $clientMailNotification->id_transaction  = $transaction->id_transaction;
                $clientMailNotification->create();

                if ($clientNotification->getNotif($client->id_client, 8, 'immediatement') == true) {
                    $clientMailNotification->get($clientMailNotification->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                    $clientMailNotification->immediatement = 1;
                    $clientMailNotification->update();
                    $this->sendClientWithdrawalNotification($client, $amount);
                }

                $this->sendInternalWithdrawalNotification($client, $transaction, $lender, $amount);

                $this->addFlash('withdrawalSuccess', $translator->trans('lender-wallet_withdrawal-success-message'));
            }
        }
    }

    /**
     * @Route("/alimentation/apport", name="deposit_money")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function depositMoneyAction(Request $request)
    {
        if (false === $request->isXmlHttpRequest()) {
            return $this->redirectToRoute('lender_wallet_deposit');
        }
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \transactions $transaction */
        $transaction = $entityManager->getRepository('transactions');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        /** @var CsrfTokenManagerInterface $csrfTokenManager */
        $csrfTokenManager = $this->get('security.csrf.token_manager');

        $amount    = $ficelle->cleanFormatedNumber($request->request->get('amount', ''));
        $csrfToken = $request->request->get('_csrf_token');

        $client->get($this->getUser()->getClientId());

        if (
            is_numeric($amount)
            && $amount >= self::MIN_DEPOSIT_AMOUNT
            && $amount <= self::MAX_DEPOSIT_AMOUNT
            && $csrfTokenManager->isTokenValid(new CsrfToken('deposit', $csrfToken))
        ) {
            $amount = (number_format($amount, 2, '.', '') * 100);

            /** @var \clients_history_actions $clientActionHistory */
            $clientActionHistory = $entityManager->getRepository('clients_history_actions');
            $clientActionHistory->histo(2, 'alim cb', $client->id_client, serialize(array('id_client' => $client->id_client, 'post' => $request->request->all())));

            $transaction->id_client        = $client->id_client;
            $transaction->montant          = $amount;
            $transaction->id_langue        = 'fr';
            $transaction->date_transaction = date('Y-m-d H:i:s');
            $transaction->status           = \transactions::STATUS_PENDING;
            $transaction->ip_client        = $request->server->get('REMOTE_ADDR');
            $transaction->type_transaction = \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT;
            $transaction->create();

            $paylineParameter = [];
            require_once $this->getParameter('path.payline') . 'include.php';
            /** @var \paylineSDK $payline */
            $payline                  = new \paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);
            $payline->returnURL       = $this->get('assets.packages')->getUrl('') . $this->generateUrl('wallet_payment', ['hash' => $client->hash]);
            $payline->cancelURL       = $payline->returnURL;
            $payline->notificationURL = NOTIFICATION_URL;

            $paylineParameter['payment']['amount']   = $amount;
            $paylineParameter['payment']['currency'] = ORDER_CURRENCY;
            $paylineParameter['payment']['action']   = PAYMENT_ACTION;
            $paylineParameter['payment']['mode']     = PAYMENT_MODE;

            $paylineParameter['order']['ref']      = $transaction->id_transaction;
            $paylineParameter['order']['amount']   = $amount;
            $paylineParameter['order']['currency'] = ORDER_CURRENCY;

            $paylineParameter['payment']['contractNumber'] = CONTRACT_NUMBER;
            $contracts                                     = explode(";", CONTRACT_NUMBER_LIST);
            $paylineParameter['contracts']                 = $contracts;
            $secondContracts                               = explode(";", SECOND_CONTRACT_NUMBER_LIST);
            $paylineParameter['secondContracts']           = $secondContracts;

            $logger->info('Calling Payline::doWebPayment: return URL=' . $payline->returnURL . ' Transmitted data: ' . json_encode($paylineParameter), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);

            $result = $payline->doWebPayment($paylineParameter);
            $logger->info('Payline response : ' . json_encode($result), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);

            $transaction->get($transaction->id_transaction, 'id_transaction');
            $transaction->serialize_payline = serialize($result);
            $transaction->update();

            if (isset($result) && $result['result']['code'] == '00000') {
                return $this->json(
                    ['url' =>$result['redirectURL']],
                    Response::HTTP_OK
                );
            }
        }
        return $this->json(['message' => $this->render('pages/lender_wallet/deposit_money_result.html.twig', ['code' => 0])->getContent()], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @Route("/alimentation/payment/{hash}", name="wallet_payment", requirements={"clientHash": "[0-9a-f]{32}"})
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     * @param string  $hash
     * @return JsonResponse
     */
    public function paymentAction($hash, Request $request)
    {
        require_once $this->getParameter('path.payline') . 'include.php';

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');

        $paylineParameter = [
            'token' => $request->request->get('token', $request->query->get('token'))
        ];

        if (true === $client->get($hash, 'hash') && false === empty($paylineParameter['token'])) {
            /** @var \paylineSDK $payline */
            $payline = new \paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);

            $paylineParameter['version'] = $request->request->get('version', '3');
            $response                    = $payline->getWebPaymentDetails($paylineParameter);
            $partnerId                   = $request->getSession()->get('id_partenaire', '');

            if (false === empty($response)) {
                /** @var PaylineManager $paylineManager */
                $paylineManager = $this->get('unilend.frontbundle.service.payline_manager');
                $paylineManager->setLogger($logger);

                if ($paylineManager->handlePaylineReturn($client, $response, $paylineParameter, $partnerId, PaylineManager::PAYMENT_LOCATION_LENDER_WALLET)) {
                    return $this->redirectToRoute('lender_wallet_deposit', [
                        'depositResult' => true,
                        'depositCode'   => Response::HTTP_OK,
                        'depositAmount' => bcdiv($response['payment']['amount'], 100, 2)
                    ]);
                } else {
                    $logger->warning('The payment was canceled or an error code was returned by payline. Client ID: ' . $client->id_client . ' - Payline response: ' . json_encode($response), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);
                }
            } else {
                $logger->error('Empty response from Payline, Client ID: ' . $client->id_client, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);
            }
        } else {
            $clientId = $this->getUser()->getClientId();
            $logger->error('Payline has returned wrong parameters: token or hash not found, hash: ' . $hash . ' - ' . json_encode($paylineParameter) . ' - Client ID: ' . $clientId, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $clientId]);
        }
        return $this->redirectToRoute('lender_wallet_deposit', ['depositResult' => true]);
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     *
     * @param string $route The name of the route
     * @param array $parameters An array of parameters
     * @param int $status The status code to use for the Response
     *
     * @return Response
     */
    protected function redirectToRoute($route, array $parameters = array(), $status = 302)
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    /**
     * @param \clients $client
     * @param $amount
     */
    private function sendClientWithdrawalNotification(\clients $client, $amount)
    {
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');

        $settings->get('Facebook', 'type');
        $lien_fb = $settings->value;
        $settings->get('Twitter', 'type');
        $lien_tw = $settings->value;

        $varMail = [
            'surl'            => $this->get('assets.packages')->getUrl(''),
            'url'             => $this->get('assets.packages')->getUrl(''),
            'prenom_p'        => $client->prenom,
            'fonds_retrait'   => $ficelle->formatNumber($amount),
            'solde_p'         => $ficelle->formatNumber($this->getUser()->getBalance()),
            'link_mandat'     => $this->get('assets.packages')->getUrl('') . '/images/default/mandat.jpg',
            'motif_virement'  => $client->getLenderPattern($client->id_client),
            'projets'         => $this->get('assets.packages')->getUrl('') . $this->generateUrl('home', ['type' => 'projets-a-financer']),
            'gestion_alertes' => $this->get('assets.packages')->getUrl('') . $this->generateUrl('lender_profile'),
            'lien_fb'         => $lien_fb,
            'lien_tw'         => $lien_tw
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-retrait', $varMail);
        $message->setTo($client->email);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }

    /**
     * @param \clients $client
     * @param \transactions $transaction
     * @param \lenders_accounts $lender
     * @param $amount
     */
    private function sendInternalWithdrawalNotification(\clients $client, \transactions $transaction, \lenders_accounts $lender, $amount)
    {
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
        $settings->get('Adresse notification controle fond', 'type');
        $destinataire = $settings->value;
        /** @var \loans $loans */
        $loans = $this->get('unilend.service.entity_manager')->getRepository('loans');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $transaction->get($client->id_client, 'type_transaction = ' . \transactions_types::TYPE_LENDER_SUBSCRIPTION . ' AND status = ' . \transactions::STATUS_VALID . ' AND id_client');

        $varMail = array(
            '$surl'                          => $this->get('assets.packages')->getUrl(''),
            '$url'                           => $this->get('assets.packages')->getUrl(''),
            '$idPreteur'                     => $client->id_client,
            '$nom'                           => $client->nom,
            '$prenom'                        => $client->prenom,
            '$email'                         => $client->email,
            '$dateinscription'               => date('d/m/Y', strtotime($client->added)),
            '$montantInscription'            => (false === is_null($transaction->montant)) ? $ficelle->formatNumber($transaction->montant / 100) : $ficelle->formatNumber(0),
            '$montantPreteDepuisInscription' => $ficelle->formatNumber($loans->sumPrets($lender->id_lender_account)),
            '$montantRetirePlateforme'       => $ficelle->formatNumber($amount),
            '$solde'                         => $ficelle->formatNumber($this->getUser()->getBalance())
        );
        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-retrait-de-fonds', $varMail, false);
        $message->setTo($destinataire);
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }
}
