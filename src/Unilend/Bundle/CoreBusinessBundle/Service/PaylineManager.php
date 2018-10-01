<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\{EntityManagerInterface, OptimisticLockException};
use Monolog\Logger;
use Payline\PaylineSDK;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Backpayline, ClientsGestionMailsNotif, ClientsGestionTypeNotif, Notifications, OperationType, Wallet};
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class PaylineManager
{
    const ORDER_CURRENCY              = 978;
    const PAYMENT_ACTION              = 101;
    const PAYMENT_MODE                = 'CPT';
    const CONTRACT_NUMBER             = '4543015';
    const CONTRACT_NUMBER_LIST        = '4543015';
    const SECOND_CONTRACT_NUMBER_LIST = '';

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;
    /** @var OperationManager */
    private $operationManager;
    /** @var RouterInterface */
    private $router;
    /** @var \NumberFormatter */
    private $currencyFormatter;
    /** @var string */
    private $merchantId;
    /** @var string */
    private $accessKey;
    /** @var string */
    private $environment;
    /** @var string */
    private $logPath;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface  $entityManager
     * @param TemplateMessageProvider $messageProvider
     * @param \Swift_Mailer           $mailer
     * @param OperationManager        $operationManager
     * @param RouterInterface         $router
     * @param \NumberFormatter        $currencyFormatter
     * @param string                  $merchantId
     * @param string                  $accessKey
     * @param string                  $environment
     * @param string                  $logPath
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        OperationManager $operationManager,
        RouterInterface $router,
        \NumberFormatter $currencyFormatter,
        string $merchantId,
        string $accessKey,
        string $environment,
        string $logPath
    )
    {
        $this->entityManager     = $entityManager;
        $this->messageProvider   = $messageProvider;
        $this->mailer            = $mailer;
        $this->operationManager  = $operationManager;
        $this->router            = $router;
        $this->currencyFormatter = $currencyFormatter;
        $this->merchantId        = $merchantId;
        $this->accessKey         = $accessKey;
        $this->environment       = $environment === 'prod' ? PaylineSDK::ENV_PROD : PaylineSDK::ENV_HOMO;
        $this->logPath           = $logPath . '/';
    }

    /**
     * @required
     *
     * @param LoggerInterface|null $logger
     */
    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param float  $amount
     * @param Wallet $wallet
     * @param string $returnUrl
     * @param string $cancelUrl
     *
     * @return string|null
     * @throws OptimisticLockException
     */
    public function pay(float $amount, Wallet $wallet, string $returnUrl, string $cancelUrl): ?string
    {
        $amountInCent = (int) (number_format($amount, 2, '.', '') * 100);

        $backPayline = new Backpayline();
        $backPayline
            ->setWallet($wallet)
            ->setAmount($amountInCent);

        $this->entityManager->persist($backPayline);
        $this->entityManager->flush($backPayline);

        $parameters = [
            'returnURL'       => $returnUrl,
            'cancelURL'       => $cancelUrl,
            'notificationURL' => $this->router->generate('payline_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'payment'         => [
                'amount'         => $amountInCent,
                'currency'       => self::ORDER_CURRENCY,
                'action'         => self::PAYMENT_ACTION,
                'mode'           => self::PAYMENT_MODE,
                'contractNumber' => self::CONTRACT_NUMBER,
            ],
            'order'           => [
                'ref'      => $backPayline->getIdBackpayline(),
                'amount'   => $amountInCent,
                'currency' => self::ORDER_CURRENCY,
                'date'     => date('d/m/Y H:i')
            ],
            'contracts'       => explode(';', self::CONTRACT_NUMBER_LIST),
            'secondContracts' => explode(';', self::SECOND_CONTRACT_NUMBER_LIST),
        ];

        $this->logger->debug('Calling Payline "doWebPayment". Request parameters: ' . json_encode($parameters), [
            'wallet'   => $wallet->getId(),
            'class'    => __CLASS__,
            'function' => __FUNCTION__
        ]);

        $result = $this->getSDK()->doWebPayment($parameters);

        $this->logger->debug('Payline "doWebPayment" response: ' . json_encode($result), [
            'wallet'   => $wallet->getId(),
            'class'    => __CLASS__,
            'function' => __FUNCTION__
        ]);

        $backPayline->setSerializeDoPayment(serialize($result));

        if (isset($result['token'])) {
            $backPayline->setToken($result['token']);
        } else {
            $this->logger->warning('No token returned in the response of Payline::doWebPayment()', [
                'wallet'   => $wallet->getId(),
                'response' => $result,
                'class'    => __CLASS__,
                'function' => __FUNCTION__
            ]);
        }

        $this->entityManager->flush($backPayline);

        if (false === isset($result['result']['code']) || $result['result']['code'] !== Backpayline::CODE_TRANSACTION_APPROVED) {
            $this->logger->error('Could not provision lender wallet ' . $wallet->getId() . '. Error code: ' . $result['result']['code'] . '. Message: ' . $result['result']['longMessage'], [
                'wallet'   => $wallet->getId(),
                'response' => $result,
                'class'    => __CLASS__,
                'function' => __FUNCTION__
            ]);

            return false;
        }

        return $result['redirectURL'];
    }

    /**
     * @param string $token
     * @param int    $version
     *
     * @return int|null
     * @throws OptimisticLockException
     */
    public function handleResponse(string $token, int $version): ?int
    {
        $this->logger->info('Calling Payline::getWebPaymentDetails: using token "' . $token . '". Version ' . $version, [
            'token'    => $token,
            'class'    => __CLASS__,
            'function' => __FUNCTION__
        ]);

        $response = $this->getSDK()->getWebPaymentDetails(['token' => $token, 'version' => $version]);

        $this->logger->info('Payline getWebPaymentDetails response: ' . json_encode($response), [
            'token'    => $token,
            'class'    => __CLASS__,
            'function' => __FUNCTION__
        ]);

        if (empty($response)) {
            return null;
        }

        $backPayline = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Backpayline')->findOneBy(['idBackpayline' => $response['order']['ref']]);

        if ($backPayline instanceof Backpayline) {
            $backPayline
                ->setId($response['transaction']['id'])
                ->setDate($response['transaction']['date'])
                ->setToken($token)
                ->setSerialize(serialize($response))
                ->setCode($response['result']['code']);

            if (isset($response['card']) && isset($response['card']['number'])) {
                $backPayline->setCardNumber($response['card']['number']);
            }

            $this->entityManager->flush($backPayline);

            if ($response['result']['code'] === Backpayline::CODE_TRANSACTION_APPROVED && $backPayline->getAmount() != $response['payment']['amount']) {
                $errorMsg = 'Payline amount for wallet ID ' . $backPayline->getWallet()->getId() .
                    ' is not the same between the response (' . $response['payment']['amount'] . ')' .
                    ' and database (' . $backPayline->getAmount() . ') ';
                $this->logger->error($errorMsg, [
                    'wallet'   => $backPayline->getWallet()->getId(),
                    'class'    => __CLASS__,
                    'function' => __FUNCTION__
                ]);

                return null;
            }

            if ($response['result']['code'] === Backpayline::CODE_TRANSACTION_APPROVED) {
                $this->operationManager->provisionLenderWallet($backPayline->getWallet(), $backPayline);
                $this->notifyClientAboutMoneyTransfer($backPayline);
                // See codes https://support.payline.com/hc/fr/article_attachments/206064778/PAYLINE-GUIDE-Descriptif_des_appels_webservices-FR-v3.A.pdf
            } elseif (in_array($response['result']['code'], ['01109', '01110', '01114', '01115', '01122', '01123', '01181', '01182', '01197', '01198', '01199', '01207', '01904', '01907', '01909', '01912', '01913', '01914', '01940', '01941', '01942', '01943', '02101', '02102', '02103', '02109', '02201', '02202', '02301', '02303', '02304', '02305', '02307', '02308', '02309', '02310', '02311', '02312', '02313', '02314', '02315', '02316', '02317', '02318', '02320', '02321', '02322'])) {
                $this->logger->error('Lender provision error for wallet ' . $backPayline->getWallet()->getId() . '. Error code: ' . $response['result']['code'], [
                    'class'    => __CLASS__,
                    'function' => __FUNCTION__
                ]);

                return null;
            }
        } else {
            $this->logger->error('Payline order cannot be found', [
                'ref'      => $response['order']['ref'],
                'response' => $response,
                'class'    => __CLASS__,
                'function' => __FUNCTION__
            ]);

            return null;
        }

        return (int) $response['payment']['amount'];
    }

    /**
     * @return PaylineSDK
     */
    private function getSDK(): PaylineSDK
    {
        return new PaylineSDK(
            $this->merchantId,
            $this->accessKey,
            null,
            null,
            null,
            null,
            $this->environment,
            $this->logPath,
            Logger::INFO,
            $this->logger
        );
    }

    /**
     * @param Backpayline $backPayline
     *
     * @throws OptimisticLockException
     */
    private function notifyClientAboutMoneyTransfer(Backpayline $backPayline): void
    {
        $lenderProvision      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_PROVISION]);
        $provisionOperation   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy(['idBackpayline' => $backPayline, 'idType' => $lenderProvision]);
        $walletBalanceHistory = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory')->findOneBy(['idWallet' => $backPayline->getWallet(), 'idOperation' => $provisionOperation]);

        $amount = round(bcdiv($backPayline->getAmount(), 100, 4), 2);


        $notification = new Notifications();
        $notification
            ->setType(Notifications::TYPE_CREDIT_CARD_CREDIT)
            ->setIdLender($backPayline->getWallet())
            ->setAmount($backPayline->getAmount());

        $this->entityManager->persist($notification);
        $this->entityManager->flush($notification);

        $emailNotification = new ClientsGestionMailsNotif();
        $emailNotification
            ->setIdClient($backPayline->getWallet()->getIdClient()->getIdClient())
            ->setIdNotif(ClientsGestionTypeNotif::TYPE_CREDIT_CARD_CREDIT)
            ->setDateNotif(new \DateTime())
            ->setIdNotification($notification->getIdNotification())
            ->setIdWalletBalanceHistory($walletBalanceHistory);

        $notificationSettings = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:ClientsGestionNotifications')
            ->findOneBy([
                'idClient'      => $backPayline->getWallet()->getIdClient()->getIdClient(),
                'idNotif'       => ClientsGestionTypeNotif::TYPE_CREDIT_CARD_CREDIT,
                'immediatement' => 1
            ]);

        if ($notificationSettings) {
            $emailNotification->setImmediatement(1);

            $message = $this->messageProvider->newMessage('preteur-alimentation-cb', [
                'firstName'     => $backPayline->getWallet()->getIdClient()->getPrenom(),
                'amount'        => $this->currencyFormatter->formatCurrency($amount, 'EUR'),
                'balance'       => $this->currencyFormatter->formatCurrency((float) $backPayline->getWallet()->getAvailableBalance(), 'EUR'),
                'lenderPattern' => $backPayline->getWallet()->getWireTransferPattern()
            ]);

            try {
                $message->setTo($backPayline->getWallet()->getIdClient()->getEmail());
                $this->mailer->send($message);
            } catch (\Exception $exception) {
                $this->logger->warning('Could not send email "preteur-alimentation-cb". Message: ' . $exception->getMessage(), [
                    'id_mail_template' => $message->getTemplateId(),
                    'id_client'        => $backPayline->getWallet()->getIdClient()->getIdClient(),
                    'class'            => __CLASS__,
                    'function'         => __FUNCTION__,
                    'file'             => $exception->getFile(),
                    'line'             => $exception->getLine(),
                ]);
            }
        }

        $this->entityManager->persist($emailNotification);
        $this->entityManager->flush($emailNotification);
    }
}
