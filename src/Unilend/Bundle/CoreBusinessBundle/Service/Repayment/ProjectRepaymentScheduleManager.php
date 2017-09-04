<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class ProjectRepaymentScheduleManager
{
    /** @var EntityManager */
    private $entityManager;


    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;

    /**
     * ProjectRepaymentManager constructor.
     *
     * @param EntityManager               $entityManager
     * @param EntityManagerSimulator      $entityManagerSimulator
     */
    public function __construct(
        EntityManager $entityManager,
        EntityManagerSimulator $entityManagerSimulator
    )
    {
        $this->entityManager               = $entityManager;
        $this->entityManagerSimulator      = $entityManagerSimulator;
    }

    /**
     * @param Projects $project
     *
     * @return float
     */
    public function getMonthlyAmount(Projects $project)
    {
        $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy(['idProject' => $project]);
        return round(bcdiv($paymentSchedule->getCapital() + $paymentSchedule->getInterets() + $paymentSchedule->getCommission() + $paymentSchedule->getTva(), 100, 4), 2);
    }

    /**
     * @param Projects $project
     *
     * @return float
     */
    public function getNetMonthlyAmount(Projects $project)
    {
        $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy(['idProject' => $project]);
        return round(bcdiv($paymentSchedule->getCapital() + $paymentSchedule->getInterets(), 100, 4), 2);
    }

    /**
     * @param Projects $project
     *
     * @return float
     */
    public function getUnilendCommissionVatIncl(Projects $project)
    {
        $paymentSchedule = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->findOneBy(['idProject' => $project]);
        return round(bcdiv($paymentSchedule->getCommission() + $paymentSchedule->getTva(), 100, 4), 2);
    }

    /**
     * @param Projects $project
     * @param int      $sequence
     *
     * @return float
     */
    public function getUnrepaidAmount(Projects $project, $sequence)
    {
        $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');

        return $repaymentScheduleRepository->getUnrepaidAmount($project, $sequence);
    }
}
