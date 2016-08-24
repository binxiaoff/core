<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\core\Loader;

class LenderWalletController extends Controller
{
    const MAX_DEPOSIT_AMOUNT = 1000;
    const MIN_DEPOSIT_AMOUNT = 20;

    /**
     * @Route("/alimentation", name="lender_wallet")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     * @return Response
     */
    public function walletAction(Request $request)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');

        $clientData = $client->select('id_client = ' . $this->getUser()->getClientId())[0];
        $lenderData = $lender->select('id_client_owner = ' . $clientData['id_client'])[0];

        $template = [
            'balance'         => $this->getUser()->getBalance(),
            'maxDepositAmount'=> self::MAX_DEPOSIT_AMOUNT,
            'minDepositAmount'=> self::MIN_DEPOSIT_AMOUNT,
            'client'          => $clientData,
            'lender'          => $lenderData,
            'lenderBankMotif' => $client->getLenderPattern($clientData['id_client']),
            'depositResult'   => $request->query->get('depositResult', false),
            'depositAmount'   => $request->query->get('depositAmount', 0),
            'depositCode'     => $request->query->get('depositCode', 0),
        ];

        $lenderSubscriptionStep3Form  = $this->get('session')->get('subscriptionStep3WalletData');
        $this->get('session')->remove('subscriptionStep3WalletData');
        if ($client->get($lenderSubscriptionStep3Form['clientId'], 'id_client') && $client->id_client == $this->getUser()->getClientId()) {
            $template['formData'] = $lenderSubscriptionStep3Form;
        }

