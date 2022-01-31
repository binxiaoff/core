<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Functional\Entity\Reservation;

use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\Test\Core\Functional\Api\AbstractApiTest;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ReservationSetTrait;

/**
 * @coversNothing
 *
 * @internal
 */
class EsbCalculusTest extends AbstractApiTest
{
    use ReservationSetTrait;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testEsbCalculationsAndEligibility(): void
    {
        $reservation = $this->createReservation();
        $this->withProject($reservation);

        $reservation->getProgram()
            ->setMaxFeiCredit(new NullableMoney('EUR', '100000'))
            ->setGuarantyDuration(12)
            ->setGuarantyCoverage('0.8')
        ;
        $reservation->getProject()
            ->setEligibleFeiCredit(new NullableMoney('EUR', '42000'))
            ->setGrant(new NullableMoney('EUR', '5000'))
            ->setAidIntensity(
                new ProgramChoiceOption($reservation->getProgram(), '0.4', $this->createAidIntensityField())
            )
        ;
        $financingObject1 = ($this->createFinancingObject($reservation, false))
            ->setLoanDuration(6)
            ->setLoanMoney(new Money('EUR', '21000'))
        ;
        $financingObject2 = ($this->createFinancingObject($reservation, false))
            ->setLoanDuration(8)
            ->setLoanMoney(new Money('EUR', '21000'))
        ;
        $reservation
            ->addFinancingObject($financingObject1)
            ->addFinancingObject($financingObject2)
        ;

        $maxFeiCredit                = $reservation->getProject()->getMaxFeiCredit();
        $grossSubsidyEquivalent1     = $financingObject1->getGrossSubsidyEquivalent();
        $grossSubsidyEquivalent2     = $financingObject2->getGrossSubsidyEquivalent();
        $totalGrossSubsidyEquivalent = $reservation->getProject()->getTotalGrossSubsidyEquivalent();

        static::assertSame((float) $maxFeiCredit->getAmount(), 69140.62);
        static::assertSame((float) $grossSubsidyEquivalent1->getAmount(), 2688.0);
        static::assertSame((float) $grossSubsidyEquivalent2->getAmount(), 3583.44);
        static::assertSame((float) $totalGrossSubsidyEquivalent->getAmount(), 2688 + 3583.44);
        static::assertTrue($reservation->isGrossSubsidyEquivalentEligible());
    }

    public function testEsbCalculationsAndEligibilityWithProgramEligibilityAndGrantNull(): void
    {
        $reservation = $this->createReservation();
        $program     = $reservation->getProgram();
        $this->withProject($reservation);

        $program->getProgramEligibilities()
            ->add(new ProgramEligibility($program, $this->createReceivingGrantField()))
        ;
        $program
            ->setMaxFeiCredit(new NullableMoney('EUR', '100000'))
            ->setGuarantyDuration(12)
            ->setGuarantyCoverage('0.8')
        ;
        $reservation->getProject()
            ->setEligibleFeiCredit(new NullableMoney('EUR', '42000'))
            ->setGrant(new NullableMoney())
            ->setAidIntensity(
                new ProgramChoiceOption($program, '0.4', $this->createAidIntensityField())
            )
        ;
        $financingObject1 = ($this->createFinancingObject($reservation, false))
            ->setLoanDuration(6)
            ->setLoanMoney(new Money('EUR', '21000'))
        ;
        $financingObject2 = ($this->createFinancingObject($reservation, false))
            ->setLoanDuration(8)
            ->setLoanMoney(new Money('EUR', '21000'))
        ;
        $reservation
            ->addFinancingObject($financingObject1)
            ->addFinancingObject($financingObject2)
        ;

        $maxFeiCredit                = $reservation->getProject()->getMaxFeiCredit();
        $grossSubsidyEquivalent1     = $financingObject1->getGrossSubsidyEquivalent();
        $grossSubsidyEquivalent2     = $financingObject2->getGrossSubsidyEquivalent();
        $totalGrossSubsidyEquivalent = $reservation->getProject()->getTotalGrossSubsidyEquivalent();

        static::assertSame((float) $maxFeiCredit->getAmount(), 98437.5);
        static::assertSame((float) $grossSubsidyEquivalent1->getAmount(), 2688.0);
        static::assertSame((float) $grossSubsidyEquivalent2->getAmount(), 3583.44);
        static::assertSame((float) $totalGrossSubsidyEquivalent->getAmount(), 2688 + 3583.44);
        static::assertTrue($reservation->isGrossSubsidyEquivalentEligible());
    }

