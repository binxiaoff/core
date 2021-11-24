<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service;

use KLS\CreditGuaranty\FEI\Entity\Borrower;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Service\ReservationAccessor;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ReservationSetTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Service\ReservationAccessor
 *
 * @internal
 */
class ReservationAccessorTest extends TestCase
{
    use ProphecyTrait;
    use PropertyValueTrait;
    use ReservationSetTrait;

    /** @var PropertyAccessorInterface|ObjectProphecy */
    private $propertyAccessor;

    /** @var Reservation */
    private $reservation;

    protected function setUp(): void
    {
        $this->propertyAccessor = $this->prophesize(PropertyAccessorInterface::class);
        $this->reservation      = $this->createReservation();
    }

    protected function tearDown(): void
    {
        $this->propertyAccessor = null;
        $this->reservation      = null;
    }

    /**
     * @covers ::getEntity
     */
    public function testGetEntity(): void
    {
        $this->withBorrower($this->reservation);

        $field = $this->createCompanyNameField();

        $this->propertyAccessor->getValue($this->reservation, 'borrower')
            ->shouldBeCalledOnce()
            ->willReturn($this->reservation->getBorrower())
        ;

        $reservationAccessor = $this->createTestObject();
        $result              = $reservationAccessor->getEntity($this->reservation, $field);

        static::assertInstanceOf(Borrower::class, $result);
    }

    /**
     * @covers ::getEntity
     */
    public function testGetEntityExceptionWithNonExistentPath(): void
    {
        $field = $this->createCompanyNameField();
        $this->forcePropertyValue($field, 'reservationPropertyName', 'borrow');

        $this->propertyAccessor->getValue($this->reservation, 'borrow')
            ->shouldBeCalledOnce()
            ->willThrow(AccessException::class)
        ;

        static::expectException(AccessException::class);

        $reservationAccessor = $this->createTestObject();
        $reservationAccessor->getEntity($this->reservation, $field);
    }

    /**
     * @covers ::getValue
     */
    public function testGetValue(): void
    {
        $this->withBorrower($this->reservation);

        $entity = $this->reservation->getBorrower();
        $field  = $this->createBeneficiaryNameField();

        $this->propertyAccessor->getValue($entity, 'beneficiaryName')
            ->shouldBeCalledOnce()
            ->willReturn('Borrower Name')
        ;

        $reservationAccessor = $this->createTestObject();
        $result              = $reservationAccessor->getValue($this->reservation->getBorrower(), $field);

        static::assertSame('Borrower Name', $result);
    }

    /**
     * @covers ::getValue
     */
    public function testGetMoneyValue(): void
    {
        $this->withBorrower($this->reservation);

        $entity = $this->reservation->getBorrower();
        $field  = $this->createTurnoverField();

        $this->propertyAccessor->getValue($entity, 'turnover')
            ->shouldBeCalledOnce()
            ->willReturn($this->reservation->getBorrower()->getTurnover())
        ;

        $reservationAccessor = $this->createTestObject();
        $result              = $reservationAccessor->getValue($entity, $field);

        static::assertSame('128', $result);
    }

    /**
     * @covers ::getValue
     */
    public function testGetListValue(): void
    {
        $this->withBorrower($this->reservation);

        $entity              = $this->reservation->getBorrower();
        $field               = $this->createBorrowerTypeField();
        $programChoiceOption = new ProgramChoiceOption($this->reservation->getProgram(), 'borrower type', $field);

        $this->propertyAccessor->getValue($entity, 'borrowerType')
            ->shouldBeCalledOnce()
            ->willReturn($programChoiceOption)
        ;

        $reservationAccessor = $this->createTestObject();
        $result              = $reservationAccessor->getValue($this->reservation->getBorrower(), $field);

        static::assertInstanceOf(ProgramChoiceOption::class, $result);
        static::assertSame($programChoiceOption, $result);
    }

    private function createTestObject(): ReservationAccessor
    {
        return new ReservationAccessor(
            $this->propertyAccessor->reveal()
        );
    }
}
