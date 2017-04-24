<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Repository\WalletRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\ClientStatusManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Form\LenderWithdrawalType;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\core\Loader;

class LenderWalletController extends Controller
{
    const MAX_DEPOSIT_AMOUNT = 5000;
    const MIN_DEPOSIT_AMOUNT = 20;

    /**
     * @Route("/alimentation", name="lender_wallet_deposit")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @return Response
     */
    public function walletDepositAction()
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');

        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        $clientData = $client->select('id_client = ' . $this->getUser()->getClientId())[0];

        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        $lenderData = $lender->select('id_client_owner = ' . $clientData['id_client'])[0];

        $template = [
            'balance'          => $this->getUser()->getBalance(),
            'maxDepositAmount' => self::MAX_DEPOSIT_AMOUNT,
            'minDepositAmount' => self::MIN_DEPOSIT_AMOUNT,
            'client'           => $clientData,
            'lender'           => $lenderData,
            'lenderBankMotif'  => $client->getLenderPattern($clientData['id_client']),
            'showNavigation'   => $this->getUser()->getClientStatus() >= \clients_status::VALIDATED
        ];

        return $this->render('pages/lender_wallet/deposit.html.twig', $template);
    }

    /**
     * @Route("/alimentation/resultat/{token}", name="lender_wallet_deposit_result")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @return Response
     */
    public function walletDepositResultAction($token)
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $backPayline   = $entityManager->getRepository('UnilendCoreBusinessBundle:Backpayline')->findOneBy(['token' => $token]);
        /** @var UserLender $user */
        $user = $this->getUser();
        if ($user) {
            $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($user->getClientId(), WalletType::LENDER);
            if ($wallet && $backPayline && $backPayline->getWallet() === $wallet) {
                return $this->render('pages/lender_wallet/deposit_result.html.twig', [
                    'depositAmount'  => round(bcdiv($backPayline->getAmount(), 100, 4), 2),
                    'depositCode'    => $backPayline->getCode(),
                    'showNavigation' => $this->getUser()->getClientStatus() >= \clients_status::VALIDATED
                ]);
            }
        }

        return $this->redirectToRoute('lender_wallet_deposit');
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
            'withdrawalForm'  => $form->createView()
        ];

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $token = $this->get('security.csrf.token_manager');
            $token->refreshToken(LenderWithdrawalType::CSRF_TOKEN_ID);

            if ($form->isValid()) {
                $post = $form->getData();
                $this->handleWithdrawalPost($post);
            } else {
                $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message'));
            }

            //Redirection is needed to refresh the token in the form which is already generated above
            return $this->redirectToRoute('lender_wallet_withdrawal');
        }

        return $this->render('pages/lender_wallet/withdrawal.html.twig', $template);
    }

    /**
     * @param array $post
     */
    private function handleWithdrawalPost(array $post)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \transactions $transaction */
        $transaction = $entityManager->getRepository('transactions');
        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
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
        $em                  = $this->get('doctrine.orm.entity_manager');

        $client = $em->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->getUser()->getClientId());

        if ($client) {
            /** @var \clients_history_actions $clientActionHistory */
            $clientActionHistory = $entityManager->getRepository('clients_history_actions');
            $serialize           = serialize(array('id_client' => $client->getIdClient(), 'montant' => $post['amount'], 'mdp' => md5($post['password'])));
            $clientActionHistory->histo(3, 'retrait argent', $client->getIdClient(), $serialize);

            if ($clientStatusManager->getLastClientStatus($client) < \clients_status::VALIDATED) {
                $this->redirectToRoute('lender_wallet_withdrawal');
            }

            $lender->get($client->getIdClient(), 'id_client_owner');

            $amount = $post['amount'];

            /** @var UserPasswordEncoder $securityPasswordEncoder */
            $securityPasswordEncoder = $this->get('security.password_encoder');

            if (false === $securityPasswordEncoder->isPasswordValid($this->getUser(), $post['password'])) {
                $logger->info('Wrong password id_client=' . $client->getIdClient(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->getIdClient()]);
                $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message'));
            } else {
                if (false === is_numeric($amount)) {
                    $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message'));
                } elseif (empty($lender->bic) || empty($lender->iban)) {
                    $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message'));
                } else {
                    $sumOffres = $this->get('unilend.service.welcome_offer_manager')->getCurrentWelcomeOfferAmount($client);

                    if (($amount + $sumOffres) > $this->getUser()->getBalance() || $amount <= 0) {
                        $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message'));
                    }
                }
            }

            if ($this->get('session')->getFlashBag()->has('withdrawalErrors')) {
                $logger->warning('Wrong parameters submitted, id_client=' . $client->getIdClient() . ' Amount : ' . $post['amount'], ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->getIdClient()]);
            } else {
                $em          = $this->get('doctrine.orm.entity_manager');
                /** @var WalletRepository $walletRepo */
                $walletRepo  = $em->getRepository('UnilendCoreBusinessBundle:Wallet');
                $wallet      = $walletRepo->getWalletByType($client->getIdClient(), WalletType::LENDER);
                $bankAccount = $em->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($wallet->getIdClient());

                if ($bankAccount) {
                    try {
                        $wireTransferOutManager = $this->get('unilend.service.wire_transfer_out_manager');
                        $wireTransferOut        = $wireTransferOutManager->createTransfer($wallet, $amount, $bankAccount);

                        $transaction->get($wireTransferOut->getIdTransaction());

                        $notification->type      = \notifications::TYPE_DEBIT;
                        $notification->id_lender = $lender->id_lender_account;
                        $notification->amount    = $amount * 100;
                        $notification->create();

                        $this->getUser()->setBalance($transaction->getSolde($client->getIdClient()));

                        $clientMailNotification->id_client       = $client->getIdClient();
                        $clientMailNotification->id_notif        = \clients_gestion_type_notif::TYPE_DEBIT;
                        $clientMailNotification->date_notif      = date('Y-m-d H:i:s');
                        $clientMailNotification->id_notification = $notification->id_notification;
                        $clientMailNotification->id_transaction  = $transaction->id_transaction;
                        $clientMailNotification->create();

                        if ($clientNotification->getNotif($client->getIdClient(), 8, 'immediatement') == true) {
                            $clientMailNotification->get($clientMailNotification->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                            $clientMailNotification->immediatement = 1;
                            $clientMailNotification->update();
                            $this->sendClientWithdrawalNotification($client, $amount);
                        }

                        $this->addFlash('withdrawalSuccess', $translator->trans('lender-wallet_withdrawal-success-message'));
                    } catch (\Exception $exception) {
                        $logger->error('Failed to handle withdrawal operation for client : ' . $client->getIdClient() . 'Error : ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->getIdClient()]);
                        $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message'));
                    }
                } else {
                    $logger->info('No validated bank account found for id_client=' . $client->getIdClient(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $client->getIdClient()]);
                    $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message'));
                }
            }
        }
    }

    /**
     * @Route("/alimentation/apport", name="deposit_money")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     * @return JsonResponse|Response
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
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
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
            $token = $this->get('security.csrf.token_manager');
            $token->refreshToken('deposit');

            /** @var \clients_history_actions $clientActionHistory */
            $clientActionHistory = $entityManager->getRepository('clients_history_actions');
            $clientActionHistory->histo(2, 'alim cb', $client->id_client, serialize(array('id_client' => $client->id_client, 'post' => $request->request->all())));

            $em = $this->get('doctrine.orm.entity_manager');
            $walletType = $em->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::LENDER]);
            $wallet = $em->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idClient' => $client->id_client, 'idType' => $walletType]);

            $successUrl = $this->generateUrl('wallet_payment', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $cancelUrl = $this->generateUrl('wallet_payment', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $redirectUrl = $this->get('unilend.service.payline_manager')->pay($amount, $wallet, $successUrl, $cancelUrl);

            if (false !== $redirectUrl) {
                return $this->json(['url' => $redirectUrl], Response::HTTP_OK);
            }
        }
        return $this->json(['message' => $this->render('pages/lender_wallet/deposit_money_result.html.twig', ['code' => 0])->getContent()], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @Route("/alimentation/payment", name="wallet_payment")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     * @return Response
     */
    public function paymentAction(Request $request)
    {
        /** @var LoggerInterface $logger */
        $logger  = $this->get('logger');
        $token   = $request->query->get('token');
        $version = $request->query->get('version', Backpayline::WS_DEFAULT_VERSION);
        if (true === empty($token)) {
            $clientId = $this->getUser()->getClientId();
            $logger->error('Payline token not found, id_client=' . $clientId, ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $clientId]);
            return $this->redirectToRoute('lender_wallet_deposit');
        }
        $paylineManager = $this->get('unilend.service.payline_manager');
        $paylineManager->handlePaylineReturn($token, $version);

        return $this->redirectToRoute('lender_wallet_deposit_result', ['token' => $token]);
    }

    /**
     * @param Clients $client
     * @param $amount
     */
    private function sendClientWithdrawalNotification(Clients $client, $amount)
    {
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var \settings $settings */
        $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');

        $settings->get('Facebook', 'type');
        $lien_fb = $settings->value;
        $settings->get('Twitter', 'type');
        $lien_tw = $settings->value;

        $wallet = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        $varMail = [
            'surl'            => $this->get('assets.packages')->getUrl(''),
            'url'             => $this->get('assets.packages')->getUrl(''),
            'prenom_p'        => $client->getPrenom(),
            'fonds_retrait'   => $ficelle->formatNumber($amount),
            'solde_p'         => $ficelle->formatNumber($this->getUser()->getBalance()),
            'link_mandat'     => $this->get('assets.packages')->getUrl('') . '/images/default/mandat.jpg',
            'motif_virement'  => $wallet->getWireTransferPattern(),
            'projets'         => $this->get('assets.packages')->getUrl('') . $this->generateUrl('home', ['type' => 'projets-a-financer']),
            'gestion_alertes' => $this->get('assets.packages')->getUrl('') . $this->generateUrl('lender_profile'),
            'lien_fb'         => $lien_fb,
            'lien_tw'         => $lien_tw
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-retrait', $varMail);
        $message->setTo($client->getEmail());
        $mailer = $this->get('mailer');
        $mailer->send($message);
    }
}
