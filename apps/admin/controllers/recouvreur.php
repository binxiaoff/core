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
        $data = [];
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager       = $this->get('doctrine.orm.entity_manager');
        $walletDebtCollector = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletType')
            ->findOneBy(['label' => WalletType::DEBT_COLLECTOR]);

        $debtCollectors = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')
            ->findBy(['idType' => $walletDebtCollector]);

        $debtCollectionMission = $entityManager->getRepository('UnilendCoreBusinessBundle:DebtCollectionMission');
        foreach ($debtCollectors as $debtCollector) {
            /** @var Clients $client */
            $client = $debtCollector->getIdClient();
            $data[] = [
                'client'           => $client,
                'entrustedProjects' => [
                    'ongoing' => $debtCollectionMission->getCountMissionsByDebtCollector($debtCollector->getIdClient()),
                    'total'   => $debtCollectionMission->getCountMissionsByDebtCollector($debtCollector->getIdClient(), true),
                    'amount'  => $debtCollectionMission->getAmountMissionsByDebtCollector($debtCollector->getIdClient())
                ]
            ];
        }

        $this->render(null, ['debtCollectors' => $data]);
    }
}
