<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Entity\{AcceptedBids, ClientsGestionMailsNotif, ClientsGestionNotifications, ClientsStatus, Echeanciers, Loans, Notifications, Projects, UnderlyingContract, Wallet};
use Unilend\SwiftMailer\{TemplateMessage, TemplateMessageProvider};

class AcceptedBidAndLoanNotificationSender
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var NotificationManager */
    private $notificationManager;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;
    /** @var TranslatorInterface */
    private $translator;
    /** @var LoggerInterface */
    private $logger;
    /** @var \NumberFormatter */
    private $numberFormatter;
    /** @var \NumberFormatter */
    private $currencyFormatter;

    /**
     * @param EntityManagerInterface  $entityManager
     * @param NotificationManager     $notificationManager
     * @param TemplateMessageProvider $messageProvider
     * @param \Swift_Mailer           $mailer
     * @param TranslatorInterface     $translator
     * @param LoggerInterface         $logger
     * @param \NumberFormatter        $numberFormatter
     * @param \NumberFormatter        $currencyFormatter
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        NotificationManager $notificationManager,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        \NumberFormatter $numberFormatter,
        \NumberFormatter $currencyFormatter
    )
    {
        $this->notificationManager = $notificationManager;
        $this->entityManager       = $entityManager;
        $this->messageProvider     = $messageProvider;
        $this->mailer              = $mailer;
        $this->translator          = $translator;
        $this->logger              = $logger;
        $this->numberFormatter     = $numberFormatter;
        $this->currencyFormatter   = $currencyFormatter;
    }

    /**
     * @param Projects $project
     */
    public function sendBidAccepted(Projects $project): void
    {
        $loanRepository         = $this->entityManager->getRepository(Loans::class);
        $acceptedBidsRepository = $this->entityManager->getRepository(AcceptedBids::class);
        $repaymentRepository    = $this->entityManager->getRepository(Echeanciers::class);
        $walletRepository       = $this->entityManager->getRepository(Wallet::class);
        $projectLenders         = $this->entityManager->getRepository(Loans::class)->getProjectLoanDetailsForEachLender($project);
        $countProjectLenders    = count($projectLenders);
        $countTreatedLenders    = 0;
        $typeEmail              = 'preteur-bid-ok';

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info($countProjectLenders . ' lenders to send email (project ' . $project->getIdProject() . ')', [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__
            ]);
        }

        foreach ($projectLenders as $lender) {
            /** @var Wallet $wallet */
            $wallet = $walletRepository->find($lender['idLender']);
            if (null === $wallet) {
                continue;
            }

            $clientStatus = $wallet->getIdClient()->getIdClientStatusHistory()->getIdStatus()->getId();
            if (false === in_array($clientStatus, ClientsStatus::GRANTED_LOGIN)) {
                continue;
            }

            try {
                $lenderLoans            = $loanRepository->findBy(['idProject' => $project, 'idLender' => $wallet->getId()], ['idTypeContract' => 'DESC']);
                $numberOfAcceptedBids   = $acceptedBidsRepository->getDistinctBidsForLenderAndProject($wallet, $project);
                $firstRepayment         = $repaymentRepository->findOneBy(['ordre' => 1, 'idProject' => $project, 'idLender' => $wallet]);

                if (empty($lenderLoans) ) {
                    throw new \Exception('Lender has no loans on project');
                }
                if (empty($firstRepayment)) {
                    throw new \Exception('Lender has no scheduled repayments on project');
                }
            } catch (\Exception $exception) {
                $this->logger->warning('An exception occurred while getting loan related information for lender and project. Exception: ' . $exception->getMessage(), [
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__,
                    'file'       => $exception->getFile(),
                    'line'       => $exception->getLine(),
                    'id_client'  => $wallet->getIdClient()->getIdClient(),
                    'id_project' => $project->getIdProject()
                ]);
                continue;
            }

            $keywords = [
                'loansDetails'                      => $this->getFormattedLoanDetails($lenderLoans),
                'pluralizedOfferTitleTranslation'   => $this->translator->transChoice('email-preteur-bid-ok_pluralized-offer-title', $numberOfAcceptedBids),
                'pluralizedOfferDetailsTranslation' => $this->translator->transChoice('email-preteur-bid-ok_pluralized-offer-details', $numberOfAcceptedBids),
                'bidWording'                        => $this->translator->transChoice('email-preteur-bid-ok_pluralized-bid', $numberOfAcceptedBids),
                'loanWording'                       => $this->translator->transChoice('email-preteur-bid-ok_pluralized-loan', $numberOfAcceptedBids)
            ];
            $keywords += $this->getCommonKeyWords($wallet, $project, $typeEmail);

            try {
                $message = $this->messageProvider->newMessage($typeEmail, $keywords);
                $message->setTo($wallet->getIdClient()->getEmail());
                $this->mailer->send($message);
            } catch (\Exception $exception){
                $this->logger->warning(
                    'Could not send email: preteur-bid-ok - Exception: ' . $exception->getMessage(), [
                    'id_mail_template' => $message->getTemplateId(),
                    'id_client'        => $wallet->getIdClient()->getIdClient(),
                    'class'            => __CLASS__,
                    'function'         => __FUNCTION__,
                    'file'             => $exception->getFile(),
                    'line'             => $exception->getLine()
                ]);
            }
        }

        $countTreatedLenders++;

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info('Loan notification emails sent to ' . $countTreatedLenders . '/' . $countProjectLenders . ' lenders  (project ' . $project->getIdProject() . ')', [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__
            ]);
        }
    }

    /**
     * @param Projects $project
     */
    public function sendLoanAccepted(Projects $project): void
    {
        $loanRepository         = $this->entityManager->getRepository(Loans::class);
        $acceptedBidsRepository = $this->entityManager->getRepository(AcceptedBids::class);
        $repaymentRepository    = $this->entityManager->getRepository(Echeanciers::class);
        $projectLenders         = $this->entityManager->getRepository(Loans::class)->getProjectLoanDetailsForEachLender($project);
        $typeEmail              = 'preteur-contrat';

        foreach ($projectLenders as $lender) {
            /** @var Wallet $wallet */
            $wallet = $this->entityManager->getRepository(Wallet::class)->find($lender['idLender']);
            if (null === $wallet) {
                continue;
            }
            $immediateNotification = $this->notificationManager->getNotif($wallet->getIdClient()->getIdClient(), Notifications::TYPE_LOAN_ACCEPTED, ClientsGestionNotifications::TYPE_NOTIFICATION_IMMEDIATE);
            if (false === $immediateNotification) {
                continue;
            }

            try {
                $lenderLoans          = $loanRepository->findBy(['idProject' => $project, 'idLender' => $wallet->getId()], ['idTypeContract' => 'DESC']);
                $numberOfAcceptedBids = $acceptedBidsRepository->getDistinctBidsForLenderAndProject($wallet, $project);
                $firstRepayment       = $repaymentRepository->findOneBy(['ordre' => 1, 'idProject' => $project, 'idLender' => $wallet]);
                $sumMonthlyPayments   = $repaymentRepository->getSumCapitalAndInterestByLenderAndProjectAndOrder($wallet, $project, 1);

                if (empty($lenderLoans) ) {
                    throw new \Exception('Lender has no loans on project');
                }
                if (empty($firstRepayment) || empty($sumMonthlyPayments)) {
                    throw new \Exception('Lender has no scheduled repayments on project');
                }
            } catch (\Exception $exception) {
                $this->logger->warning('An exception occurred while getting loan related information for lender and project. Exception: ' . $exception->getMessage(), [
                    'class'      => __CLASS__,
                    'function'   => __FUNCTION__,
                    'file'       => $exception->getFile(),
                    'line'       => $exception->getLine(),
                    'id_client'  => $wallet->getIdClient()->getIdClient(),
                    'id_project' => $project->getIdProject()
                ]);
                continue;
            }

            $numberLenderLoans = count($lenderLoans);
            $keywords          = [
                'loansDetails'       => $this->getFormattedLoanDetails($lenderLoans),
                'bidWording'         => $this->translator->transChoice('email-preteur-contrat_pluralized-bid', $numberOfAcceptedBids),
                'loanWording'        => $this->translator->transChoice('email-preteur-contrat_pluralized-loan', $numberLenderLoans),
                'contractWording'    => $this->translator->transChoice('email-preteur-contrat_pluralized-contract-available', $numberLenderLoans),
                'firstRepaymentDate' => strftime('%d %B %Y', $firstRepayment->getDateEcheance()->getTimeStamp()),
                'monthlyRepayment'   => $this->numberFormatter->format($sumMonthlyPayments / 100)
            ];
            $keywords          += $this->getCommonKeyWords($wallet, $project, $typeEmail);

            /** @var TemplateMessage $message */
            $message = $this->messageProvider->newMessage($typeEmail, $keywords);

            try {
                $message->setTo($wallet->getIdClient()->getEmail());
                $this->mailer->send($message);
            } catch (\Exception $exception) {
                $this->logger->warning('Could not send email: preteur-contrat - Exception: ' . $exception->getMessage(), [
                    'id_mail_template' => $message->getTemplateId(),
                    'id_client'        => $wallet->getIdClient()->getIdClient(),
                    'class'            => __CLASS__,
                    'function'         => __FUNCTION__,
                    'file'             => $exception->getFile(),
                    'line'             => $exception->getLine()
                ]);
            }

            $this->updateLoanClientsGestionNotification($lenderLoans);
        }
    }

    /**
     * @param Wallet   $wallet
     * @param Projects $project
     *
     * @return array
     */
    private function getCommonKeyWords(Wallet $wallet, Projects $project, string $typeMail): array
    {
        if (null === $project->getIdCompany()) {
            $companyName = '';
            $this->logger->error('No company found for project ' . $project->getIdProject(), [
                'id_project' => $project->getIdProject(),
                'class'      => __CLASS__,
                'function'   => __FUNCTION__
            ]);
        } else {
            $companyName = $project->getIdCompany()->getName();
        }

        try {
            if ($wallet->getIdClient()->isNaturalPerson()) {
                $ifpContract = $this->entityManager->getRepository(UnderlyingContract::class)->findOneBy(['label' => UnderlyingContract::CONTRACT_IFP]);
                $ifpLoan     = $this->entityManager->getRepository(Loans::class)->findOneBy(['idProject' => $project, 'idLender' => $wallet, 'idTypeContract' => $ifpContract]);

                if (false === empty($ifpLoan)) {
                    $numberOfBidsInLoanIFP = $this->entityManager->getRepository(AcceptedBids::class)->getCountAcceptedBidsByLoan($ifpLoan);

                    if (1 < $numberOfBidsInLoanIFP) {
                        $multiBidsDisclaimer  = $this->translator->trans('email-' . $typeMail . '_multi-bids-disclaimer', ['%numberOfBidsInLoanIFP%' => $numberOfBidsInLoanIFP]);
                        $multiBidsExplanation = $this->translator->trans('email-' . $typeMail . '_multi-bids-explanation', ['%numberOfBidsInLoanIFP%' => $numberOfBidsInLoanIFP]);
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error('Could not get Ifp loan details for email bid accepted. Message: ' . $exception->getMessage(), [
                'class'      => __CLASS__,
                'function'   => __FUNCTION__,
                'file'       => $exception->getFile(),
                'line'       => $exception->getLine(),
                'id_client'  => $wallet->getIdClient()->getIdClient(),
                'id_project' => $project->getIdProject()
            ]);
        }

        return [
            'firstName'                         => $wallet->getIdClient()->getPrenom(),
            'companyName'                       => $companyName,
            'multiBidsDisclaimer'               => $multiBidsDisclaimer ?? '',
            'multiBidsExplanation'              => $multiBidsExplanation ?? '',
            'lenderPattern'                     => $wallet->getWireTransferPattern()
        ];
    }

    /**
     * @param Loans[] $lenderLoans
     *
     * @return string
     */
    private function getFormattedLoanDetails(array $lenderLoans): string
    {
        $repaymentRepository = $this->entityManager->getRepository(Echeanciers::class);
        $loanDetails         = '';

        /** @var Loans $loan */
        foreach ($lenderLoans as $loan) {
            if (null !== $loan->getProject() && null !== $loan->getIdTypeContract()) {
                $firstRepayment = $repaymentRepository->findOneBy(['ordre' => 1, 'idProject' => $loan->getProject(), 'idLender' => $loan->getWallet()]);
                $amount         = round(bcdiv(bcadd($firstRepayment->getCapital(), $firstRepayment->getInterets(), 2), 100, 3), 2);
                $loanDetails    .= '<tr>
                                    <td class="td text-center">' . $this->currencyFormatter->formatCurrency($loan->getAmount() / 100, 'EUR') . '</td>
                                    <td class="td text-center">' . $this->numberFormatter->format($loan->getRate()->getMargin()) . '&nbsp;%</td>
                                    <td class="td text-center">' . $loan->getProject()->getPeriod() . ' mois</td>
                                    <td class="td text-center">' . $this->currencyFormatter->formatCurrency($amount, 'EUR') . '</td>
                                    <td class="td text-center">' . $this->translator->trans('contract-type-label_' . $loan->getIdTypeContract()->getLabel()) . '</td></tr>';
            }
        }

        return $loanDetails;
    }

    /**
     * @param Loans[] $lenderLoans
     */
    private function updateLoanClientsGestionNotification(array $lenderLoans): void
    {
        /** @var Loans $loan */
        foreach ($lenderLoans as $loan) {
            try {
                $clientMailNotifications   = $this->entityManager->getRepository(ClientsGestionMailsNotif::class);
                $immediateLoanNotification = $clientMailNotifications->findOneBy(['idLoan' => $loan->getIdLoan(), 'idClient' => $loan->getWallet()->getIdClient()->getIdClient()]);

                if (null !== $immediateLoanNotification) {
                    $immediateLoanNotification->setImmediatement(1);

                    $this->entityManager->flush($immediateLoanNotification);
                }
            } catch (\Exception $exception) {
                $this->logger->error('Could not update clients gestion mail notification for loan to sent. Message: ' . $exception->getMessage(), [
                    'class'    => __CLASS__,
                    'function' => __FUNCTION__,
                    'file'     => $exception->getFile(),
                    'line'     => $exception->getLine(),
                    'id_loan'  => $loan->getIdLoan(),
                ]);
            }
        }
    }
}