        return $this->render(':frontbundle/pages/lender_wallet:wallet_layout.html.twig', $template);
    }

    /**
     * @Route("/alimentation/retrait", name="withdraw_money")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function withdrawMoneyAction(Request $request)
    {
        if (false === $request->isXmlHttpRequest()) {
            return $this->redirectToRoute('lender_wallet');
        }

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
        /** @var \clients_status $clientStatus */
        $clientStatus = $entityManager->getRepository('clients_status');
        /** @var \offres_bienvenues_details $welcomeOfferDetails */
        $welcomeOfferDetails = $entityManager->getRepository('offres_bienvenues_details');
        /** @var \notifications $notification */
        $notification = $entityManager->getRepository('notifications');
        /** @var \clients_gestion_notifications $clientNotification */
        $clientNotification = $entityManager->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clientMailNotification */
        $clientMailNotification = $entityManager->getRepository('clients_gestion_mails_notif');
        /** @var \clients_adresses $clientAddress */
        $clientAddress = $entityManager->getRepository('clients_adresses');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');

        if ($client->get($this->getUser()->getClientId(), 'id_client') && false === empty($request->request->get('mdp')) && false === empty($request->request->get('montant'))) {
            /** @var \clients_history_actions $clientActionHistory */
            $clientActionHistory = $entityManager->getRepository('clients_history_actions');
            $serialize           = serialize(array('id_client' => $client->id_client, 'montant' => $request->request->get('montant'), 'mdp' => md5($request->request->get('mdp'))));
            $clientActionHistory->histo(3, 'retrait argent', $client->id_client, $serialize);

            $clientStatus->getLastStatut($client->id_client);

            if ($clientStatus->status < \clients_status::VALIDATED) {
                $this->redirectToRoute('lender_wallet');
            }
            $lender->get($client->id_client, 'id_client_owner');

            $code    = Response::HTTP_OK;
            $montant = str_replace(',', '.', $request->request->get('montant'));

            if (md5($request->request->get('mdp')) !== $client->password && false === password_verify($request->request->get('mdp'), $client->password)) {
                $logger->info('Wrong password id_client=' . $client->id_client, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);
                $code = Response::HTTP_UNAUTHORIZED;
            } else {
                if (false === is_numeric($montant)) {
                    $code = Response::HTTP_BAD_REQUEST;
                } elseif (empty($lender->bic) || empty($lender->iban)) {
                    $code = $code = Response::HTTP_INTERNAL_SERVER_ERROR;
                } else {
                    $sumOffres = $welcomeOfferDetails->sum('id_client = ' . $client->id_client . ' AND status = 0', 'montant');

                    if ($sumOffres > 0) {
                        $sumOffres = ($sumOffres / 100);
                    } else {
                        $sumOffres = 0;
                    }

                    if (($montant + $sumOffres) > $this->getUser()->getBalance() || $montant <= 0) {
                        $code = Response::HTTP_BAD_REQUEST;
                    }
                }
                $logger->info('Wrong parameters submitted, id_client=' . $client->id_client, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);
            }

            if ($code === Response::HTTP_OK) {
                /** @var \ficelle $ficelle */
                $ficelle = Loader::loadLib('ficelle');

                $clientAddress->get($client->id_client, 'id_client');

                $transaction->id_client        = $client->id_client;
                $transaction->montant          = '-' . ($montant * 100);
                $transaction->id_langue        = 'fr';
                $transaction->date_transaction = date('Y-m-d H:i:s');
                $transaction->status           = '1'; // on met en mode reglé pour ne plus avoir la somme sur le compte
                $transaction->etat             = '1';
                $transaction->ip_client        = $request->server->get('REMOTE_ADDR');
                $transaction->civilite_fac     = $client->civilite;
                $transaction->nom_fac          = $client->nom;
                $transaction->prenom_fac       = $client->prenom;

                if ($client->type == 2) {
                    /** @var \companies $company */
                    $company = $entityManager->getRepository('companies');

                    $transaction->societe_fac = $company->name;
                }
                $transaction->adresse1_fac     = $clientAddress->adresse1;
                $transaction->cp_fac           = $clientAddress->cp;
                $transaction->ville_fac        = $clientAddress->ville;
                $transaction->id_pays_fac      = $clientAddress->id_pays;
                $transaction->type_transaction = \transactions_types::TYPE_LENDER_WITHDRAWAL; // on signal que c'est un retrait
                $transaction->transaction      = 1; // transaction physique
                $transaction->create();

                $walletLine->id_lender                = $lender->id_lender_account;
                $walletLine->type_financial_operation = 30; // Inscription preteur
                $walletLine->id_transaction           = $transaction->id_transaction;
                $walletLine->status                   = \wallets_lines::STATUS_VALID;
                $walletLine->type                     = 1;
                $walletLine->amount                   = '-' . ($montant * 100);
                $walletLine->create();

                // Transaction physique donc on enregistre aussi dans la bank lines
                $bankLine->id_wallet_line    = $walletLine->id_wallet_line;
                $bankLine->id_lender_account = $lender->id_lender_account;
                $bankLine->status            = 1;
                $bankLine->amount            = '-' . ($montant * 100);
                $bankLine->create();

                $bankTransfer->id_client      = $client->id_client;
                $bankTransfer->id_transaction = $transaction->id_transaction;
                $bankTransfer->montant        = $montant * 100;
                $bankTransfer->motif          = $client->getLenderPattern($client->id_client);
                $bankTransfer->type           = 1; // preteur
                $bankTransfer->status         = 0;
                $bankTransfer->create();

                $notification->type      = \notifications::TYPE_DEBIT;
                $notification->id_lender = $lender->id_lender_account;
                $notification->amount    = $montant * 100;
                $notification->create();

                $this->getUser()->setBalance($transaction->getSolde($client->id_client));

                $clientMailNotification->id_client       = $client->id_client;
                $clientMailNotification->id_notif        = \clients_gestion_type_notif::TYPE_DEBIT;
                $clientMailNotification->date_notif      = date('Y-m-d H:i:s');
                $clientMailNotification->id_notification = $notification->id_notification;
                $clientMailNotification->id_transaction  = $transaction->id_transaction;
                $clientMailNotification->create();

                /** @var \settings $oSettings */
                $oSettings = $entityManager->getRepository('settings');

                if ($clientNotification->getNotif($client->id_client, 8, 'immediatement') == true) {
                    $clientMailNotification->get($clientMailNotification->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                    $clientMailNotification->immediatement = 1;
                    $clientMailNotification->update();

                    $oSettings->get('Facebook', 'type');
                    $lien_fb = $oSettings->value;
                    $oSettings->get('Twitter', 'type');
                    $lien_tw = $oSettings->value;

                    $varMail = array(
                        'surl'            => $this->get('assets.packages')->getUrl(''),
                        'url'             => $this->get('assets.packages')->getUrl(''),
                        'prenom_p'        => $client->prenom,
                        'fonds_retrait'   => $ficelle->formatNumber($montant),
                        'solde_p'         => $ficelle->formatNumber($this->getUser()->getBalance()),
                        'link_mandat'     => $this->get('assets.packages')->getUrl('') . '/images/default/mandat.jpg',
                        'motif_virement'  => $client->getLenderPattern($client->id_client),
                        'projets'         => $this->get('assets.packages')->getUrl('') . $this->generateUrl('home', ['type' => 'projets-a-financer']),
                        'gestion_alertes' => $this->get('assets.packages')->getUrl('') . $this->generateUrl('lender_profile'),
                        'lien_fb'         => $lien_fb,
                        'lien_tw'         => $lien_tw
                    );

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-retrait', $varMail);
                    $message->setTo($client->email);
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                }

                //******************************************//
                //*** ENVOI DU MAIL NOTIFICATION RETRAIT ***//
                //******************************************//
                $oSettings->get('Adresse notification controle fond', 'type');
                $destinataire = $oSettings->value;
                /** @var \loans $loans */
                $loans = $entityManager->getRepository('loans');

                // on recup la somme versé a l'inscription si y en a 1
                $transaction->get($client->id_client, 'type_transaction = 1 AND status = 1 AND etat = 1 AND transaction = 1 AND id_client');

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
                    '$montantRetirePlateforme'       => $ficelle->formatNumber($montant),
                    '$solde'                         => $ficelle->formatNumber($this->getUser()->getBalance())
                );
                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-retrait-de-fonds', $varMail, false);
                $message->setTo($destinataire);
                $mailer = $this->get('mailer');
                $mailer->send($message);
                $logger->debug('Withdraw money successfully done, id_client=' . $client->id_client . '. Withdraw amount='.$request->request->get('montant'), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);
            }
        } else {
            $code = Response::HTTP_BAD_REQUEST;
            $logger->error('Wrong parameters submitted, id_client=' . $client->id_client . ' Request parameters : ' . json_encode($request->request->all()), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);
        }
        return $this->json(
            [
                'balance' => $this->getUser()->getBalance(),
                'message' => $this->render(':frontbundle/pages/lender_wallet:withdraw_money_result.html.twig', ['code' => $code])->getContent()
            ],
            $code
        );
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
            return $this->redirectToRoute('lender_wallet');
        }
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \transactions $transaction */
        $transaction = $entityManager->getRepository('transactions');
        /** @var \clients_adresses $clientAddress */
        $clientAddress = $entityManager->getRepository('clients_adresses');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');

        $amount = $ficelle->cleanFormatedNumber($request->request->get('amount'));

        $client->get($this->getUser()->getClientId());

        if (is_numeric($amount) && $amount >= self::MIN_DEPOSIT_AMOUNT && $amount <= self::MAX_DEPOSIT_AMOUNT) {
            $amount = (number_format($amount, 2, '.', '') * 100);

            /** @var \clients_history_actions $clientActionHistory */
            $clientActionHistory = $entityManager->getRepository('clients_history_actions');

            $clientActionHistory->histo(2, 'alim cb', $client->id_client, serialize(array('id_client' => $client->id_client, 'post' => $request->request->all())));

            $transaction->id_client        = $client->id_client;
            $transaction->montant          = $amount;
            $transaction->id_langue        = 'fr';
            $transaction->date_transaction = date('Y-m-d H:i:s');
            $transaction->status           = '0';
            $transaction->etat             = '0';
            $transaction->ip_client        = $request->server->get('REMOTE_ADDR');
            $transaction->civilite_fac     = $client->civilite;
            $transaction->nom_fac          = $client->nom;
            $transaction->prenom_fac       = $client->prenom;
            if ($client->type == 2) {
                /** @var \companies $company */
                $company = $entityManager->getRepository('companies');

                $transaction->societe_fac = $company->name;
            }
            $clientAddress->get($client->id_client, 'id_client');
            $transaction->adresse1_fac     = $clientAddress->adresse1;
            $transaction->cp_fac           = $clientAddress->cp;
            $transaction->ville_fac        = $clientAddress->ville;
            $transaction->id_pays_fac      = $clientAddress->id_pays;
            $transaction->type_transaction = \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT;
            $transaction->transaction      = 1;
            $transaction->id_transaction   = $transaction->create();

            $array = [];
            require_once $this->getParameter('path.payline') . 'include.php';
            /** @var \paylineSDK $payline */
            $payline                  = new \paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);
            $payline->returnURL       = $this->get('assets.packages')->getUrl('') . $this->generateUrl('wallet_payment', ['hash' => $client->hash]);
            $payline->cancelURL       = $payline->returnURL;
            $payline->notificationURL = NOTIFICATION_URL;

            $array['payment']['amount']   = $amount;
            $array['payment']['currency'] = ORDER_CURRENCY;
            $array['payment']['action']   = PAYMENT_ACTION;
            $array['payment']['mode']     = PAYMENT_MODE;

            $array['order']['ref']      = $transaction->id_transaction;
            $array['order']['amount']   = $amount;
            $array['order']['currency'] = ORDER_CURRENCY;

            $array['payment']['contractNumber'] = CONTRACT_NUMBER;
            $contracts                          = explode(";", CONTRACT_NUMBER_LIST);
            $array['contracts']                 = $contracts;
            $secondContracts                    = explode(";", SECOND_CONTRACT_NUMBER_LIST);
            $array['secondContracts']           = $secondContracts;

            $logger->info('Calling Payline::doWebPayment: return URL=' . $payline->returnURL . ' Transmetted data: ' . json_encode($array), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);

            $result = $payline->doWebPayment($array);
            $logger->info('Payline response : ' . json_encode(['$result']), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);

            $transaction->get($transaction->id_transaction, 'id_transaction');
            $transaction->serialize_payline = serialize($result);
            $transaction->update();

            if (isset($result)) {

                if ($result['result']['code'] == '00000') {
                    return $this->json(
                        ['url' =>$result['redirectURL']],
                        Response::HTTP_OK
                    );
                } elseif (isset($result)) {
                    mail('alertesit@unilend.fr', 'unilend erreur payline', 'alimentation preteur (client : ' . $client->id_client . ') | ERROR : ' . $result['result']['code'] . ' ' . $result['result']['longMessage']);
                }
            }
        }
        return $this->json(
            [
                'message' => $this->render(':frontbundle/pages/lender_wallet:deposit_money_result.html.twig', ['code' => 0])->getContent()
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    /**
     * @Route("/alimentation/payment/{hash}", name="wallet_payment")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     * @param $hash
     * @return JsonResponse
     */
    public function paymentAction(Request $request, $hash)
    {
        require_once $this->getParameter('path.payline') . 'include.php';

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
        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        /** @var \notifications $notification */
        $notification = $entityManager->getRepository('notifications');
        /** @var \clients_gestion_notifications $clientNotification */
        $clientNotification = $entityManager->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clientMailNotification */
        $clientMailNotification = $entityManager->getRepository('clients_gestion_mails_notif');
        /** @var \backpayline $backPayline */
        $backPayline = $entityManager->getRepository('backpayline');
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');

        if ($client->get($hash, 'hash')) {
            $array = array();
            /** @var \paylineSDK $payline */
            $payline = new \paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);

            $array['token'] = $request->request->get('token', $request->query->get('token'));

            if (true === empty($array['token'])) {
                $logger->error('Payline token not found, id_client=' . $client->id_client, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->id_client]);
                return $this->redirectToRoute('lender_wallet', ['depositResult' => true]);
            }
            $array['version'] = $request->request->get('version', '3');

            $response = $payline->getWebPaymentDetails($array);

            if (false === empty($response)) {
                $backPayline->code      = $response['result']['code'];
                $backPayline->token     = $array['token'];
                $backPayline->id        = $response['transaction']['id'];
                $backPayline->date      = $response['transaction']['date'];
                $backPayline->amount    = $response['payment']['amount'];
                $backPayline->serialize = serialize($response);
                $backPayline->create();

                // Paiement approuvé
                if ($response['result']['code'] == '00000') {
                    if ($transaction->get($response['order']['ref'], 'status = 0 AND etat = 0 AND id_transaction')) {
                        $transaction->id_backpayline   = $backPayline->id_backpayline;
                        $transaction->montant          = $response['payment']['amount'];
                        $transaction->id_langue        = 'fr';
                        $transaction->date_transaction = date('Y-m-d H:i:s');
                        $transaction->status           = '1';
                        $transaction->etat             = '1';
                        /** @todo id_partenaire is set from db table : partenaires.id_partenaire */
                        $transaction->id_partenaire    = $request->getSession()->get('id_partenaire' ,'');
                        $transaction->type_paiement    = ($response['extendedCard']['type'] == 'VISA' ? '0' : ($response['extendedCard']['type'] == 'MASTERCARD' ? '3' : ''));
                        $transaction->update();

                        $lender->get($client->id_client, 'id_client_owner');
                        $lender->status = 1;
                        $lender->update();

                        $walletLine->id_lender                = $lender->id_lender_account;
                        $walletLine->type_financial_operation = 30; // Inscription preteur
                        $walletLine->id_transaction           = $transaction->id_transaction;
                        $walletLine->status                   = 1;
                        $walletLine->type                     = 1;
                        $walletLine->amount                   = $response['payment']['amount'];
                        $walletLine->create();

                        // Transaction physique donc on enregistre aussi dans la bank lines
                        $bankLine->id_wallet_line    = $walletLine->id_wallet_line;
                        $bankLine->id_lender_account = $lender->id_lender_account;
                        $bankLine->status            = 1;
                        $bankLine->amount            = $response['payment']['amount'];
                        $bankLine->create();

                        $notification->type      = \notifications::TYPE_CREDIT_CARD_CREDIT;
                        $notification->id_lender = $lender->id_lender_account;
                        $notification->amount    = $response['payment']['amount'];
                        $notification->create();

                        $clientMailNotification->id_client       = $lender->id_client_owner;
                        $clientMailNotification->id_notif        = \clients_gestion_type_notif::TYPE_CREDIT_CARD_CREDIT;
                        $clientMailNotification->date_notif      = date('Y-m-d H:i:s');
                        $clientMailNotification->id_notification = $notification->id_notification;
                        $clientMailNotification->id_transaction  = $transaction->id_transaction;
                        $clientMailNotification->create();

                        if ($client->etape_inscription_preteur < 3) {
                            $client->etape_inscription_preteur = 3;
                            $client->update();
                        }

                        if ($clientNotification->getNotif($client->id_client, 7, 'immediatement') == true) {
                            $clientMailNotification->get($clientMailNotification->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                            $clientMailNotification->immediatement = 1;
                            $clientMailNotification->update();

                            /** @var \settings $oSettings */
                            $oSettings = $entityManager->getRepository('settings');
                            $oSettings->get('Facebook', 'type');
                            $lien_fb = $oSettings->value;

                            $oSettings->get('Twitter', 'type');
                            $lien_tw = $oSettings->value;

                            $varMail = array(
                                'surl'            => $this->get('assets.packages')->getUrl(''),
                                'url'             => $this->get('assets.packages')->getUrl(''),
                                'prenom_p'        => $client->prenom,
                                'fonds_depot'     => bcdiv($response['payment']['amount'], 100, 2),
                                'solde_p'         => bcadd($this->getUser()->getBalance(), bcdiv($response['payment']['amount'], 100, 2), 2),
                                'link_mandat'     => $this->get('assets.packages')->getUrl('') . '/images/default/mandat.jpg',
                                'motif_virement'  => $client->getLenderPattern($client->id_client),
                                'projets'         => $this->get('assets.packages')->getUrl('') . $this->generateUrl('home', ['type' => 'projets-a-financer']),
                                'gestion_alertes' => $this->get('assets.packages')->getUrl('') . $this->generateUrl('lender_profile'),
                                'lien_fb'         => $lien_fb,
                                'lien_tw'         => $lien_tw
                            );

                            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-alimentation-cb', $varMail);
                            $message->setTo($client->email);
                            $mailer = $this->get('mailer');
                            $mailer->send($message);
                        }
                    }
                    return $this->redirectToRoute('lender_wallet', ['depositResult' => true, 'depositCode' => Response::HTTP_OK, 'depositAmount' => bcdiv($response['payment']['amount'], 100, 2)]);

                } elseif ($response['result']['code'] == '02319') { // Payment cancelled
                    $transaction->get($response['order']['ref'], 'id_transaction');
                    $transaction->id_backpayline = $backPayline->id_backpayline;
                    $transaction->statut         = '0';
                    $transaction->etat           = '3';
                    $transaction->update();

                } else { // Payment error
                    mail('alertesit@unilend.fr', 'unilend payline erreur', 'erreur sur page payment alimentation preteur (client : ' . $client->id_client . ') : ' . serialize($response));
                }
                return $this->redirectToRoute('lender_wallet', ['depositResult' => true]);
            }
        }
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
}
