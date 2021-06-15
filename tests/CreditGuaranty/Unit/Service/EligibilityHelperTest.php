<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\Unit\Service;

use LogicException;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Unilend\CreditGuaranty\Entity\Borrower;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;
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

    /** @var ProgramChoiceOptionRepository|ObjectProphecy */
    private $programChoiceOptionRepository;

    protected function setUp(): void
    {
        $this->propertyAccessor              = $this->prophesize(PropertyAccessorInterface::class);
        $this->programChoiceOptionRepository = $this->prophesize(ProgramChoiceOptionRepository::class);
    }

    protected function tearDown(): void
    {
        $this->propertyAccessor              = null;
        $this->programChoiceOptionRepository = null;
    }

    public function testGetEntity(): void
    {
        $reservation = $this->createReservation();
        $field       = new Field('field_alias', 'category', 'type', 'borrower::companyName', false, null, null);

        $this->propertyAccessor->getValue($reservation, 'borrower')->shouldBeCalledOnce()->willReturn($reservation->getBorrower());

        $eligibilityHelper = $this->createTestObject();
        $result            = $eligibilityHelper->getEntity($reservation, $field);

        static::assertInstanceOf(Borrower::class, $result);
    }

    public function testGetEntityExceptionWithUnexistedPath(): void
    {
        $reservation = $this->createReservation();
        $field       = new Field('field_alias', 'category', 'type', 'borrow::companyName', false, null, null);

        $this->propertyAccessor->getValue($reservation, 'borrow')->shouldBeCalledOnce()->willThrow(AccessException::class);

        static::expectException(AccessException::class);

        $eligibilityHelper = $this->createTestObject();
        $eligibilityHelper->getEntity($reservation, $field);
    }

    public function testGetValue(): void
    {
        $reservation = $this->createReservation();
        $entity      = $reservation->getBorrower();
        $field       = new Field('beneficiary_name', 'profile', 'other', 'borrower::beneficiaryName', false, null, null);

        $this->propertyAccessor->getValue($entity, 'beneficiaryName')->shouldBeCalledOnce()->willReturn('Borrower Name');
        $this->programChoiceOptionRepository->findOneBy(Argument::any())->shouldNotBeCalled();

        $eligibilityHelper = $this->createTestObject();
        $result            = $eligibilityHelper->getValue($reservation->getProgram(), $reservation->getBorrower(), $field);

        static::assertSame('Borrower Name', $result);
    }

    public function testGetMoneyValue(): void
    {
        $reservation = $this->createReservation();
        $entity      = $reservation->getBorrower();
        $field       = new Field('last_year_turnover', 'activity', 'other', 'borrower::turnover::amount', true, 'money', null);

        $this->propertyAccessor->getValue($entity, 'turnover.amount')->shouldBeCalledOnce()->willReturn('128');
        $this->programChoiceOptionRepository->findOneBy(Argument::any())->shouldNotBeCalled();

        $eligibilityHelper = $this->createTestObject();
        $result            = $eligibilityHelper->getValue($reservation->getProgram(), $entity, $field);

        static::assertSame('128', $result);
    }

    public function testGetListValue(): void
    {
        $reservation         = $this->createReservation();
        $entity              = $reservation->getBorrower();
        $field               = new Field('activity_country', 'activity', 'list', 'borrower::address::country', false, null, null);
        $programChoiceOption = new ProgramChoiceOption($reservation->getProgram(), 'FR', $field);

        $this->propertyAccessor->getValue($entity, 'address.country')->shouldBeCalledOnce()->willReturn('FR');
        $this->programChoiceOptionRepository->findOneBy([
            'program'     => $reservation->getProgram(),
            'field'       => $field,
            'description' => 'FR',
        ])->shouldBeCalledOnce()->willReturn($programChoiceOption);

        $eligibilityHelper = $this->createTestObject();
        $result            = $eligibilityHelper->getValue($reservation->getProgram(), $entity, $field);

        static::assertInstanceOf(ProgramChoiceOption::class, $result);
        static::assertSame($programChoiceOption, $result);
    }

    public function testGetListValueChoiceOption(): void
    {
        $reservation         = $this->createReservation();
        $entity              = $reservation->getBorrower();
        $field               = new Field('borrower_type', 'profile', 'list', 'borrower::borrowerType', false, null, null);
        $programChoiceOption = new ProgramChoiceOption($reservation->getProgram(), 'borrower type', $field);

        $this->propertyAccessor->getValue($entity, 'borrowerType')->shouldBeCalledOnce()->willReturn($programChoiceOption);
        $this->programChoiceOptionRepository->findOneBy(Argument::any())->shouldNotBeCalled();

        $eligibilityHelper = $this->createTestObject();
        $result            = $eligibilityHelper->getValue($reservation->getProgram(), $reservation->getBorrower(), $field);

        static::assertInstanceOf(ProgramChoiceOption::class, $result);
        static::assertSame($programChoiceOption, $result);
    }

    public function testGetListValueExceptionWithProgramChoiceOptionNotFound(): void
    {
        $reservation = $this->createReservation();
        $entity      = $reservation->getBorrower();
        $field       = new Field('activity_country', 'activity', 'list', 'borrower::address::country', false, null, null);

        $this->propertyAccessor->getValue($entity, 'address.country')->shouldBeCalledOnce()->willReturn('FR');
        $this->programChoiceOptionRepository->findOneBy([
            'program'     => $reservation->getProgram(),
            'field'       => $field,
            'description' => 'FR',
        ])->shouldBeCalledOnce()->willReturn(null);

        static::expectException(LogicException::class);

        $eligibilityHelper = $this->createTestObject();
        $eligibilityHelper->getValue($reservation->getProgram(), $reservation->getBorrower(), $field);
    }

    private function createTestObject(): EligibilityHelper
    {
        return new EligibilityHelper(
            $this->propertyAccessor->reveal(),
            $this->programChoiceOptionRepository->reveal()
        );
    }
}
