<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;

class recouvreurController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);

        $this->menu_admin = 'emprunteurs';
    }

    public function _liste()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager       = $this->get('doctrine.orm.entity_manager');
        $walletDebtCollector = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletType')
            ->findOneBy(['label' => WalletType::DEBT_COLLECTOR]);
        $debtCollectors      = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')
            ->findBy(['idType' => $walletDebtCollector]);
        $data                = [];

        foreach ($debtCollectors as $debtCollector) {
            /** @var Clients $client */
            $client = $debtCollector->getIdClient();
            $data[] = [
                'client'            => $client,
                'entrustedProjects' => $this->getEntrustedProjectData($client)
            ];
        }

        $this->render(null, ['debtCollectors' => $data]);
    }

    public function _details_recouvreur()
    {
        if (false === empty($this->params[0])) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager        = $this->get('doctrine.orm.entity_manager');
            $clientId             = filter_var($this->params[0], FILTER_VALIDATE_INT);
            $wallet               = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idClient' => $clientId]);
            $walletBalanceHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory');
            $data                 = [];

            if ($wallet && WalletType::DEBT_COLLECTOR === $wallet->getIdType()->getLabel()) {
                $firstOperation = $walletBalanceHistory->findOneBy(['idWallet' => $wallet], ['added' => 'ASC']);
                $data           = [
                    'address'           => $entityManager->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->findOneBy(['idClient' => $wallet->getIdClient()]),
                    'entrustedProjects' => $this->getEntrustedProjectData($wallet->getIdClient()),
                    'operationHistory'  => null !== $firstOperation ? $walletBalanceHistory->getDebtCollectorWalletOperations($wallet, $firstOperation->getAdded(), new \DateTime()) : [],
                    'availableBalance'  => $wallet->getAvailableBalance()
                ];
            }
            $this->render(null, ['debtCollectorData' => $data]);
        }
    }

    /**
     * @param Clients|null $debtCollector
     *
     * @return array
     */
    private function getEntrustedProjectData(Clients $debtCollector = null)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager         = $this->get('doctrine.orm.entity_manager');
        $debtCollectionMission = $entityManager->getRepository('UnilendCoreBusinessBundle:DebtCollectionMission');

        return [
            'ongoing' => $debtCollectionMission->getCountMissionsByDebtCollector($debtCollector),
            'total'   => $debtCollectionMission->getCountMissionsByDebtCollector($debtCollector, true),
            'amount'  => $debtCollectionMission->getAmountMissionsByDebtCollector($debtCollector)
        ];
    }
}
