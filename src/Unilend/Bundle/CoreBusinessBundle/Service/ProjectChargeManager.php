<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Entity\{ProjectCharge, Receptions, Wallet, WalletType};

class ProjectChargeManager
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var DebtCollectionMissionManager */
    private $debtCollectionMissionManager;

    /** @var OperationManager */
    private $operationManager;

    /**
     * @param EntityManagerInterface       $entityManager
     * @param DebtCollectionMissionManager $debtCollectionMissionManager
     * @param OperationManager             $operationManager
     */
    public function __construct(EntityManagerInterface $entityManager, DebtCollectionMissionManager $debtCollectionMissionManager, OperationManager $operationManager)
    {
        $this->entityManager                = $entityManager;
        $this->debtCollectionMissionManager = $debtCollectionMissionManager;
        $this->operationManager             = $operationManager;
    }

    /**
     * @param Receptions      $wireTransferIn
     * @param ProjectCharge[] $projectCharges
     *
     * @return float
     * @throws \Exception
     */
    public function applyProjectCharge(Receptions $wireTransferIn, $projectCharges)
    {
        $project                          = $wireTransferIn->getIdProject();
        $isDebtCollectionFeeDueToBorrower = $this->debtCollectionMissionManager->isDebtCollectionFeeDueToBorrower($project);
        $totalAppliedCharges              = 0;
        //Treat the project's charges only if the debt collection fee is due to the borrower.
        //Because otherwise, the Unilend takes the charges, and the charges have already been paid before (the charges are created in this case with "paid" status).
        if ($projectCharges && $isDebtCollectionFeeDueToBorrower) {
            foreach ($projectCharges as $projectCharge) {
                if ($projectCharge instanceof ProjectCharge) {
                    $totalAppliedCharges = round(bcadd($totalAppliedCharges, $projectCharge->getAmountInclVat(), 4), 2);
                    $projectCharge->setIdWireTransferIn($wireTransferIn)
                        ->setRepaymentDate(new \DateTime());

                    $this->entityManager->flush($projectCharge);
                } else {
                    throw new \Exception('$projectCharge is not an instance of ProjectCharge.' .
                        'Project id ' . $project->getIdProject() .
                        '. Reception id' . $wireTransferIn->getIdReception() .
                        'projectCharges : ' . var_export($projectCharges));
                }
            }
        }

        return $totalAppliedCharges;
    }

    public function cancelProjectCharge(Receptions $wireTransferIn)
    {
        $appliedProjectCharges = $this->entityManager->getRepository(ProjectCharge::class)->findBy(['idWireTransferIn' => $wireTransferIn]);
        foreach ($appliedProjectCharges as $projectCharge) {
            $projectCharge->setIdWireTransferIn(null)
                ->setRepaymentDate(null);
            $this->entityManager->flush($projectCharge);
        }
    }

    /**
     * @param Receptions $wireTransferIn
     *
     * @throws \Exception
     */
    public function processProjectCharge(Receptions $wireTransferIn)
    {
        $project                          = $wireTransferIn->getIdProject();
        $isDebtCollectionFeeDueToBorrower = $this->debtCollectionMissionManager->isDebtCollectionFeeDueToBorrower($project);

        if ($isDebtCollectionFeeDueToBorrower) {
            $totalAppliedCharges = 0;

            $projectCharges = $this->entityManager->getRepository(ProjectCharge::class)->findBy([
                'idWireTransferIn' => $wireTransferIn,
                'status'           => ProjectCharge::STATUS_PAID_BY_UNILEND
            ]);

            $this->entityManager->getConnection()->beginTransaction();
            try {
                foreach ($projectCharges as $projectCharge) {
                    $totalAppliedCharges = round(bcadd($totalAppliedCharges, $projectCharge->getAmountInclVat(), 4), 2);
                    $projectCharge->setStatus(ProjectCharge::STATUS_REPAID_BY_BORROWER);

                    $this->entityManager->flush($projectCharge);
                }
                $borrowerWallet = $this->entityManager
                    ->getRepository(Wallet::class)
                    ->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);

                $this->operationManager->repayProjectChargeByBorrower($borrowerWallet, $totalAppliedCharges, [$project, $wireTransferIn]);

                $this->entityManager->commit();
            } catch (\Exception $exception) {
                $this->entityManager->rollback();
                throw $exception;
            }
        }
    }
}
