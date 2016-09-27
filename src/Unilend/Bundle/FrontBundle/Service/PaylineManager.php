<?php

namespace Unilend\Bundle\FrontBundle\Service;


use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class PaylineManager
{
    const PAYMENT_LOCATION_LENDER_SUBSCRIPTION = 1;
    const PAYMENT_LOCATION_LENDER_WALLET = 2;

    /** @var EntityManager  */
    private $entityManager;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;
    /** @var ClientManager  */
    private $clientManager;
    /** @var Router  */
    private $router;
    /** @var LoggerInterface  */
    private $logger;

    private $paylinePath;
    private $sUrl;

    public function __construct(
        EntityManager $entityManager,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        ClientManager $clientManager,
        RouterInterface $router,
        Packages $assetsPackages,
        $paylinePath
    ) {
        $this->entityManager = $entityManager;
        $this->messageProvider = $messageProvider;
        $this->mailer = $mailer;
        $this->clientManager = $clientManager;
        $this->router = $router;
        $this->paylinePath = $paylinePath;
        $this->sUrl = $assetsPackages->getUrl('');

    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param \clients $client
     * @param array $response
     * @param array $paylineParameter
     * @param string $partnerId
     * @return bool
     */
    public function handlePaylineReturn(\clients $client, $response, $paylineParameter, $partnerId, $locationCall)
    {
        require_once $this->paylinePath . 'include.php';

        /** @var \transactions $transaction */
        $transaction = $this->entityManager->getRepository('transactions');
        /** @var \wallets_lines $walletLine */
        $walletLine = $this->entityManager->getRepository('wallets_lines');
        /** @var \bank_lines $bankLine */
        $bankLine = $this->entityManager->getRepository('bank_lines');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->entityManager->getRepository('lenders_accounts');

        /** @var \backpayline $backPayline */
        $backPayline = $this->entityManager->getRepository('backpayline');

        $backPayline->code      = $response['result']['code'];
        $backPayline->token     = $paylineParameter['token'];
        $backPayline->id        = $response['transaction']['id'];
        $backPayline->date      = $response['transaction']['date'];
        $backPayline->amount    = $response['payment']['amount'];
        $backPayline->serialize = serialize($response);
        $backPayline->create();

        if ($response['result']['code'] == '00000') {
            if ($transaction->get($response['order']['ref'], 'status = 0 AND etat = 0 AND id_transaction')) {
                $transaction->id_backpayline   = $backPayline->id_backpayline;
                $transaction->montant          = $response['payment']['amount'];
                $transaction->id_langue        = 'fr';
                $transaction->date_transaction = date('Y-m-d H:i:s');
                $transaction->status           = \transactions::PHYSICAL;
                $transaction->etat             = 1;
                /** @todo id_partenaire is set from db table : partenaires.id_partenaire */
                $transaction->id_partenaire    = $partnerId;
                $transaction->type_paiement    = ($response['extendedCard']['type'] == 'VISA' ? \transactions::PAYMENT_TYPE_VISA : ($response['extendedCard']['type'] == 'MASTERCARD' ? \transactions::PAYMENT_TYPE_MASTERCARD : ''));
                $transaction->update();

                $lenderAccount->get($client->id_client, 'id_client_owner');
                $lenderAccount->status = \lenders_accounts::LENDER_STATUS_ONLINE;
                $lenderAccount->update();

                $walletLine->id_lender                = $lenderAccount->id_lender_account;
                $walletLine->type_financial_operation = ($locationCall == self::PAYMENT_LOCATION_LENDER_WALLET) ? \wallets_lines::TYPE_MONEY_SUPPLY : \wallets_lines::TYPE_LENDER_SUBSCRIPTION;
                $walletLine->id_transaction           = $transaction->id_transaction;
                $walletLine->status                   = \wallets_lines::STATUS_VALID;
                $walletLine->type                     = \wallets_lines::PHYSICAL;
                $walletLine->amount                   = $response['payment']['amount'];
                $walletLine->create();

                $bankLine->id_wallet_line    = $walletLine->id_wallet_line;
                $bankLine->id_lender_account = $lenderAccount->id_lender_account;
                $bankLine->status            = 1;
                $bankLine->amount            = $response['payment']['amount'];
                $bankLine->create();

                if ($client->etape_inscription_preteur < 3) {
                    $client->etape_inscription_preteur = 3;
                    $client->update();
                }

                if ($locationCall == self::PAYMENT_LOCATION_LENDER_WALLET) {
                    $this->notifyClientAboutMoneyTransfer($client, $lenderAccount, $response, $transaction);
                }
            }
            return true;
        } elseif ($response['result']['code'] == '02319') { // Payment cancelled
            $transaction->get($response['order']['ref'], 'id_transaction');
            $transaction->id_backpayline = $backPayline->id_backpayline;
            $transaction->statut         = '0';
            $transaction->etat           = '3';
            $transaction->update();

        } else { // Payment error
            mail('alertesit@unilend.fr', 'unilend payline erreur', 'erreur sur page payment alimentation preteur (client : ' . $client->id_client . ') : ' . serialize($response));
        }
        return false;
    }

    private function notifyClientAboutMoneyTransfer(\clients $client, \lenders_accounts $lenderAccount, $response, \transactions $transaction)
    {
        /** @var \notifications $notification */
        $notification = $this->entityManager->getRepository('notifications');
        /** @var \clients_gestion_notifications $clientNotification */
        $clientNotification = $this->entityManager->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clientMailNotification */
        $clientMailNotification = $this->entityManager->getRepository('clients_gestion_mails_notif');

        $notification->type      = \notifications::TYPE_CREDIT_CARD_CREDIT;
        $notification->id_lender = $lenderAccount->id_lender_account;
        $notification->amount    = $response['payment']['amount'];
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
            $settings = $this->entityManager->getRepository('settings');
            $settings->get('Facebook', 'type');
            $lien_fb = $settings->value;

            $settings->get('Twitter', 'type');
            $lien_tw = $settings->value;

            $varMail = [
                'surl'            => $this->sUrl,
                'url'             => $this->sUrl,
                'prenom_p'        => $client->prenom,
                'fonds_depot'     => bcdiv($response['payment']['amount'], 100, 2),
                'solde_p'         => bcadd($this->clientManager->getClientBalance($client), bcdiv($response['payment']['amount'], 100, 2), 2),
                'link_mandat'     => $this->sUrl . '/images/default/mandat.jpg',
                'motif_virement'  => $client->getLenderPattern($client->id_client),
                'projets'         => $this->sUrl . $this->router->generate('home', ['type' => 'projets-a-financer']),
                'gestion_alertes' => $this->sUrl . $this->router->generate('lender_profile'),
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