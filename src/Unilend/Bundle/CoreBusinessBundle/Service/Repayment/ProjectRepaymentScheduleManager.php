<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Repayment;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Service\WorkingDaysDetector;

class ProjectRepaymentScheduleManager
{
    const WORKING_DAY_DIFF_BETWEEN_BORROWER_AND_LENDER = 6;

    /** @var EntityManager */
    private $entityManager;
    /** @var WorkingDaysDetector */
    private $workingDaysDetector;

    public function __construct(EntityManager $entityManager, WorkingDaysDetector $workingDaysDetector)
    {
        $this->entityManager = $entityManager;
        $this->workingDaysDetector = $workingDaysDetector;
    }

    /**
     * @param \DateTime $fundedDate
     * @param int       $sequence
     *
     * @return \DateTime
     */
    public function generateLenderMonthlyAmortizationDate(\DateTime $fundedDate, int $sequence): \DateTime
    {
        if (1 > $sequence) {
            throw new \InvalidArgumentException('The sequence must be greater then 1');
        }

        $date = clone $fundedDate;

        $day = $date->format('d');

        $date->modify($sequence . ' month');

        if ($day !== $date->format('d')) {
            $date->modify('last day of previous month');
        }

        return $date;
    }

    /**
     * @param \DateTime $fundedDate
     * @param int       $sequence
     *
     * @return \DateTimeInterface
     */
    public function generateBorrowerMonthlyAmortizationDate(\DateTime $fundedDate, int $sequence): \DateTimeInterface
    {
        $lenderRepaymentDate = $this->generateLenderMonthlyAmortizationDate($fundedDate, $sequence);

        $daysOffsetSetting = $this
            ->entityManager
            ->getRepository('UnilendCoreBusinessBundle:Settings')
            ->findOneBy(['type' => 'Nombre jours avant remboursement pour envoyer une demande de prelevement']);

        if ($daysOffsetSetting) {
            $daysOffset = $daysOffsetSetting->getValue();
        } else {
            $daysOffset = self::WORKING_DAY_DIFF_BETWEEN_BORROWER_AND_LENDER;
        }

        return $this->workingDaysDetector->previousWorkingDay($lenderRepaymentDate, $daysOffset);
    }
}
