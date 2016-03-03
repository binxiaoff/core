<?php
namespace Unilend\Service;

use Unilend\core\Loader;

/**
 * Class LenderManager
 * @package Unilend\Service
 */
class LenderManager
{

    /**
     * @param \lenders_accounts $oLenderAccount
     *
     * @return bool
     */
    public function canBid(\lenders_accounts $oLenderAccount)
    {
        /** @var \clients_status $oClientStatus */
        $oClientStatus = Loader::loadData('clients_status');
        if ($oClientStatus->getLastStatut($oLenderAccount->id_client_owner) && $oClientStatus->status == \clients_status::VALIDATED) {
            return true;
        }
        return false;
    }

}
