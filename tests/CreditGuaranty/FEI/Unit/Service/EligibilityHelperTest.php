<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service;

use KLS\CreditGuaranty\FEI\Entity\Borrower;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Service\EligibilityHelper;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ReservationSetTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Service\EligibilityHelper
 *
 * @internal
 */
class EligibilityHelperTest extends TestCase
{
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

    public function testGetEntity(): void
    {
        $this->withBorrower($this->reservation);

        $field = new Field('company_name', 'category', 'type', 'borrower', 'companyName', Borrower::class, false, null, null);

        $this->propertyAccessor->getValue($this->reservation, 'borrower')->shouldBeCalledOnce()->willReturn($this->reservation->getBorrower());

        $eligibilityHelper = $this->createTestObject();
        $result            = $eligibilityHelper->getEntity($this->reservation, $field);

        static::assertInstanceOf(Borrower::class, $result);
    }

    public function testGetEntityExceptionWithUnexistedPath(): void
    {
        $field = new Field('company_name', 'category', 'type', 'borrow', 'companyName', 'Name\\Class\\Borrow', false, null, null);

        $this->propertyAccessor->getValue($this->reservation, 'borrow')->shouldBeCalledOnce()->willThrow(AccessException::class);

        static::expectException(AccessException::class);

        $eligibilityHelper = $this->createTestObject();
        $eligibilityHelper->getEntity($this->reservation, $field);
    }

    public function testGetValue(): void
    {
        $this->withBorrower($this->reservation);

        $entity = $this->reservation->getBorrower();
        $field  = new Field('beneficiary_name', 'profile', 'other', 'borrower', 'beneficiaryName', Borrower::class, false, null, null);

        $this->propertyAccessor->getValue($entity, 'beneficiaryName')->shouldBeCalledOnce()->willReturn('Borrower Name');

        $eligibilityHelper = $this->createTestObject();
        $result            = $eligibilityHelper->getValue($this->reservation->getBorrower(), $field);

        static::assertSame('Borrower Name', $result);
    }

    public function testGetMoneyValue(): void
    {
        $this->withBorrower($this->reservation);

        $entity = $this->reservation->getBorrower();
        $field  = new Field('turnover', 'profile', 'other', 'borrower', 'turnover::amount', Borrower::class, true, 'money', null);

        $this->propertyAccessor->getValue($entity, 'turnover.amount')->shouldBeCalledOnce()->willReturn('128');

        $eligibilityHelper = $this->createTestObject();
        $result            = $eligibilityHelper->getValue($entity, $field);

        static::assertSame('128', $result);
    }

    public function testGetListValue(): void
    {
        $this->withBorrower($this->reservation);

        $entity              = $this->reservation->getBorrower();
        $field               = new Field('borrower_type', 'profile', 'list', 'borrower', 'borrowerType', Borrower::class, false, null, null);
        $programChoiceOption = new ProgramChoiceOption($this->reservation->getProgram(), 'borrower type', $field);

        $this->propertyAccessor->getValue($entity, 'borrowerType')->shouldBeCalledOnce()->willReturn($programChoiceOption);

        $eligibilityHelper = $this->createTestObject();
        $result            = $eligibilityHelper->getValue($this->reservation->getBorrower(), $field);

        static::assertInstanceOf(ProgramChoiceOption::class, $result);
        static::assertSame($programChoiceOption, $result);
    }

    private function createTestObject(): EligibilityHelper
    {
        return new EligibilityHelper(
            $this->propertyAccessor->reveal()
        );
    }
}
