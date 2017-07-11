<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
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
     * @param Clients $client
     *
     * @return bool
     */
    public function canBid(Clients $client)
    {
        if (
            $client->isLender()
            && Clients::STATUS_ONLINE == $client->getStatus()
            && $this->isValidated($client)
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param Clients $client
     *
     * @return int
     * @throws \Exception
     */
    public function getDiversificationLevel(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        $wallet               = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        $projectsRepository   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $numberOfCompanies    = $projectsRepository->countCompaniesLenderInvestedIn($wallet->getId());
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
     * @param Clients $client
     *
     * @return bool
     * @throws \Exception
     */
    public function hasTransferredLoans(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        /** @var \transfer $transfer */
        $transfer = $this->entityManagerSimulator->getRepository('transfer');
        /** @var \loan_transfer $loanTransfer */
        $loanTransfer                = $this->entityManagerSimulator->getRepository('loan_transfer');
        $transfersWithLenderInvolved = $transfer->select('id_client_origin = ' . $client->getIdClient() . ' OR id_client_receiver = ' . $client->getIdClient());
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
     *
     * @return string
     * @throws \Exception
     */
    public function getLenderPattern(Clients $client)
    {
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        if (null === $wallet) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        return $wallet->getWireTransferPattern();
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    public function isValidated(Clients $client)
    {
        /** @var \clients_status $lastClientStatus */
        $lastClientStatus = $this->entityManagerSimulator->getRepository('clients_status');
        $lastClientStatus->getLastStatut($client->getIdClient());

        return $lastClientStatus->status == \clients_status::VALIDATED;
    }

    /**
     * @param Clients $client
     *
     * @return null|string
     * @throws \Exception
     */
    public function getLossRate(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        $wallet                      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $loansRepository             = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans');
        $lostAmount                  = $repaymentScheduleRepository->getLostCapitalForLender($wallet->getId());
        $remainingDueCapital         = round(bcdiv($lostAmount, 100, 3), 2);
        $sumOfLoans                  = $loansRepository->sumLoansOfProjectsInRepayment($wallet);

        $lossRate = $sumOfLoans > 0 ? bcmul(round(bcdiv($remainingDueCapital, $sumOfLoans, 3), 2), 100) : null ;

        return $lossRate;
    }

    /**
     * @param Clients $client
     *
     * @return string
     */
    public function getFundsOriginTextValue(Clients $client)
    {
        switch ($client->getType()) {
            case Clients::TYPE_PERSON:
            case Clients::TYPE_PERSON_FOREIGNER:
                $setting = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Liste deroulante origine des fonds']);
                break;
            default:
                $setting = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Liste deroulante origine des fonds societe']);
                break;
        }

        $fundsOriginList = explode(';', $setting->getValue());
        $fundsOriginList = array_combine(range(1, count($fundsOriginList)), array_values($fundsOriginList));

        if (0 == $client->getFundsOrigin()) {
            return $client->getFundsOriginDetail();
        }

        return $fundsOriginList[$client->getFundsOrigin()];
    }
}
