<?php
namespace Unilend\Bundle\FrontBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Entity\Notifications;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Service\AutoBidSettingsManager;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\RouterInterface;
use Unilend\core\Loader;

class NotificationDisplayManager
{
    /** @var EntityManager $entityManager */
    private $entityManager;
    /** @var AutoBidSettingsManager */
    private $autoBidSettingsManager;
    /** @var TranslatorInterface */
    private $translator;
    /** @var RouterInterface */
    private $router;

    public function __construct(EntityManager $entityManager, AutoBidSettingsManager $autoBidSettingsManager, TranslatorInterface $translator, RouterInterface $router)
    {
        $this->entityManager          = $entityManager;
        $this->autoBidSettingsManager = $autoBidSettingsManager;
        $this->translator             = $translator;
        $this->router                 = $router;
    }

    /**
     * @param \lenders_accounts $lender
     * @return array
     */
    public function getLastLenderNotifications(\lenders_accounts $lender)
    {
        return $this->getLenderNotifications($lender, 1, 20);
    }

    /**
     * @param \lenders_accounts $lender
     * @param int               $offset
     * @param int               $length
     *
     * @return array
     */
    public function getLenderNotifications(\lenders_accounts $lender, $offset, $length)
    {
        return $this->getLenderNotificationsDetail($lender->id_client_owner, null, $offset, $length);
    }

    /**
     * @param int      $clientId
     * @param int      $projectId
     * @param null|int $offset
     * @param null|int $length
     *
     * @return array
     */
    public function getLenderNotificationsByProject($clientId, $projectId, $offset = null, $length = null)
    {
        return $this->getLenderNotificationsDetail($clientId, $projectId, $offset, $length);
    }

    /**
     * @param int  $clientId
     * @param null|int $projectId
     * @param null|int $offset
     * @param null|int $length
     *
     * @return array
     */
    private function getLenderNotificationsDetail($clientId, $projectId = null, $offset = null, $length = null)
    {
        /** @var \accepted_bids $acceptedBid */
        $acceptedBid = $this->entityManager->getRepository('accepted_bids');
        /** @var \autobid $autobid */
        $autobid = $this->entityManager->getRepository('autobid');
        /** @var \bids $bid */
        $bid = $this->entityManager->getRepository('bids');
        /** @var \clients $client */
        $client = $this->entityManager->getRepository('clients');
        /** @var \companies $company */
        $company = $this->entityManager->getRepository('companies');
        /** @var \notifications $notifications */
        $notifications = $this->entityManager->getRepository('notifications');
        /** @var \projects $project */
        $project = $this->entityManager->getRepository('projects');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var \lenders_accounts $lender */
        $lender = $this->entityManager->getRepository('lenders_accounts');
        $lender->get($clientId, 'id_client_owner');

        $result = [];

        $where        = (true === empty($projectId)) ? 'id_lender = ' . $lender->id_lender_account : 'id_lender = ' . $lender->id_lender_account . ' AND id_project = ' . $projectId;
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
                    $client->get($lender->id_client_owner);

                    $type    = 'offer-accepted';
                    $image   = 'circle-accepted';
                    $title   = $this->translator->trans('lender-notifications_autolend-first-activation-title');
                    $content = $this->translator->trans('lender-notifications_autolend-first-activation-content', [
                        '%activationDate%' => $this->autoBidSettingsManager->getActivationTime($client)->format('G\hi'),
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
