<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\Unit\Service;

use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Unilend\CreditGuaranty\Entity\Borrower;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Service\EligibilityHelper;

/**
 * @coversDefaultClass \Unilend\CreditGuaranty\Service\EligibilityHelper
 *
 * @internal
 */
class EligibilityHelperTest extends AbstractEligibilityTest
{
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
        $this->reservation->setBorrower($this->createBorrower($this->reservation));

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
        $this->reservation->setBorrower($this->createBorrower($this->reservation));

        $entity = $this->reservation->getBorrower();
        $field  = new Field('beneficiary_name', 'profile', 'other', 'borrower', 'beneficiaryName', Borrower::class, false, null, null);

        $this->propertyAccessor->getValue($entity, 'beneficiaryName')->shouldBeCalledOnce()->willReturn('Borrower Name');

        $eligibilityHelper = $this->createTestObject();
        $result            = $eligibilityHelper->getValue($this->reservation->getBorrower(), $field);

        static::assertSame('Borrower Name', $result);
    }

    public function testGetMoneyValue(): void
    {
        $this->reservation->setBorrower($this->createBorrower($this->reservation));

        $entity = $this->reservation->getBorrower();
        $field  = new Field('turnover', 'profile', 'other', 'borrower', 'turnover::amount', Borrower::class, true, 'money', null);

        $this->propertyAccessor->getValue($entity, 'turnover.amount')->shouldBeCalledOnce()->willReturn('128');

        $eligibilityHelper = $this->createTestObject();
        $result            = $eligibilityHelper->getValue($entity, $field);

        static::assertSame('128', $result);
    }

    public function testGetListValue(): void
    {
        $this->reservation->setBorrower($this->createBorrower($this->reservation));

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
