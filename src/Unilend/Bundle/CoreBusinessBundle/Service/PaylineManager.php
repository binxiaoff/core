<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class PaylineManager
{
    const PAYMENT_LOCATION_LENDER_WALLET = 2;

    /**
     * @var EntityManagerSimulator
     */
    private $entityManagerSimulator;
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
    /**
     * @var boolean
     */
    private $isProduction;
    /**
     * @var string
     */
    private $merchantId;
    /**
     * @var string
     */
    private $accessKey;

    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        ClientManager $clientManager,
        OperationManager $operationManager,
        RouterInterface $router,
        Packages $assetsPackages,
        $paylineFile,
        $environment,
        $merchantId,
        $accessKey
    ) {
        require_once $paylineFile;

        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->messageProvider        = $messageProvider;
        $this->mailer                 = $mailer;
        $this->clientManager          = $clientManager;
        $this->operationManager       = $operationManager;
        $this->router                 = $router;
        $this->sUrl                   = $assetsPackages->getUrl('');
        $this->isProduction           = $environment === 'prod' ? true : false;
        $this->merchantId             = (string) $merchantId; // PaylineSDK need string
        $this->accessKey              = $accessKey;
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
        $this->entityManager->persist($backPayline);
        $this->entityManager->flush($backPayline);
        /** @var \paylineSDK $payline */
        $payline                  = new \paylineSDK($this->merchantId, $this->accessKey, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, $this->isProduction);
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
                'amount'   => $amountInCent,
                'currency' => ORDER_CURRENCY,
            ],
            'contracts'       => explode(";", CONTRACT_NUMBER_LIST),
            'secondContracts' => explode(";", SECOND_CONTRACT_NUMBER_LIST),
        ];

        $this->logger->debug('Calling Payline::doWebPayment: return URL=' . $payline->returnURL . ' Transmetted data: ' . json_encode($paylineParameter));

        $result = $payline->doWebPayment($paylineParameter);

        $this->logger->debug('Payline doWebPayment response : ' . json_encode($result));

        $backPayline->setSerializeDoPayment(serialize($result));

        $this->entityManager->flush($backPayline);

        if (false === isset($result['result']['code']) || $result['result']['code'] !== Backpayline::CODE_TRANSACTION_APPROVED) {
            $this->logger->error('alimentation preteur (wallet : ' . $wallet->getId() . ') | ERROR : ' . $result['result']['code'] . ' ' . $result['result']['longMessage']);
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
        $payline = new \paylineSDK($this->merchantId, $this->accessKey, PROXY_HOST, PROXY_PORT, PROXY_LOGIN, PROXY_PASSWORD, $this->isProduction);

        $this->logger->debug('Calling Payline::getWebPaymentDetails: return token=' . $token . ' version: ' . $version);

        $response = $payline->getWebPaymentDetails(['token' => $token, 'version' => $version]);

        $this->logger->debug('Payline getWebPaymentDetails response : ' . json_encode($response));

        if (empty($response)) {
            return false;
        }

        $backPayline = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Backpayline')->findOneBy(['idBackpayline' => $response['order']['ref']]);

        if ($backPayline instanceof Backpayline) {
            $backPayline->setId($response['transaction']['id']);
            $backPayline->setDate($response['transaction']['date']);
            $backPayline->setToken($token);
            $backPayline->setSerialize(serialize($response));
            $backPayline->setCode($response['result']['code']);

            if (isset($response['card']) && isset($response['card']['number'])) {
                $backPayline->setCardNumber($response['card']['number']);
            }
            $this->entityManager->flush($backPayline);

            if ($response['result']['code'] === Backpayline::CODE_TRANSACTION_APPROVED && $backPayline->getAmount() != $response['payment']['amount']) {
                $errorMsg = 'Payline amount for wallet id : '
                    . $backPayline->getWallet()->getId()
                    . ' is not the same between the response (' . $response['payment']['amount'] . ') and database (' . $backPayline->getAmount() . ') ';
                $this->logger->error($errorMsg, ['class' => __CLASS__, 'function' => __FUNCTION__]);

                return false;
            }

            if ($response['result']['code'] === Backpayline::CODE_TRANSACTION_APPROVED) {
                $this->operationManager->provisionLenderWallet($backPayline->getWallet(), $backPayline);
                $this->notifyClientAboutMoneyTransfer($backPayline);
            // See codes https://support.payline.com/hc/fr/article_attachments/206064778/PAYLINE-GUIDE-Descriptif_des_appels_webservices-FR-v3.A.pdf
            } elseif (in_array($response['result']['code'], ['01109', '01110', '01114', '01115', '01122', '01123', '01181', '01182', '01197', '01198', '01199', '01207', '01904', '01907', '01909', '01912', '01913', '01914', '01940', '01941', '01942', '01943', '02101', '02102', '02103', '02109', '02201', '02202', '02301', '02303', '02304', '02305', '02307', '02308', '02309', '02310', '02311', '02312', '02313', '02314', '02315', '02316', '02317', '02318', '02320', '02321', '02322'])) {
                $this->logger->error('Lender provision error (wallet id : ' . $backPayline->getWallet()->getId() . ') : ' . serialize($response));

                return false;
            }
        } else {
            $errorMsg = 'Payline order : ' . $response['order']['ref'] . ' cannot be found';
            $this->logger->error($errorMsg);

            return false;
        }

        return $response['payment']['amount'];
    }

    private function notifyClientAboutMoneyTransfer(Backpayline $backPayline)
    {
        /** @var \notifications $notification */
        $notification = $this->entityManagerSimulator->getRepository('notifications');
        /** @var \clients_gestion_notifications $clientNotification */
        $clientNotification = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clientMailNotification */
        $clientMailNotification = $this->entityManagerSimulator->getRepository('clients_gestion_mails_notif');
        /** @var \transactions $transaction */
        $transaction = $this->entityManagerSimulator->getRepository('transactions');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->entityManagerSimulator->getRepository('lenders_accounts');
        /** @var \clients $client */
        $client = $this->entityManagerSimulator->getRepository('clients');

        $amount = round(bcdiv($backPayline->getAmount(), 100, 4), 2);

        $client->get($backPayline->getWallet()->getIdClient()->getIdClient());
        $lenderAccount->get($client->id_client, 'id_client_owner');
        $transaction->get($backPayline->getIdBackpayline(), 'type_transaction = ' . \transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT . ' AND id_backpayline');

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

        if ($clientNotification->getNotif($client->id_client, 7, 'immediatement') == true) {
            $clientMailNotification->get($clientMailNotification->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
            $clientMailNotification->immediatement = 1;
            $clientMailNotification->update();

            /** @var \settings $settings */
            $settings = $this->entityManagerSimulator->getRepository('settings');
            $settings->get('Facebook', 'type');
            $lien_fb = $settings->value;

            $settings->get('Twitter', 'type');
            $lien_tw = $settings->value;

            $varMail = [
                'surl'            => $this->sUrl,
                'url'             => $this->router->getContext()->getBaseUrl(),
                'prenom_p'        => $client->prenom,
                'fonds_depot'     => number_format($amount,2, ',', ' '),
                'solde_p'         => number_format($backPayline->getWallet()->getAvailableBalance(), 2, ',', ' '),
                'link_mandat'     => $this->sUrl . '/images/default/mandat.jpg',
                'motif_virement'  => $backPayline->getWallet()->getWireTransferPattern(),
                'projets'         => $this->router->generate('home', ['type' => 'projets-a-financer']),
                'gestion_alertes' => $this->router->generate('lender_profile'),
                'lien_fb'         => $lien_fb,
                'lien_tw'         => $lien_tw
            ];

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->messageProvider->newMessage('preteur-alimentation-cb', $varMail);
            $message->setTo($client->email);
            $this->mailer->send($message);
        }
    }
}
