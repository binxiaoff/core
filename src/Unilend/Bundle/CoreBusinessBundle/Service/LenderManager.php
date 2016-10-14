<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

/**
 * Class LenderManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class LenderManager
{
    /** @var EntityManager */
    private $oEntityManager;

    /** @var  ProductManager */
    private $productManager;

    /** @var  ContractManager */
    private $contractManager;

    public function __construct(EntityManager $oEntityManager, ProductManager $productManager, ContractManager $contractManager)
    {
        $this->oEntityManager           = $oEntityManager;
        $this->productManager           = $productManager;
        $this->contractManager          = $contractManager;
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
            && $oClientStatus->getLastStatut($oLenderAccount->id_client_owner) && $oClientStatus->status == \clients_status::VALIDATED
        ) {
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

    /**
     * @param \lenders_accounts $lenderAccount
     * @return int
     */
    public function getDiversificationLevel(\lenders_accounts $lenderAccount)
    {
        $numberOfCompanies    = $lenderAccount->countCompaniesLenderInvestedIn($lenderAccount->id_lender_account);
        $diversificationLevel = 0;

        if ($numberOfCompanies === 0) {
            $diversificationLevel = 0;
        }

        if ($numberOfCompanies >= 1 && $numberOfCompanies <= 19) {
            $diversificationLevel = 1;
        }

        if ($numberOfCompanies >= 20 && $numberOfCompanies <= 49) {
            $diversificationLevel = 2;
        }

        if ($numberOfCompanies >= 50 && $numberOfCompanies <= 79) {
            $diversificationLevel = 3;
        }

        if ($numberOfCompanies >= 80 && $numberOfCompanies <= 119) {
            $diversificationLevel = 4;
        }

        if ($numberOfCompanies >= 120) {
            $diversificationLevel = 5;
        }

        return $diversificationLevel;
    }

    /**
     * @param \lenders_accounts $lender
     * @param null              $projectRates optional, for optimize the performance.
     *
     * @return array
     */
    public function getBadAutoBidSettings(\lenders_accounts $lender, $projectRates = null)
    {
        /** @var \autobid $autoBid */
        $autoBid = $this->oEntityManager->getRepository('autobid');

        if ($projectRates == null) {
            /** @var \project_rate_settings $projectRateSettings */
            $projectRateSettings = $this->oEntityManager->getRepository('project_rate_settings');
            $projectRates        = $projectRateSettings->getSettings();
        }

        $projectMaxRate = [];
        foreach ($projectRates as $rate) {
            $projectMaxRate[$rate['id_period']][$rate['evaluation']] = $rate['rate_max'];
        }

        $autoBidSettings = $autoBid->getSettings($lender->id_lender_account);
        $badSettings     = [];
        foreach ($autoBidSettings as $setting) {
            if (false === isset($projectMaxRate[$setting['id_period']][$setting['evaluation']])) {
                continue;
            }
            if (bccomp($setting['rate_min'], $projectMaxRate[$setting['id_period']][$setting['evaluation']], 1) > 0) {
                $badSettings[] = [
                    'period_min'       => $setting['period_min'],
                    'period_max'       => $setting['period_max'],
                    'evaluation'       => $setting['evaluation'],
                    'rate_min_autobid' => $setting['rate_min'],
                    'rate_max_project' => $projectMaxRate[$setting['id_period']][$setting['evaluation']],
                ];
            }
        }

        return $badSettings;
    }

    public function isAutobidEligible(\lenders_accounts $lender)
    {
        foreach ($this->productManager->getAvailableProducts(true) as $product) {
            $autobidContracts = $this->contractManager->getAutobidEligibleContracts($this->contractManager->getProductAvailableContracts($product));
            foreach ($autobidContracts as $contract) {
                if ($this->contractManager->isLenderEligibleForContract($lender, $contract)){
                    return true;
                }
            }
        }

        return false;
    }
}
