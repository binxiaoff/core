<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ReservationSetTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Entity\FinancingObject
 *
 * @internal
 */
class FinancingObjectTest extends TestCase
{
    use ProphecyTrait;
    use ReservationSetTrait;

    /**
     * @covers ::validateMainLoan
     *
     * @dataProvider mainLoanProvider
     */
    public function testValidateMainLoan(
        Reservation $reservation,
        ArrayCollection $reservationFinancingObjects,
        FinancingObject $financingObject,
        bool $hasViolation
    ): void {
        $context                    = $this->prophesize(ExecutionContextInterface::class);
        $constraintViolationBuilder = $this->prophesize(ConstraintViolationBuilderInterface::class);

        $this->forcePropertyValue($reservation, 'financingObjects', $reservationFinancingObjects);

        if ($hasViolation) {
            $context->buildViolation(Argument::type('string'))
                ->shouldBeCalledOnce()
                ->willReturn($constraintViolationBuilder->reveal())
            ;
            $constraintViolationBuilder->atPath('mainLoan')
                ->shouldBeCalledOnce()
                ->willReturn($constraintViolationBuilder->reveal())
            ;
            $constraintViolationBuilder->addViolation()
                ->shouldBeCalledOnce()
                ->willReturn($constraintViolationBuilder->reveal())
            ;
        } else {
            $context->buildViolation(Argument::any())->shouldNotBeCalled();
            $constraintViolationBuilder->atPath(Argument::any())->shouldNotBeCalled();
            $constraintViolationBuilder->addViolation()->shouldNotBeCalled();
        }

        static::assertNull($financingObject->validateMainLoan($context->reveal()));
    }

    public function mainLoanProvider(): iterable
    {
        $reservation = $this->createReservation();

        yield 'reservation without financingObjects - financingObject as not main' => [
            $reservation,
            new ArrayCollection(),
            ($this->createFinancingObject($reservation, false))->setMainLoan(false),
            false,
        ];
        yield 'reservation without financingObjects - financingObject as main' => [
            $reservation,
            new ArrayCollection(),
            ($this->createFinancingObject($reservation, false))->setMainLoan(true),
            false,
        ];
        yield 'reservation with financingObjects without main - financingObject as not main' => [
            $reservation,
            new ArrayCollection([
                ($this->createFinancingObject($reservation, false))->setMainLoan(false),
            ]),
            ($this->createFinancingObject($reservation, false))->setMainLoan(false),
            false,
        ];
        yield 'reservation with financingObjects without main - financingObject as main' => [
            $reservation,
            new ArrayCollection([
                ($this->createFinancingObject($reservation, false))->setMainLoan(false),
                ($this->createFinancingObject($reservation, false))->setMainLoan(false),
            ]),
            ($this->createFinancingObject($reservation, false))->setMainLoan(true),
            false,
        ];
        yield 'reservation with financingObjects with main - financingObject as not main' => [
            $reservation,
            new ArrayCollection([
                ($this->createFinancingObject($reservation, false))->setMainLoan(false),
                ($this->createFinancingObject($reservation, false))->setMainLoan(true),
            ]),
            ($this->createFinancingObject($reservation, false))->setMainLoan(false),
            false,
        ];
        yield 'reservation with financingObjects with main - financingObject as main' => [
            $reservation,
            new ArrayCollection([
                ($this->createFinancingObject($reservation, false))->setMainLoan(true),
            ]),
            ($this->createFinancingObject($reservation, false))->setMainLoan(true),
            true,
        ];
    }

    /**
     * @covers ::validateLoanMoneyAfterContractualisation
     *
     * @dataProvider loanMoneyAfterContractualisationProvider
     */
    public function testValidateLoanMoneyAfterContractualisation(
        FinancingObject $financingObject,
        bool $hasViolation
    ): void {
        $context                    = $this->prophesize(ExecutionContextInterface::class);
        $constraintViolationBuilder = $this->prophesize(ConstraintViolationBuilderInterface::class);

        if ($hasViolation) {
            $context->buildViolation(Argument::type('string'))
                ->shouldBeCalledOnce()
                ->willReturn($constraintViolationBuilder->reveal())
            ;
            $constraintViolationBuilder->atPath('loanMoneyAfterContractualisation')
                ->shouldBeCalledOnce()
                ->willReturn($constraintViolationBuilder->reveal())
            ;
            $constraintViolationBuilder->addViolation()
                ->shouldBeCalledOnce()
                ->willReturn($constraintViolationBuilder->reveal())
            ;
        } else {
            $context->buildViolation(Argument::any())->shouldNotBeCalled();
            $constraintViolationBuilder->atPath(Argument::any())->shouldNotBeCalled();
            $constraintViolationBuilder->addViolation()->shouldNotBeCalled();
        }

        static::assertNull($financingObject->validateLoanMoneyAfterContractualisation($context->reveal()));
    }

    public function loanMoneyAfterContractualisationProvider(): iterable
    {
        $reservation = $this->createReservation();

        yield 'loanMoneyAfterContractualisation invalid' => [
            ($this->createFinancingObject($reservation, false))
                ->setLoanMoney(new Money('EUR', '10000'))
                ->setLoanMoneyAfterContractualisation(new NullableMoney(null, null)),
            false,
        ];
        yield 'loanMoneyAfterContractualisation = loanMoney' => [
            ($this->createFinancingObject($reservation, false))
                ->setLoanMoney(new Money('EUR', '10000'))
                ->setLoanMoneyAfterContractualisation(new NullableMoney('EUR', '10000')),
            false,
        ];
        yield 'loanMoneyAfterContractualisation < loanMoney' => [
            ($this->createFinancingObject($reservation, false))
                ->setLoanMoney(new Money('EUR', '10000'))
                ->setLoanMoneyAfterContractualisation(new NullableMoney('EUR', '200')),
            false,
        ];
        yield 'loanMoneyAfterContractualisation > loanMoney' => [
            ($this->createFinancingObject($reservation, false))
                ->setLoanMoney(new Money('EUR', '10000'))
                ->setLoanMoneyAfterContractualisation(new NullableMoney('EUR', '42000')),
            true,
        ];
    }
}
