<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Backpayline, BankAccount, Clients, ClientsGestionMailsNotif, ClientsGestionTypeNotif, ClientsHistoryActions, ClientsStatus, Notifications, Wallet, WalletType};
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
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function depositAction(?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderDeposit()) {
            return $this->redirectToRoute('lender_dashboard');
        }

        $wallet = $this->getWallet($client);

        $template = [
            'balance'          => $wallet->getAvailableBalance(),
            'maxDepositAmount' => self::MAX_DEPOSIT_AMOUNT,
            'minDepositAmount' => self::MIN_DEPOSIT_AMOUNT,
            'client'           => $client,
            'lenderBankMotif'  => $wallet->getWireTransferPattern()
        ];

        return $this->render('lender_wallet/deposit.html.twig', $template);
    }

    /**
     * @Route("/alimentation/resultat/{paymentToken}", name="lender_wallet_deposit_result")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param string                     $paymentToken
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function depositResultAction(string $paymentToken, ?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderDeposit()) {
            return $this->redirectToRoute('lender_dashboard');
        }

        $entityManager = $this->get('doctrine.orm.entity_manager');
        $backPayline   = $entityManager->getRepository('UnilendCoreBusinessBundle:Backpayline')->findOneBy(['token' => $paymentToken]);
        $wallet        = $this->getWallet($client);

        if ($wallet && $backPayline && $backPayline->getWallet() === $wallet) {
            return $this->render('lender_wallet/deposit_result.html.twig', [
                'depositAmount' => round(bcdiv($backPayline->getAmount(), 100, 4), 2),
                'depositCode'   => $backPayline->getCode()
            ]);
        }

        return $this->redirectToRoute('lender_wallet_deposit');
    }

    /**
     * @Route("/alimentation/erreur", name="lender_wallet_deposit_result_error")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function depositResultErrorAction(?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderDeposit()) {
            return $this->redirectToRoute('lender_dashboard');
        }

        return $this->render('lender_wallet/deposit_result.html.twig', ['depositCode' => 'X']);
    }

    /**
     * @Route("/retrait", name="lender_wallet_withdrawal")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @return Response
     */
    public function withdrawalAction(Request $request, ?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderWithDraw()) {
            return $this->redirectToRoute('lender_dashboard');
        }

        $entityManager = $this->get('doctrine.orm.entity_manager');
        $bankAccount   = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($client);

        if (null === $bankAccount) {
            $this->setWithdrawalInformationMessage($client);
        }

        $form     = $this->createForm(LenderWithdrawalType::class);
        $template = [
            'balance'         => $this->getWallet($client)->getAvailableBalance(),
            'client'          => $client,
            'bankAccount'     => $bankAccount,
            'lenderBankMotif' => $this->getWallet($client)->getWireTransferPattern(),
            'withdrawalForm'  => $form->createView()
        ];

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $token = $this->get('security.csrf.token_manager');
            $token->refreshToken(LenderWithdrawalType::CSRF_TOKEN_ID);

            if ($form->isValid()) {
                $post = $form->getData();
                $this->handleWithdrawalPost($client, $request, $post);
            } else {
                $this->addFlash('withdrawalErrors', $this->get('translator')->trans('lender-wallet_withdrawal-error-message'));
            }

            //Redirection is needed to refresh the token in the form which is already generated above
            return $this->redirectToRoute('lender_wallet_withdrawal');
        }

        return $this->render('lender_wallet/withdrawal.html.twig', $template);
    }

    /**
     * @param Clients $client
     */
    private function setWithdrawalInformationMessage(Clients $client): void
    {
        $lastModifiedBankAccount = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:BankAccount')->getLastModifiedBankAccount($client);
        $translator              = $this->get('translator');

        if (null === $lastModifiedBankAccount) {
            $this->addFlash('withdrawalInfo', $translator->trans('lender-wallet_withdrawal-error-no-valid-or-pending-iban', ['%fiscalUrl%' => $this->generateUrl('lender_profile_fiscal_information')]));
            $this->get('logger')->error('The client ' . $client->getIdClient() . ' navigated to withdrawal page, but he has no bank account', [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__
            ]);
        } else {
            $this->addFlash('withdrawalInfo', $translator->trans('lender-wallet_withdrawal-error-no-valid-iban'));
            $this->get('logger')->warning('The client ' . $client->getIdClient() . ' navigated to withdrawal page, but his pending bank account is not validated yet.', [
                'id_client'       => $client->getIdClient(),
                'id_bank_account' => $lastModifiedBankAccount->getId(),
                'class'           => __CLASS__,
                'function'        => __FUNCTION__
            ]);
        }
    }

    /**
     * @param Clients $client
     * @param Request $request
     * @param array   $post
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function handleWithdrawalPost(Clients $client, Request $request, array $post): void
    {
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \clients_gestion_notifications $clientNotification */
        $clientNotification = $entityManagerSimulator->getRepository('clients_gestion_notifications');

        $logger        = $this->get('logger');
        $translator    = $this->get('translator');
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $formManager   = $this->get('unilend.frontbundle.service.form_manager');
        $wallet        = $this->getWallet($client);

        if ($client instanceof Clients) {
            /** @var BankAccount $bankAccount */
            $bankAccount = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($client);
            $amount      = $post['amount'];
            $serialize   = serialize(['id_client' => $client->getIdClient(), 'montant' => $amount, 'mdp' => md5($post['password'])]);

            $formManager->saveFormSubmission($client, ClientsHistoryActions::LENDER_WITHDRAWAL, $serialize, $request->getClientIp());

            $securityPasswordEncoder = $this->get('security.password_encoder');

            if (false === $securityPasswordEncoder->isPasswordValid($client, $post['password'])) {
                $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message-wrong-password'));
            } elseif (false === is_numeric($amount) || $amount <= 0) {
                $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message-wrong-amount'));
            } elseif (null === $bankAccount || empty($bankAccount->getBic()) || empty($bankAccount->getIban())) {
                $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message-missing-bank-details'));
            } elseif ($amount > $wallet->getAvailableBalance()) {
                $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message-insufficient-balance'));
            } else {
                try {
                    $unusedWelcomeOfferAmount = $this->get('unilend.service.welcome_offer_manager')->getUnusedWelcomeOfferAmount($client);
                } catch (\Exception $exception) {
                    $logger->error('Could not get unused welcome offer amount. Failed to handle withdrawal operation for client: ' . $client->getIdClient() . 'Error: ' . $exception->getMessage(), [
                        'id_client' => $client->getIdClient(),
                        'class'     => __CLASS__,
                        'function'  => __FUNCTION__,
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine()
                    ]);
                    $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message'));

                    return;
                }
                $unusedSponseeRewardAmount = $this->get('unilend.service.sponsorship_manager')->getUnusedSponseeRewardAmount($client);
                $unusedSponsorRewardAmount = $this->get('unilend.service.sponsorship_manager')->getUnusedSponsorRewardAmount($client);
                $blockedAmount             = round(bcadd($unusedWelcomeOfferAmount, bcadd($unusedSponseeRewardAmount, $unusedSponsorRewardAmount, 4), 4), 2);

                if (round(bcadd($amount, $blockedAmount, 4), 2) > $wallet->getAvailableBalance()) {
                    $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message-blocked-amount'));
                }
            }

            if ($this->get('session')->getFlashBag()->has('withdrawalErrors')) {
                $logger->warning('Withdrawal cannot be accomplished, id_client: ' . $client->getIdClient() . ' Amount: ' . $post['amount'], [
                    'id_client' => $client->getIdClient(),
                    'errors'    => $this->get('session')->getFlashBag()->peek('withdrawalErrors'),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                ]);
            } else {
                $wireTransferOutManager = $this->get('unilend.service.wire_transfer_out_manager');
                try {
                    $wireTransferOut = $wireTransferOutManager->createTransfer($wallet, $amount, $bankAccount);
                } catch (\Exception $exception) {
                    $logger->error('Could not create bank transfer out entry. Failed to handle withdrawal operation for client: ' . $client->getIdClient() . 'Error : ' . $exception->getMessage(), [
                        'id_client' => $client->getIdClient(),
                        'class'     => __CLASS__,
                        'function'  => __FUNCTION__,
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine()
                    ]);
                    $this->addFlash('withdrawalErrors', $translator->trans('lender-wallet_withdrawal-error-message'));

                    return;
                }
                $notificationManager = $this->get('unilend.service.notification_manager');
                $notification        = $notificationManager->createNotification(Notifications::TYPE_DEBIT, $wallet->getIdClient()->getIdClient(), null, $amount);

                $withdrawalOperation  = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->findOneBy(['idWireTransferOut' => $wireTransferOut]);
                $walletBalanceHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory')->findOneBy([
                    'idOperation' => $withdrawalOperation,
                    'idWallet'    => $wallet
                ]);

                try {
                    $clientMailNotification = $notificationManager->createEmailNotification(ClientsGestionTypeNotif::TYPE_DEBIT, $client->getIdClient(), $notification->id_notification, $walletBalanceHistory, null, null, false, new \DateTime());
                } catch (\Exception $exception) {
                    $clientMailNotification = null;
                    $logger->error('Could not create client email notification on withdrawal operation for client: ' . $client->getIdClient() . ' Error: ' . $exception->getMessage(), [
                        'id_client' => $client->getIdClient(),
                        'class'     => __CLASS__,
                        'function'  => __FUNCTION__,
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine()
                    ]);
                }

                if ($clientNotification->getNotif($client->getIdClient(), ClientsGestionTypeNotif::TYPE_DEBIT, 'immediatement') == true) {
                    $this->sendClientWithdrawalNotification($wallet, $amount, $clientMailNotification);
                }

                $this->addFlash('withdrawalSuccess', $translator->trans('lender-wallet_withdrawal-success-message'));
            }
        }
    }

    /**
     * @Route("/alimentation/apport", name="deposit_money", methods={"POST"})
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return JsonResponse|Response
     */
    public function depositMoneyAction(Request $request, ?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderDeposit()) {
            return $this->redirectToRoute('lender_dashboard');
        }

        $csrfTokenManager       = $this->get('security.csrf.token_manager');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $amount    = $ficelle->cleanFormatedNumber($request->request->get('amount', ''));
        $csrfToken = $request->request->get('_csrf_token');

        if (
            is_numeric($amount)
            && $amount >= self::MIN_DEPOSIT_AMOUNT
            && $amount <= self::MAX_DEPOSIT_AMOUNT
            && $csrfTokenManager->isTokenValid(new CsrfToken('deposit', $csrfToken))
        ) {
            $token = $this->get('security.csrf.token_manager');
            $token->refreshToken('deposit');

            $formManager = $this->get('unilend.frontbundle.service.form_manager');
            $formManager->saveFormSubmission($client, ClientsHistoryActions::LENDER_PROVISION_BY_CREDIT_CARD, serialize(['id_client' => $client->getIdClient(), 'post' => $request->request->all()]), $request->getClientIp());

            $wallet     = $this->getWallet($client);
            $successUrl = $this->generateUrl('wallet_payment', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $cancelUrl  = $this->generateUrl('wallet_payment', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $redirectUrl = $this->get('unilend.service.payline_manager')->pay($amount, $wallet, $successUrl, $cancelUrl);

            if (null !== $redirectUrl) {
                return $this->redirect($redirectUrl);
            }
        }

        return $this->redirectToRoute('lender_wallet_deposit_result_error');
    }

    /**
     * @Route("/alimentation/payment", name="wallet_payment")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function paymentAction(Request $request, ?UserInterface $client): Response
    {
        if (false === $client->isGrantedLenderDeposit()) {
            return $this->redirectToRoute('lender_dashboard');
        }

        $logger  = $this->get('logger');
        $token   = $request->query->filter('token', FILTER_SANITIZE_STRING);
        $version = $request->query->getInt('version', Backpayline::WS_DEFAULT_VERSION);

        if (empty($token)) {
            $logger->error('Payline token not found.' ,[
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__
            ]);

            return $this->redirectToRoute('lender_wallet_deposit');
        }

        $paylineManager = $this->get('unilend.service.payline_manager');
        $paylineManager->handleResponse($token, $version);

        return $this->redirectToRoute('lender_wallet_deposit_result', ['paymentToken' => $token]);
    }

    /**
     * @param Wallet                        $wallet
     * @param float                         $amount
     * @param ClientsGestionMailsNotif|null $clientMailNotification
     */
    private function sendClientWithdrawalNotification(Wallet $wallet, float $amount, ?ClientsGestionMailsNotif $clientMailNotification = null): void
    {
        $numberFormatter = $this->get('number_formatter');

        $keywords = [
            'firstName'     => $wallet->getIdClient()->getPrenom(),
            'amount'        => $numberFormatter->format($amount),
            'balance'       => $numberFormatter->format($wallet->getAvailableBalance()),
            'lenderPattern' => $wallet->getWireTransferPattern()
        ];

        $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('preteur-retrait', $keywords);

        try {
            $message->setTo($wallet->getIdClient()->getEmail());
            $mailer = $this->get('mailer');
            $mailer->send($message);

            try {
                if (false === empty($clientMailNotification)) {
                    $clientMailNotification->setImmediatement(1);
                    $this->get('doctrine.orm.entity_manager')->flush($clientMailNotification);
                }
            } catch (\Exception $exception) {
                $this->get('logger')->error('Could not update client email notification to sent for client: ' . $wallet->getIdClient()->getIdClient() . ' Error: ' . $exception->getMessage(), [
                    'id_client' => $wallet->getIdClient()->getIdClient(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine()
                ]);
            }
        } catch (\Exception $exception) {
            $this->get('logger')->error('Could not send email: preteur-retrait - Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'id_client'        => $wallet->getIdClient()->getIdClient(),
                'class'            => __CLASS__,
                'function'         => __FUNCTION__,
                'file'             => $exception->getFile(),
                'line'             => $exception->getLine()
            ]);
        }
    }

    /**
     * @param Clients $client
     *
     * @return Wallet
     */
    private function getWallet(Clients $client): Wallet
    {
        return $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
    }
}
