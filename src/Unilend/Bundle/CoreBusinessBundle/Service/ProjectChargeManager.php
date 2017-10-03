<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectCharge;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;

class ProjectChargeManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var DebtCollectionMissionManager */
    private $debtCollectionMissionManager;

    public function __construct(EntityManager $entityManager, DebtCollectionMissionManager $debtCollectionMissionManager)
    {
        $this->entityManager                = $entityManager;
        $this->debtCollectionMissionManager = $debtCollectionMissionManager;
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
                    $projectCharge->setIdWireTransferIn($wireTransferIn);

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
        $appliedProjectCharges = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectCharge')->findBy(['idWireTransferIn' => $wireTransferIn]);
        foreach ($appliedProjectCharges as $projectCharge) {
            $projectCharge->setIdWireTransferIn(null);
            $this->entityManager->flush($projectCharge);
        }
    }
}
