<?php

namespace Unilend\Bundle\FrontBundle\Service;


use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientManager;
use Unilend\Bundle\CoreBusinessBundle\Service\OperationManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class PaylineManager
{
    const PAYMENT_LOCATION_LENDER_WALLET       = 2;

    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var TemplateMessageProvider
     */
    private $messageProvider;
    /**
     * @var \Swift_Mailer
     */
    private $mailer;
    /**
     * @var ClientManager
     */
    private $clientManager;
    /**
     * @var OperationManager
     */
    private $operationManager;
    /**
     * @var Router
     */
    private $router;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var string
     */
    private $sUrl;

    public function __construct(
        EntityManagerSimulator $entityManager,
        EntityManager $em,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        ClientManager $clientManager,
        OperationManager $operationManager,
        RouterInterface $router,
        Packages $assetsPackages,
        $paylineFile
    ) {
        require_once $paylineFile;

        $this->entityManager    = $entityManager;
        $this->em               = $em;
        $this->messageProvider  = $messageProvider;
        $this->mailer           = $mailer;
        $this->clientManager    = $clientManager;
        $this->operationManager = $operationManager;
        $this->router           = $router;
        $this->sUrl             = $assetsPackages->getUrl('');
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function pay($amount, Wallet $wallet, $redirectUrl, $cancelUrl)
    {
        $amountInCent = number_format($amount, 2, '.', '') * 100;

        $backPayline = new Backpayline();
        $backPayline->setWallet($wallet);
        $backPayline->setAmount($amountInCent);
        $this->em->persist($backPayline);
        $this->em->flush();
        /** @var \paylineSDK $payline */
        $payline                  = new \paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);
        $payline->returnURL       = $redirectUrl;
        $payline->cancelURL       = $cancelUrl;
        $payline->notificationURL = NOTIFICATION_URL;

        $paylineParameter = [
            'payment'         => [
                'amount'         => $amountInCent,
                'currency'       => ORDER_CURRENCY,
                'action'         => PAYMENT_ACTION,
                'mode'           => PAYMENT_MODE,
                'contractNumber' => CONTRACT_NUMBER,
            ],
            'order'           => [
                'ref'      => $backPayline->getIdBackpayline(),
                'amount'   => $amount,
                'currency' => ORDER_CURRENCY,
            ],
            'contracts'       => explode(";", CONTRACT_NUMBER_LIST),
            'secondContracts' => explode(";", SECOND_CONTRACT_NUMBER_LIST),
        ];

        $this->logger->debug('Calling Payline::doWebPayment: return URL=' . $payline->returnURL . ' Transmetted data: ' . json_encode($paylineParameter));

        $result = $payline->doWebPayment($paylineParameter);

        $this->logger->debug('Payline doWebPayment response : ' . json_encode($result));

        $backPayline->setSerializeDoPayment(json_encode($result));

        $this->em->flush();

        if (false === isset($result['result']['code']) || $result['result']['code'] !== Backpayline::CODE_TRANSACTION_APPROVED) {
            $this->handleError('alimentation preteur (wallet : ' . $wallet->getId() . ') | ERROR : ' . $result['result']['code'] . ' ' . $result['result']['longMessage']);

            return false;
        }

        return $result['redirectURL'];
    }

    /**
     * @param $token
     * @param $version
     *
     * @return bool
     */
    public function handlePaylineReturn($token, $version)
    {
        /** @var \paylineSDK $payline */
        $payline  = new \paylineSDK(MERCHANT_ID, ACCESS_KEY, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, PRODUCTION);

        $this->logger->debug('Calling Payline::getWebPaymentDetails: return token=' . $token. ' version: ' . $version);

        $response = $payline->getWebPaymentDetails(['token' => $token, 'version' => $version]);

        $this->logger->debug('Payline getWebPaymentDetails response : ' . json_encode($response));

        if (empty($response)) {
            return false;
        }

        $backPayline = $this->em->getRepository('UnilendCoreBusinessBundle:Backpayline')->findOneBy(['idBackpayline' => $response['order']['ref']]);

        if ($backPayline instanceof Backpayline) {
            $backPayline->setId($response['transaction']['id']);
            $backPayline->setDate($response['transaction']['date']);
            $backPayline->setToken($token);
            $backPayline->setSerialize(serialize($response));
            $backPayline->setCode($response['result']['code']);

            $this->em->flush();

            if ($backPayline->getAmount() != $response['payment']['amount']) {
                $errorMsg = 'Payline amount for wallet id : '
                    . $backPayline->getWallet()->getId()
                    . 'is not the same between the response (' . $response['payment']['amount'] . ') and database (' . $backPayline->getAmount() . ') ';
                $this->handleError($errorMsg);

                return false;
            }

            if ($response['result']['code'] == Backpayline::CODE_TRANSACTION_APPROVED) {
                $amount = round(bcdiv($backPayline->getAmount(), 100, 4), 2);
                $this->operationManager->provisionLenderWallet($amount, $backPayline->getWallet(), $backPayline);
                $this->notifyClientAboutMoneyTransfer($backPayline);
                $this->notifyAboutPaylineApprovement($backPayline);
            } elseif ($response['result']['code'] !== Backpayline::CODE_TRANSACTION_CANCELLED) { // Payment error
                $this->handleError('erreur sur page payment alimentation preteur (wallet id : ' . $backPayline->getWallet()->getId() . ') : ' . serialize($response));

                return false;
            }
        } else {
            $errorMsg = 'Payline order : ' . $response['order']['ref'] . ' cannot be found';
            $this->handleError($errorMsg);

            return false;
        }

        return $response['payment']['amount'];
    }

    private function notifyAboutPaylineApprovement(Backpayline $backPayline)
    {
        $settings = $this->entityManager->getRepository('settings');
        $settings->get('DebugMailFrom', 'type');
        $debugEmail = $settings->value;
        $settings->get('DebugMailIt', 'type');
        $sDestinatairesDebug = $settings->value;
        $sHeadersDebug  = 'MIME-Version: 1.0' . "\r\n";
        $sHeadersDebug .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $sHeadersDebug .= 'From: ' . $debugEmail . "\r\n";
        $subject = '[Alerte] BACK PAYLINE Transaction approved';

        $message = '<html>
                        <head>
                          <title>[Alerte] BACK PAYLINE Transaction approved</title>
                        </head>
                        <body>
                          <h3>[Alerte] BACK PAYLINE Transaction approved</h3>
                          <p>Un payement payline accepet&eacute; n\'a pas &eacute;t&eacute; mis &agrave; jour dans la BDD Unilend.</p>
                          <table>
                            <tr>
                              <th>Id wallet : </th><td>' . $backPayline->getWallet()->getId() . '</td>
                            </tr>
                            <tr>
                              <th>montant : </th><td>' . $backPayline->getAmount() / 100 . '</td>
                            </tr>
                            <tr>
                              <th>serialize donnees payline : </th><td>' . $backPayline->getSerialize() . '</td>
                            </tr>
                          </table>
                        </body>
                    </html>';

        mail($sDestinatairesDebug, $subject, $message, $sHeadersDebug);
    }

    private function notifyClientAboutMoneyTransfer(Backpayline $backPayline)
    {
        /** @var \notifications $notification */
        $notification = $this->entityManager->getRepository('notifications');
        /** @var \clients_gestion_notifications $clientNotification */
        $clientNotification = $this->entityManager->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clientMailNotification */
        $clientMailNotification = $this->entityManager->getRepository('clients_gestion_mails_notif');
        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->entityManager->getRepository('lenders_accounts');

        $amount = round(bcdiv($backPayline->getAmount(), 100, 4), 2);

        $client = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->find($backPayline->getWallet()->getIdClient()->getIdClient());
        $lenderAccount->get($client->getIdClient(), 'id_client_owner');
        $transaction->get($backPayline->getIdBackpayline(), 'type_transaction = ' . \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT . ' AND id_backpayline');

        $notification->type      = \notifications::TYPE_CREDIT_CARD_CREDIT;
        $notification->id_lender = $lenderAccount->id_lender_account;
        $notification->amount    = $backPayline->getAmount();
        $notification->create();

        $clientMailNotification->id_client       = $lenderAccount->id_client_owner;
        $clientMailNotification->id_notif        = \clients_gestion_type_notif::TYPE_CREDIT_CARD_CREDIT;
        $clientMailNotification->date_notif      = date('Y-m-d H:i:s');
        $clientMailNotification->id_notification = $notification->id_notification;
        $clientMailNotification->id_transaction  = $transaction->id_transaction;
        $clientMailNotification->create();

        if ($clientNotification->getNotif($client->getIdClient(), 7, 'immediatement') == true) {
            $clientMailNotification->get($clientMailNotification->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
            $clientMailNotification->immediatement = 1;
            $clientMailNotification->update();

            /** @var \settings $settings */
            $settings = $this->entityManager->getRepository('settings');
            $settings->get('Facebook', 'type');
            $lien_fb = $settings->value;

            $settings->get('Twitter', 'type');
            $lien_tw = $settings->value;

            $varMail = [
                'surl'            => $this->sUrl,
                'url'             => $this->router->getContext()->getBaseUrl(),
                'prenom_p'        => $client->getPrenom(),
                'fonds_depot'     => $amount,
                'solde_p'         => $this->clientManager->getClientBalance($client),
                'link_mandat'     => $this->sUrl . '/images/default/mandat.jpg',
                'motif_virement'  => $client->getLenderPattern($client->getIdClient()),
                'projets'         => $this->router->generate('home', ['type' => 'projets-a-financer']),
                'gestion_alertes' => $this->router->generate('lender_profile'),
                'lien_fb'         => $lien_fb,
                'lien_tw'         => $lien_tw
            ];

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->messageProvider->newMessage('preteur-alimentation-cb', $varMail);
            $message->setTo($client->getEmail());
            $this->mailer->send($message);
        }
    }

    private function handleError($errorMsg)
    {
        $this->logger->error($errorMsg);
        mail('alertesit@unilend.fr', 'unilend erreur payline', 'alimentation preteur | ERROR : ' . $errorMsg);
        return false;
    }
}
