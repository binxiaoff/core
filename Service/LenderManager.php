<?php
namespace Unilend\Service;

use Unilend\core\Loader;

/**
 * Class LenderManager
 * @package Unilend\Service
 */
class LenderManager extends DataService
{

    /**
     * @param \lenders_accounts $oLenderAccount
     *
     * @return bool
     */
    public function canBid(\lenders_accounts $oLenderAccount)
    {
        /** @var \clients_status $oClientStatus */
        $oClientStatus = $this->loadData('clients_status');
        /** @var \clients $oClient */
        $oClient = $this->loadData('clients');

        if ($oClient->get($oLenderAccount->id_client_owner) && $oClient->status == \clients::STATUS_ONLINE
             && $oClientStatus->getLastStatut($oLenderAccount->id_client_owner) && $oClientStatus->status == \clients_status::VALIDATED) {
            return true;
        }
        return false;
    }

}
