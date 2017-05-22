<?php
namespace Unilend\Bundle\FrontBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Notifications;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\CoreBusinessBundle\Service\AutoBidSettingsManager;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\RouterInterface;
use Unilend\core\Loader;

class NotificationDisplayManager
{
    /** @var EntityManagerSimulator $entityManagerSimulator */
    private $entityManagerSimulator;
    /** @var AutoBidSettingsManager */
    private $autoBidSettingsManager;
    /** @var TranslatorInterface */
    private $translator;
    /** @var RouterInterface */
    private $router;
    /** @var  EntityManager */
    private $entityManager;

    /**
     * NotificationDisplayManager constructor.
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param AutoBidSettingsManager $autoBidSettingsManager
     * @param TranslatorInterface    $translator
     * @param RouterInterface        $router
     * @param EntityManager          $entityManager
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        AutoBidSettingsManager $autoBidSettingsManager,
        TranslatorInterface $translator,
        RouterInterface $router,
        EntityManager $entityManager
    ) {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->autoBidSettingsManager = $autoBidSettingsManager;
        $this->translator             = $translator;
        $this->router                 = $router;
        $this->entityManager          = $entityManager;
    }

    /**
     * @param Clients $client
     *
     * @return array
     * @throws \Exception
     */
    public function getLastLenderNotifications(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        return $this->getLenderNotifications($client, 1, 20);
    }

    /**
     * @param Clients $client
     * @param int     $offset
     * @param int     $length
     *
     * @return array
     * @throws \Exception
     */
    public function getLenderNotifications(Clients $client, $offset, $length)
    {
        return $this->getLenderNotificationsDetail($client, null, $offset, $length);
    }

    /**
     * @param Clients      $client
     * @param Projects     $project
     * @param null|int $offset
     * @param null|int $length
     *
     * @return array
     */
    public function getLenderNotificationsByProject(Clients $client, Projects $project, $offset = null, $length = null)
    {
        return $this->getLenderNotificationsDetail($client, $project->getIdProject(), $offset, $length);
    }

    /**
     * @param Clients       $client
     * @param integer|null  $projectId
     * @param int           $offset
     * @param int           $length
     *
     * @return array
     * @throws \Exception
     */
    private function getLenderNotificationsDetail(Clients $client, $projectId = null, $offset = null, $length = null)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        /** @var \accepted_bids $acceptedBid */
        $acceptedBid = $this->entityManagerSimulator->getRepository('accepted_bids');
        /** @var \autobid $autobid */
        $autobid = $this->entityManagerSimulator->getRepository('autobid');
        /** @var \bids $bid */
        $bid = $this->entityManagerSimulator->getRepository('bids');
        /** @var \companies $company */
        $company = $this->entityManagerSimulator->getRepository('companies');
        /** @var \notifications $notifications */
        $notifications = $this->entityManagerSimulator->getRepository('notifications');
        /** @var \projects $project */
        $project = $this->entityManagerSimulator->getRepository('projects');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $result = [];

        $where        = (null === $projectId) ? 'id_lender = ' . $wallet->getId() : 'id_lender = ' . $wallet->getId() . ' AND id_project = ' . $projectId;
        $start        = (true === empty($offset)) ? '' : $offset - 1;
        $numberOfRows = (true === empty($length)) ? '' : $length;

