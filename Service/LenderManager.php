<?php
namespace Unilend\Service;

use Unilend\core\Loader;

/**
 * Class LenderManager
 * @package Unilend\Service
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

}
