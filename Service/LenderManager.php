<?php
namespace Unilend\Service;

use Unilend\core\Loader;

/**
 * Class LenderManager
 * @package Unilend\Service
 */
class LenderManager
{
    /** @var array */
    private $aConfig;

    public function __construct()
    {
        $this->aConfig = Loader::loadConfig();
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     *
     * @return bool
     */
    public function canBid(\lenders_accounts $oLenderAccount)
    {
        /** @var \clients_status $oClientStatus */
        $oClientStatus = Loader::loadData('clients_status');
        /** @var \clients $oClient */
        $oClient = Loader::loadData('clients');

        if ($oClient->get($oLenderAccount->id_client_owner) && $oClient->status == \clients::STATUS_ONLINE
             && $oClientStatus->getLastStatut($oLenderAccount->id_client_owner) && $oClientStatus->status == \clients_status::VALIDATED) {
            return true;
        }
        return false;
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     *
     */
    public function addToLendersAccountsStatQueue(\lenders_accounts $oLenderAccount)
    {
        /** @var \lenders_accounts_stats_queue $oLendersAccountsStatQueue */
        $oLendersAccountsStatQueue = Loader::loadData('lenders_accounts_stats_queue');

        if (false === $oLendersAccountsStatQueue->exist($oLenderAccount->id_lender_account, 'id_lender_account')) {
            $oLendersAccountsStatQueue->id_lender_account = $oLenderAccount->id_lender_account;
            $oLendersAccountsStatQueue->create();
        }
    }

    public function addLendersWithLatePaymentsToLendersAccountsStatQueue()
    {
        /** @var \lenders_account_stats $oLendersAccountsStats */
        $oLendersAccountsStats = Loader::loadData('lenders_account_stats');
        $this->addLendersToLendersAccountsStatQueue($oLendersAccountsStats->getLendersWithLatePaymentsForProjectStatus(array(\projects_status::PROBLEME, \projects_status::PROBLEME_J_X, \projects_status::RECOUVREMENT)));

    }

    public function addLendersToLendersAccountsStatQueue(array $aLenders)
    {
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = Loader::loadData('lenders_accounts');

        foreach ($aLenders as $aLender) {
            if (array_key_exists('id_lender', $aLender) && $oLenderAccount->get($aLender['id_lender'], 'id_lender_account')
                || array_key_exists('id_lender_account', $aLender) && $oLenderAccount->get($aLender['id_lender_account'], 'id_lender_account')
            ) {
                $this->addToLendersAccountsStatQueue($oLenderAccount);
            }
        }
    }

}