        foreach ($notifications->select($where, 'added DESC', $start, $numberOfRows) as $notification) {
            $type    = ''; // Style of title (account, offer-accepted, offer-rejected, remboursement)
            $title   = ''; // Title (translation)
            $content = ''; // Main message (translation)
            $image   = ''; // SVG icon (icons/notification)

            switch ($notification['type']) {
                case Notifications::TYPE_BID_REJECTED:
                    $bid->get($notification['id_bid'], 'id_bid');
                    $project->get($notification['id_project'], 'id_project');
                    $company->get($project->id_company, 'id_company');

                    $type    = 'offer-rejected';
                    $image   = 'offer-rejected';
                    $title   = 'rejected-bid-title';
                    $content = $bid->id_autobid == 0 ? 'rejected-bid-content' : 'rejected-autobid-content';

                    if ($bid->amount != $notification['amount']) {
                        $title   = 'partially-' . $title;
                        $content = 'partially-' . $content;
                    }

                    $title   = $this->translator->trans('lender-notifications_' . $title);
                    $content = $this->translator->trans('lender-notifications_' . $content, [
                        '%rate%'       => $ficelle->formatNumber($bid->rate, 1),
                        '%amount%'     => $ficelle->formatNumber($notification['amount'] / 100, 0),
                        '%projectUrl%' => $this->router->generate('project_detail', ['projectSlug' => $project->slug]),
                        '%company%'    => $company->name
                    ]);
                    break;
                case Notifications::TYPE_REPAYMENT:
                    $project->get($notification['id_project'], 'id_project');
                    $company->get($project->id_company, 'id_company');

                    $type    = 'remboursement';
                    $image   = 'remboursement';
                    $title   = $this->translator->trans('lender-notifications_repayment-title');
                    $content = $this->translator->trans('lender-notifications_repayment-content', [
                        '%amount%'     => $ficelle->formatNumber($notification['amount'] / 100, 2),
                        '%projectUrl%' => $this->router->generate('project_detail', ['projectSlug' => $project->slug]),
                        '%company%'    => $company->name
                    ]);
                    break;
                case Notifications::TYPE_BID_PLACED:
                    $bid->get($notification['id_bid'], 'id_bid');
                    $project->get($notification['id_project'], 'id_project');
                    $company->get($project->id_company, 'id_company');

                    $type    = 'offer';
                    $image   = 'remboursement';
                    $title   = $this->translator->trans('lender-notifications_placed-bid-title');
                    $content = 'placed-bid-content';

                    if ($bid->id_autobid > 0) {
                        $autobid->get($bid->id_autobid);
                        $content = 'placed-autobid-content';
                    }

                    $content = $this->translator->trans('lender-notifications_' . $content, [
                        '%rate%'       => $ficelle->formatNumber($bid->rate, 1),
                        '%amount%'     => $ficelle->formatNumber($notification['amount'] / 100, 0),
                        '%projectUrl%' => $this->router->generate('project_detail', ['projectSlug' => $project->slug]),
                        '%company%'    => $company->name,
                        '%minRate%'    => $bid->id_autobid > 0 ? $ficelle->formatNumber($autobid->rate_min, 1) : ''
                    ]);
                    break;
                case Notifications::TYPE_LOAN_ACCEPTED:
                    $bid->get($notification['id_bid'], 'id_bid');
                    $project->get($notification['id_project'], 'id_project');
                    $company->get($project->id_company, 'id_company');

                    $type    = 'offer-accepted';
                    $image   = 'offer-accepted';
                    $title   = $this->translator->trans('lender-notifications_accepted-loan-title');
                    $content = $this->translator->trans('lender-notifications_accepted-loan-content', [
                        '%rate%'       => $ficelle->formatNumber($bid->rate, 1),
                        '%amount%'     => $ficelle->formatNumber($acceptedBid->getAcceptedAmount($bid->id_bid), 0),
                        '%projectUrl%' => $this->router->generate('project_detail', ['projectSlug' => $project->slug]),
                        '%company%'    => $company->name
                    ]);
                    break;
                case Notifications::TYPE_BANK_TRANSFER_CREDIT:
                    $type    = 'remboursement';
                    $image   = 'account-borne';
                    $title   = $this->translator->trans('lender-notifications_bank-transfer-credit-title');
                    $content = $this->translator->trans('lender-notifications_bank-transfer-credit-content', [
                        '%amount%' => $ficelle->formatNumber($notification['amount'] / 100, 2)
                    ]);
                    break;
                case Notifications::TYPE_CREDIT_CARD_CREDIT:
                    $type  = 'remboursement';
                    $image = 'account-cb';
                    $title   = $this->translator->trans('lender-notifications_credit-card-credit-title');
                    $content = $this->translator->trans('lender-notifications_credit-card-credit-content', [
                        '%amount%' => $ficelle->formatNumber($notification['amount'] / 100, 2)
                    ]);
                    break;
                case Notifications::TYPE_DEBIT:
                    $type    = 'remboursement';
                    $image   = 'account-withdraw';
                    $title   = $this->translator->trans('lender-notifications_withdraw-title');
                    $content = $this->translator->trans('lender-notifications_withdraw-content', [
                        '%amount%' => $ficelle->formatNumber($notification['amount'] / 100, 2)
                    ]);
                    break;
                case Notifications::TYPE_NEW_PROJECT:
                    $project->get($notification['id_project'], 'id_project');

                    $type    = 'remboursement';
                    $image   = 'project';
                    $title   = $this->translator->trans('lender-notifications_new-project-title');
                    $content = $this->translator->trans('lender-notifications_new-project-content', [
                        '%projectUrl%'      => $this->router->generate('project_detail', ['projectSlug' => $project->slug]),
                        '%projectTitle%'    => $project->title,
                        '%publicationDate%' => date('d/m/Y', strtotime($project->date_publication)),
                        '%publicationTime%' => date('H:i', strtotime($project->date_publication)),
                        '%amount%'          => $ficelle->formatNumber($project->amount, 0),
                        '%duration%'        => $project->period
                    ]);
                    break;
                case Notifications::TYPE_PROJECT_PROBLEM:
                    $project->get($notification['id_project'], 'id_project');
                    $company->get($project->id_company);

                    $type    = 'account';
                    $image   = 'entreprise-inprogress';
                    $title   = $this->translator->trans('lender-notifications_late-repayment-title');
                    $content = $this->translator->trans('lender-notifications_late-repayment-content', [
                        '%projectUrl%' => $this->router->generate('project_detail', ['projectSlug' => $project->slug]) . '#project-section-info',
                        '%company%'    => $company->name
                    ]);
                    break;
                case Notifications::TYPE_PROJECT_PROBLEM_REMINDER:
                    $project->get($notification['id_project'], 'id_project');
                    $company->get($project->id_company);

                    $type    = 'account';
                    $image   = 'entreprise-inprogress';
                    $title   = $this->translator->trans('lender-notifications_late-repayment-x-days-title');
                    $content = $this->translator->trans('lender-notifications_late-repayment-x-days-content', [
                        '%projectUrl%' => $this->router->generate('project_detail', ['projectSlug' => $project->slug]) . '#project-section-info',
                        '%company%'    => $company->name
                    ]);
                    break;
                case Notifications::TYPE_PROJECT_RECOVERY:
                    $project->get($notification['id_project'], 'id_project');
                    $company->get($project->id_company);

                    $type    = 'account';
                    $image   = 'entreprise-recovery';
                    $title   = $this->translator->trans('lender-notifications_recovery-title');
                    $content = $this->translator->trans('lender-notifications_recovery-content', [
                        '%projectUrl%' => $this->router->generate('project_detail', ['projectSlug' => $project->slug]) . '#project-section-info',
                        '%company%'    => $company->name
                    ]);
                    break;
                case Notifications::TYPE_PROJECT_PRECAUTIONARY_PROCESS:
                    $project->get($notification['id_project'], 'id_project');
                    $company->get($project->id_company);

                    $type    = 'account';
                    $image   = 'entreprise-palace';
                    $title   = $this->translator->trans('lender-notifications_precautionary-process-title');
                    $content = $this->translator->trans('lender-notifications_precautionary-process-content', [
                        '%projectUrl%' => $this->router->generate('project_detail', ['projectSlug' => $project->slug]) . '#project-section-info',
                        '%company%'    => $company->name
                    ]);
                    break;
                case Notifications::TYPE_PROJECT_RECEIVERSHIP:
                    $project->get($notification['id_project'], 'id_project');
                    $company->get($project->id_company);

                    $type    = 'account';
                    $image   = 'entreprise-palace';
                    $title   = $this->translator->trans('lender-notifications_receivership-title');
                    $content = $this->translator->trans('lender-notifications_receivership-content', [
                        '%projectUrl%' => $this->router->generate('project_detail', ['projectSlug' => $project->slug]) . '#project-section-info',
                        '%company%'    => $company->name
                    ]);
                    break;
                case Notifications::TYPE_PROJECT_COMPULSORY_LIQUIDATION:
                    $project->get($notification['id_project'], 'id_project');
                    $company->get($project->id_company);

                    $type    = 'offer-rejected';
                    $image   = 'entreprise-rejected';
                    $title   = $this->translator->trans('lender-notifications_compulsory-liquidation-title');
                    $content = $this->translator->trans('lender-notifications_compulsory-liquidation-content', [
                        '%projectUrl%' => $this->router->generate('project_detail', ['projectSlug' => $project->slug]) . '#project-section-info',
                        '%company%'    => $company->name
                    ]);
                    break;
                case Notifications::TYPE_PROJECT_FAILURE:
                    $project->get($notification['id_project'], 'id_project');
                    $company->get($project->id_company);

                    $type    = 'offer-rejected';
                    $image   = 'entreprise-rejected';
                    $title   = $this->translator->trans('lender-notifications_company-failure-title');
                    $content = $this->translator->trans('lender-notifications_company-failure-content', [
                        '%projectUrl%' => $this->router->generate('project_detail', ['projectSlug' => $project->slug]) . '#project-section-info',
                        '%company%'    => $company->name
                    ]);
                    break;
                case Notifications::TYPE_AUTOBID_BALANCE_INSUFFICIENT:
                    $type    = 'offer-rejected';
                    $image   = 'account-noauto';
                    $title   = $this->translator->trans('lender-notifications_autolend-insufficient-balance-title');
                    $content = $this->translator->trans('lender-notifications_autolend-insufficient-balance-content', [
                        '%url%' => $this->router->generate('lender_wallet_deposit')
                    ]);
                    break;
                case Notifications::TYPE_AUTOBID_BALANCE_LOW:
                    $type    = 'account';
                    $image   = 'account-lowbalance';
                    $title   = $this->translator->trans('lender-notifications_autolend-low-balance-title');
                    $content = $this->translator->trans('lender-notifications_autolend-low-balance-content', [
                        '%url%' => $this->router->generate('lender_wallet_deposit')
                    ]);
                    break;
                case Notifications::TYPE_AUTOBID_FIRST_ACTIVATION:
                    $type    = 'offer-accepted';
                    $image   = 'circle-accepted';
                    $title   = $this->translator->trans('lender-notifications_autolend-first-activation-title');
                    $content = $this->translator->trans('lender-notifications_autolend-first-activation-content', [
                        '%activationDate%' => $this->autoBidSettingsManager->getActivationTime($wallet->getIdClient())->format('G\hi'),
                        '%settingsUrl%'    => $this->router->generate('autolend')
                    ]);
                    break;
            }

            $added = new \DateTime($notification['added']);

            $result[] = [
                'id'       => $notification['id_notification'],
                'type'     => $type,
                'title'    => $title,
                'datetime' => $added,
                'iso-8601' => $added->format('c'),
                'content'  => $content,
                'image'    => $image,
                'status'   => $notification['status'] == Notifications::STATUS_READ ? 'read' : 'unread'
            ];
        }

        return $result;
    }
}
