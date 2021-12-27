<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Entity;

use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ReservationSetTrait;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Entity\Project
 *
 * @internal
 */
class ProjectTest extends TestCase
{
    use ReservationSetTrait;

    /**
     * @covers ::getMaxFeiCredit
     * @covers ::getTotalGrossSubsidyEquivalent
     */
    public function testGetMaxFeiCredit(): void
    {
        $reservation = $this->createReservation();
        $this->withProject($reservation);

        $reservation->getProgram()
            ->setMaxFeiCredit(new NullableMoney('EUR', '4980000'))
            ->setGuarantyDuration(120)
            ->setGuarantyCoverage('0.8')
        ;
        $reservation->getProject()
            ->setGrant(new NullableMoney('EUR', '70000'))
            ->setAidIntensity(
                new ProgramChoiceOption($reservation->getProgram(), '0.4', $this->createAidIntensityField())
            )
            ->setTotalFeiCredit(new NullableMoney('EUR', '100000'))
            ->setEligibleFeiCredit(new NullableMoney('EUR', '100000'))
        ;
        $financingObject1 = $this->createFinancingObject($reservation, false);
        $financingObject1
            ->setLoanDuration(120)
            ->setLoanMoney(new Money('EUR', '100000'))
        ;
        $reservation->addFinancingObject($financingObject1);

        $maxFeiCredit                = $reservation->getProject()->getMaxFeiCredit();
        $totalGrossSubsidyEquivalent = $reservation->getProject()->getTotalGrossSubsidyEquivalent();

        static::assertSame((int) $maxFeiCredit->getAmount(), -76800);
        static::assertSame((int) $totalGrossSubsidyEquivalent->getAmount(), 256000);
    }
}
