<?php

namespace Unilend\Service;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Unilend\Entity\{ClientVigilanceStatusHistory, Clients, Echeanciers, Loans, Settings, VigilanceRule, Wallet, WalletType};
use Unilend\Service\Simulator\EntityManager as EntityManagerSimulator;

class LenderManager
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerSimulator $entityManagerSimulator, EntityManagerInterface $entityManager)
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    public function canBid(Clients $client): bool
    {
        return $client->isLender() && $client->isValidated();
    }

    /**
     * @param Clients $client
     *
     * @throws Exception
     *
     * @return bool
     */
    public function hasTransferredLoans(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new Exception(sprintf('Client %s is not a Lender', $client->getIdClient()));
        }

        /** @var \transfer $transfer */
        $transfer = $this->entityManagerSimulator->getRepository('transfer');
        /** @var \loan_transfer $loanTransfer */
        $loanTransfer                = $this->entityManagerSimulator->getRepository('loan_transfer');
        $transfersWithLenderInvolved = $transfer->select('id_client_origin = ' . $client->getIdClient() . ' OR id_client_receiver = ' . $client->getIdClient());
        foreach ($transfersWithLenderInvolved as $transfer) {
            if ($loanTransfer->exist($transfer['id_transfer'], 'id_transfer')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve pattern that lender must use in bank transfer label.
     *
     * @param Clients $client
     *
     * @throws Exception
     *
     * @return string
     */
    public function getLenderPattern(Clients $client)
    {
        $wallet = $this->entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);

        if (null === $wallet) {
            throw new Exception(sprintf('Client %s is not a Lender', $client->getIdClient()));
        }

        return $wallet->getWireTransferPattern();
    }

    /**
     * @param Clients $client
     *
     * @throws Exception
     *
     * @return string|null
     */
    public function getLossRate(Clients $client)
    {
        if (false === $client->isLender()) {
            throw new Exception(sprintf('Client %s is not a Lender', $client->getIdClient()));
        }

        $wallet                      = $this->entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);
        $repaymentScheduleRepository = $this->entityManager->getRepository(Echeanciers::class);
        $loansRepository             = $this->entityManager->getRepository(Loans::class);
        $lostAmount                  = $repaymentScheduleRepository->getLostCapitalForLender($wallet->getId());
        $remainingDueCapital         = round(bcdiv($lostAmount, 100, 3), 2);
        $sumOfLoans                  = $loansRepository->sumLoansOfProjectsInRepayment($wallet);

        return $sumOfLoans > 0 ? bcmul(round(bcdiv($remainingDueCapital, $sumOfLoans, 3), 2), 100) : null;
    }

    /**
     * @param int $clientType
     *
     * @return array
     */
    public function getFundsOrigins(int $clientType): array
    {
        switch ($clientType) {
            case Clients::TYPE_PERSON:
            case Clients::TYPE_PERSON_FOREIGNER:
                $settingName = 'Liste deroulante origine des fonds';

                break;
            default:
                $settingName = 'Liste deroulante origine des fonds societe';

                break;
        }

        $fundsOriginList = $this->entityManager
            ->getRepository(Settings::class)
            ->findOneBy(['type' => $settingName])
            ->getValue()
        ;
        $fundsOriginList = explode(';', $fundsOriginList);

        return array_combine(range(1, count($fundsOriginList)), array_values($fundsOriginList));
    }

    /**
     * @param Clients $client
     *
     * @return bool
     *
     * @throws Exception
     */
    public function needUpdatePersonalData(Clients $client): bool
    {
        if (false === $client->isLender()) {
            throw new InvalidArgumentException(sprintf('Client %s is not a Lender', $client->getIdClient()));
        }

        $clientVigilanceStatus = $this->entityManager->getRepository(ClientVigilanceStatusHistory::class)->findOneBy(['client' => $client], ['added' => 'DESC', 'id' => 'DESC']);

        $currentVigilanceStatus = VigilanceRule::VIGILANCE_STATUS_LOW;
        if ($clientVigilanceStatus) {
            $currentVigilanceStatus = $clientVigilanceStatus->getVigilanceStatus();
        }

        $personalDataUpdated = $client->getPersonalDataUpdated() ?? $client->getAdded();
        $interval            = $personalDataUpdated->diff(new DateTime());
        $intervalInMonths    = $interval->y * 12 + $interval->m;

        if (VigilanceRule::VIGILANCE_STATUS_HIGH === $currentVigilanceStatus) {
            $needUpdatePersonalData = $intervalInMonths >= 6;
        } else {
            $needUpdatePersonalData = $intervalInMonths >= 12;
        }

        return $needUpdatePersonalData;
    }
}
