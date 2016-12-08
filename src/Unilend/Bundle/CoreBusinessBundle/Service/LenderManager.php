<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Entity\AcceptationsLegalDocs;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Settings;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

/**
 * Class LenderManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class LenderManager
{
    /** @var EntityManager */
    private $oEntityManager;
    /** @var  \Doctrine\ORM\EntityManager */
    private $em;

    public function __construct
    (
        EntityManager $oEntityManager,
        \Doctrine\ORM\EntityManager $em
    )
    {
        $this->oEntityManager = $oEntityManager;
        $this->em             = $em;
    }

    /**
     * @param \lenders_accounts $oLenderAccount
     *
     * @return bool
     */
    public function canBid(\lenders_accounts $oLenderAccount)
    {
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');

        if ($oClient->get($oLenderAccount->id_client_owner) && $oClient->status == \clients::STATUS_ONLINE
            && $this->isValidated($oClient)
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


    public function hasTransferredLoans(\lenders_accounts $lender)
    {
        /** @var \transfer $transfer */
        $transfer = $this->oEntityManager->getRepository('transfer');
        /** @var \loan_transfer $loanTransfer */
        $loanTransfer = $this->oEntityManager->getRepository('loan_transfer');
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
     */
    public function saveLenderTermsOfUse(Clients $client)
    {
        if (in_array($client->getType(), [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER])) {
            $type = 'Lien conditions generales inscription preteur particulier';
        } else {
            $type = 'Lien conditions generales inscription preteur societe';
        }

        /** @var Settings $settingsEntity */
        $settingsEntity =  $this->em->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => $type]);

        $termsOfUse = new AcceptationsLegalDocs();
        $termsOfUse->setIdLegalDoc($settingsEntity->getValue());
        $termsOfUse->setIdClient($client->getIdClient());

        $this->em->persist($client);
        $this->em->flush();
    }

    /**
     * @param \clients $client
     * @return bool
     */
    public function isValidated(\clients $client)
    {
        /** @var \clients_status $lastClientStatus */
        $lastClientStatus = $this->oEntityManager->getRepository('clients_status');
        $lastClientStatus->getLastStatut($client->id_client);
        return $lastClientStatus->status == \clients_status::VALIDATED;
    }
}
