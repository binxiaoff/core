<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

/**
 * Class LenderManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class LenderManager
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManager */
    private $entityManager;

    public function __construct
    (
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager
    )
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     *
     * @return bool
     */
    public function canBid(\lenders_accounts $oLenderAccount)
    {
        /** @var \clients $oClient */
        $oClient = $this->entityManagerSimulator->getRepository('clients');

        if ($oClient->get($oLenderAccount->id_client_owner) && $oClient->status == Clients::STATUS_ONLINE
            && $this->isValidated($oClient)
        ) {
            return true;
        }
        return false;
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
        $autoBid = $this->entityManagerSimulator->getRepository('autobid');

        if ($projectRates == null) {
            /** @var \project_rate_settings $projectRateSettings */
            $projectRateSettings = $this->entityManagerSimulator->getRepository('project_rate_settings');
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


    /**
     * @param \lenders_accounts $lender
     * @return bool
     */
    public function hasTransferredLoans(\lenders_accounts $lender)
    {
        /** @var \transfer $transfer */
        $transfer = $this->entityManagerSimulator->getRepository('transfer');
        /** @var \loan_transfer $loanTransfer */
        $loanTransfer = $this->entityManagerSimulator->getRepository('loan_transfer');
        $transfersWithLenderInvolved = $transfer->select('id_client_origin = ' . $lender->id_client_owner . ' OR id_client_receiver = ' . $lender->id_client_owner);

        foreach ($transfersWithLenderInvolved as $transfer) {
            if ($loanTransfer->exist($transfer['id_transfer'], 'id_transfer')){
               return true;
            }
        }
        return false;
    }

    /**
     * Retrieve pattern that lender must use in bank transfer label
     * @param Clients $client
     * @return string
     */
    public function getLenderPattern(Clients $client)
    {
        $oToolkit = new \ficelle();

        return mb_strtoupper(
            str_pad($client->getIdClient(), 6, 0, STR_PAD_LEFT) .
            substr($oToolkit->stripAccents($client->getPrenom()), 0, 1) .
            $oToolkit->stripAccents($client->getNom())
        );
    }

    /**
     * @param \clients $client
     * @return bool
     */
    public function isValidated(\clients $client)
    {
        /** @var \clients_status $lastClientStatus */
        $lastClientStatus = $this->entityManagerSimulator->getRepository('clients_status');
        $lastClientStatus->getLastStatut($client->id_client);
        return $lastClientStatus->status == \clients_status::VALIDATED;
    }

    /**
     * @param \lenders_accounts $lender
     *
     * @return null|string
     */
    public function getLossRate(\lenders_accounts $lender)
    {
        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $lostAmount                  = $repaymentScheduleRepository->getLostCapitalForLender($lender->id_lender_account);
        $remainingDueCapital         = round(bcdiv($lostAmount, 100, 3), 2);
        $sumOfLoans                  = $lender->sumLoansOfProjectsInRepayment($lender->id_lender_account);

        $lossRate = $sumOfLoans > 0 ? bcmul(round(bcdiv($remainingDueCapital, $sumOfLoans, 3), 2), 100) : null ;

        return $lossRate;
    }
}
