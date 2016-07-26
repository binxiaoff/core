<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

/**
 * Class LenderManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class LenderManager
{
    /** @var EntityManager  */
    private $oEntityManager;

    public function __construct(EntityManager $oEntityManager)
    {
        $this->oEntityManager = $oEntityManager;
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     *
     * @return bool
     */
    public function canBid(\lenders_accounts $oLenderAccount)
    {
        /** @var \clients_status $oClientStatus */
        $oClientStatus = $this->oEntityManager->getRepository('clients_status');
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');

        if ($oClient->get($oLenderAccount->id_client_owner) && $oClient->status == \clients::STATUS_ONLINE
             && $oClientStatus->getLastStatut($oLenderAccount->id_client_owner) && $oClientStatus->status == \clients_status::VALIDATED) {
            return true;
        }
        return false;
    }

    /**
     * @param array $aLenders
     */
    public function addLendersToLendersAccountsStatQueue(array $aLenders)
    {
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \lenders_accounts_stats_queue $oLendersAccountsStatsQueue */
        $oLendersAccountsStatsQueue = $this->oEntityManager->getRepository('lenders_accounts_stats_queue');

        foreach ($aLenders as $aLender) {
            if (array_key_exists('id_lender', $aLender) && $oLenderAccount->get($aLender['id_lender'], 'id_lender_account')
                || array_key_exists('id_lender_account', $aLender) && $oLenderAccount->get($aLender['id_lender_account'], 'id_lender_account')
            ) {
                $oLendersAccountsStatsQueue->addLenderToQueue($oLenderAccount);
            }
        }
    }

    public function getBadAutoBidSettings(\lenders_accounts $lender)
    {
        /** @var \project_rate_settings $projectRateSettings */
        $projectRateSettings = $this->oEntityManager->getRepository('project_rate_settings');
        /** @var \autobid $autoBid */
        $autoBid = $this->oEntityManager->getRepository('autobid');

        $rateTable      = $projectRateSettings->getSettings();
        $projectMaxRate = [];
        foreach ($rateTable as $rate) {
            $projectMaxRate[$rate['id_period']][$rate['evaluation']] = $rate['rate_max'];
        }

        $autoBidSettings = $autoBid->getSettings($lender->id_lender_account);
        $badSettings = [];
        foreach ($autoBidSettings as $setting) {
            if (false === isset($projectMaxRate[$setting['id_period']][$setting['evaluation']])) {
                continue;
            }
            if (bccomp($setting['rate_min'], $projectMaxRate[$setting['id_period']][$setting['evaluation']], 1) > 0) {
                $badSettings[] = [
                    'period_min'       => $setting['min'],
                    'period_max'       => $setting['max'],
                    'evaluation'       => $setting['evaluation'],
                    'rate_min_autobid' => $setting['rate_min'],
                    'rate_max_project' => $projectMaxRate[$setting['id_period']][$setting['evaluation']],
                ];
            }
        }

        return $badSettings;
    }
}
