<?php

use Unilend\Entity\Clients;
use Unilend\Entity\Wallet;
use Unilend\Entity\WalletType;
use Unilend\Entity\Zones;
use Unilend\Bundle\CoreBusinessBundle\Service\DebtCollectionMissionManager;

class recouvreurController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_DEBT_COLLECTOR);

        $this->menu_admin = 'recouvreur';
    }

    public function _liste()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager       = $this->get('doctrine.orm.entity_manager');
        $walletDebtCollector = $entityManager->getRepository(WalletType::class)
            ->findOneBy(['label' => WalletType::DEBT_COLLECTOR]);
        $debtCollectors      = $entityManager->getRepository(Wallet::class)
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
            $wallet               = $entityManager->getRepository(Wallet::class)->findOneBy(['idClient' => $clientId]);
            $walletBalanceHistory = $entityManager->getRepository(WalletBalanceHistory::class);
            $data                 = [];

            if ($wallet && WalletType::DEBT_COLLECTOR === $wallet->getIdType()->getLabel()) {
                $company = $entityManager->getRepository(Companies::class)->findOneBy(['idClientOwner' => $wallet->getIdClient()->getIdClient()]);
                $data    = [
                    'company'           => $company,
                    'entrustedProjects' => $this->getEntrustedProjectData($wallet->getIdClient()),
                    'operationHistory'  => $walletBalanceHistory->getDebtCollectorWalletOperations($wallet),
                    'availableBalance'  => $wallet->getAvailableBalance(),
                    'repaymentFees'     => $this->getRepaymentsList($wallet)
                ];
            }
            $this->render(null, $data);
        }
    }

    public function _downloadFeesFile()
    {
        /** @var DebtCollectionMissionManager $debtCollectionMissionManager */
        $debtCollectionMissionManager = $this->get('unilend.service.debt_collection_mission_manager');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        if (false === empty($this->params[0]) && false === empty($this->params[1])) {
            $wireTransferId        = filter_var($this->params[0], FILTER_VALIDATE_INT);
            $debtCollectorClientId = filter_var($this->params[1], FILTER_VALIDATE_INT);
            if (
                null !== ($wireTransfer = $entityManager->getRepository(Receptions::class)->find($wireTransferId))
                && null !== ($debtCollectorWallet = $entityManager->getRepository(Wallet::class)->findOneBy(['idClient' => $debtCollectorClientId]))
                && WalletType::DEBT_COLLECTOR === $debtCollectorWallet->getIdType()->getLabel()
            ) {
                try {
                    $fileName = 'honoraires_' . $debtCollectorWallet->getIdClient()->getNom() . '_rec-' . $wireTransfer->getIdReception() . '_' . date('d-m-Y') . '.xlsx';

                    header('Content-Type: application/force-download; charset=utf-8');
                    header('Content-Disposition: attachment;filename=' . $fileName);
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Expires: 0');

                    $excel = $debtCollectionMissionManager->generateFeeDetailsFile($wireTransfer);
                    if ($excel instanceof \PHPExcel) {
                        /** @var \PHPExcel_Writer_Excel2007 $writer */
                        $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
                        $writer->save('php://output');
                    }
                    die;
                } catch (\Exception $exception) {
                    $this->get('logger')->warning(
                        'Could not download the fees details Excel file for wire transfer: ' . $wireTransfer->getIdReception() . ' Error: ' . $exception->getMessage(),
                        ['file' => $exception->getFile(), 'line' => $exception->getLine()]
                    );
                    header('Location: ' . $this->url . '/recouvreur/details_recouvreur/' . $debtCollectorClientId);
                    die;
                }
            }
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
        $debtCollectionMission = $entityManager->getRepository(DebtCollectionMission::class);

        return [
            'ongoing' => $debtCollectionMission->getCountMissionsByDebtCollector($debtCollector),
            'total'   => $debtCollectionMission->getCountMissionsByDebtCollector($debtCollector, true),
            'amount'  => $debtCollectionMission->getAmountMissionsByDebtCollector($debtCollector)
        ];
    }

    /**
     * @param Wallet|integer $wallet
     *
     * @return array
     */
    private function getRepaymentsList($wallet)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager       = $this->get('doctrine.orm.entity_manager');
        $operationRepository = $entityManager->getRepository(Operation::class);

        return $operationRepository->getFeesPaymentOperations($wallet);
    }
}