    public function testEsbCalculationsAndEligibilityWithNullValues(): void
    {
        $reservation = $this->createReservation();
        $this->withProject($reservation);

        $reservation->getProgram()
            ->setMaxFeiCredit(new NullableMoney())
            ->setGuarantyDuration(null)
            ->setGuarantyCoverage('0.8')
        ;
        $reservation->getProject()
            ->setEligibleFeiCredit(new NullableMoney('EUR', '42000'))
            ->setGrant(new NullableMoney('EUR', '5000'))
            ->setAidIntensity(null)
        ;
        $financingObject1 = ($this->createFinancingObject($reservation, false))
            ->setLoanDuration(null)
            ->setLoanMoney(new Money('EUR', '21000'))
        ;
        $financingObject2 = ($this->createFinancingObject($reservation, false))
            ->setLoanDuration(8)
            ->setLoanMoney(new Money('EUR', '21000'))
        ;
        $reservation
            ->addFinancingObject($financingObject1)
            ->addFinancingObject($financingObject2)
        ;

        $maxFeiCredit                = $reservation->getProject()->getMaxFeiCredit();
        $grossSubsidyEquivalent1     = $financingObject1->getGrossSubsidyEquivalent();
        $grossSubsidyEquivalent2     = $financingObject2->getGrossSubsidyEquivalent();
        $totalGrossSubsidyEquivalent = $reservation->getProject()->getTotalGrossSubsidyEquivalent();

        static::assertInstanceOf(NullableMoney::class, $maxFeiCredit);
        static::assertTrue($maxFeiCredit->isNull());
        static::assertInstanceOf(NullableMoney::class, $grossSubsidyEquivalent1);
        static::assertTrue($grossSubsidyEquivalent1->isNull());
        static::assertInstanceOf(NullableMoney::class, $grossSubsidyEquivalent2);
        static::assertTrue($grossSubsidyEquivalent2->isNull());
        static::assertInstanceOf(NullableMoney::class, $totalGrossSubsidyEquivalent);
        static::assertTrue($totalGrossSubsidyEquivalent->isNull());
        static::assertFalse($reservation->isGrossSubsidyEquivalentEligible());
    }

    public function testEsbCalculationsAndEligibilityWithoutFinancingObjects(): void
    {
        $reservation = $this->createReservation();
        $this->withProject($reservation);

        $reservation->getProgram()
            ->setMaxFeiCredit(new NullableMoney('EUR', '100000'))
            ->setGuarantyDuration(12)
            ->setGuarantyCoverage('0.8')
        ;
        $reservation->getProject()
            ->setEligibleFeiCredit(new NullableMoney('EUR', '42000'))
            ->setGrant(new NullableMoney('EUR', '5000'))
            ->setAidIntensity(
                new ProgramChoiceOption($reservation->getProgram(), '0.4', $this->createAidIntensityField())
            )
        ;

        $maxFeiCredit                = $reservation->getProject()->getMaxFeiCredit();
        $totalGrossSubsidyEquivalent = $reservation->getProject()->getTotalGrossSubsidyEquivalent();

        static::assertInstanceOf(NullableMoney::class, $maxFeiCredit);
        static::assertTrue($maxFeiCredit->isNull());
        static::assertInstanceOf(NullableMoney::class, $totalGrossSubsidyEquivalent);
        static::assertTrue($totalGrossSubsidyEquivalent->isNull());
        static::assertFalse($reservation->isGrossSubsidyEquivalentEligible());
    }
}
