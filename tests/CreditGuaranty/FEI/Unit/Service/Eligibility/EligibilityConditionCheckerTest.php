<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service\Eligibility;

use Doctrine\Common\Collections\ArrayCollection;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityCondition;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Service\Eligibility\EligibilityConditionChecker;
use KLS\CreditGuaranty\FEI\Service\ReservationAccessor;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ReservationSetTrait;
use LogicException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Service\Eligibility\EligibilityConditionChecker
 *
 * @internal
 */
class EligibilityConditionCheckerTest extends TestCase
{
    use ProphecyTrait;
    use ReservationSetTrait;

    /** @var ReservationAccessor|ObjectProphecy */
    private $reservationAccessor;

    /** @var Reservation */
    private $reservation;

    protected function setUp(): void
    {
        $this->reservationAccessor = $this->prophesize(ReservationAccessor::class);
        $this->reservation         = $this->createReservation();
    }

    protected function tearDown(): void
    {
        $this->reservationAccessor = null;
        $this->reservation         = null;
    }

    /**
     * @covers ::checkByConfiguration
     */
    public function testCheckByConfigurationWithoutConditions(): void
    {
        $field = new Field(
            'alias_1',
            Field::TAG_ELIGIBILITY,
            'test',
            'other',
            'entity',
            'field',
            'type',
            'Name\\Class\\Entity',
            false,
            null,
            null
        );

        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);

        $this->reservationAccessor->getValue(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->reservationAccessor->getEntity($this->reservation, Argument::any())->shouldNotBeCalled();

        $eligibilityConditionChecker = $this->createTestObject();
        $result                      = $eligibilityConditionChecker->checkByConfiguration(
            $this->reservation->getBorrower(),
            $programEligibilityConfiguration
        );

        static::assertTrue($result);
    }

    /**
     * @covers ::checkByConfiguration
     */
    public function testCheckByConfigurationWithEligibleConditions(): void
    {
        $this->withBorrower($this->reservation);
        $entity = $this->reservation->getBorrower();

        $field                           = $this->createRegistrationNumberField();
        $leftField1                      = $this->createEmployeesNumberField();
        $leftField2                      = $this->createTurnoverField();
        $rightField2                     = $this->createTotalAssetsField();
        $leftField3                      = $this->createCreationInProgressField();
        $leftField4                      = $this->createActivityDepartmentField();
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);

        $programEligibilityCondition1 = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $leftField1,
            null,
            'gte',
            'value'
        ))->setValue('42');
        $programEligibilityCondition2 = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $leftField2,
            $rightField2,
            'lt',
            'rate'
        ))->setValue('42');
        $programEligibilityCondition3 = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $leftField3,
            null,
            'eq',
            'bool'
        ))->setValue('0');
        $programEligibilityCondition4 = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $leftField4,
            null,
            'eq',
            'list'
        ))->addProgramChoiceOption($this->reservation->getBorrower()->getAddressDepartment());

        $programEligibilityConditions = new ArrayCollection([
            $programEligibilityCondition1,
            $programEligibilityCondition2,
            $programEligibilityCondition3,
            $programEligibilityCondition4,
        ]);
        $this->forcePropertyValue(
            $programEligibilityConfiguration,
            'programEligibilityConditions',
            $programEligibilityConditions
        );

        // condition 1 - value
        $this->reservationAccessor->getEntity($this->reservation, $leftField1)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $leftField1)
            ->shouldBeCalledOnce()
            ->willReturn(42)
        ;

        // condition 2 - rate
        $this->reservationAccessor->getEntity($this->reservation, $rightField2)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $rightField2)
            ->shouldBeCalledOnce()
            ->willReturn('2048')
        ;
        $this->reservationAccessor->getEntity($this->reservation, $leftField2)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $leftField2)->shouldBeCalledOnce()->willReturn('128');

        // condition 3 - bool
        $this->reservationAccessor->getEntity($this->reservation, $leftField3)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $leftField3)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        // condition 4 - list
        $this->reservationAccessor->getEntity($this->reservation, $leftField4)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $leftField4)
            ->shouldBeCalledOnce()
            ->willReturn($entity->getAddressDepartment())
        ;

        $eligibilityConditionChecker = $this->createTestObject();
        $result                      = $eligibilityConditionChecker->checkByConfiguration(
            $entity,
            $programEligibilityConfiguration
        );

        static::assertTrue($result);
    }

    /**
     * @covers ::checkByConfiguration
     */
    public function testCheckByConfigurationWithIneligibleValueTypeCondition(): void
    {
        $entity = $this->reservation->getBorrower();

        $field                           = $this->createRegistrationNumberField();
        $leftField1                      = $this->createTotalAssetsField();
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);
        $programEligibilityCondition1    = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $leftField1,
            null,
            'gt',
            'value'
        ))->setValue('2048');

        $programEligibilityConditions = new ArrayCollection([$programEligibilityCondition1]);
        $this->forcePropertyValue(
            $programEligibilityConfiguration,
            'programEligibilityConditions',
            $programEligibilityConditions
        );

        $this->reservationAccessor->getEntity($this->reservation, $leftField1)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $leftField1)
            ->shouldBeCalledOnce()
            ->willReturn('2048')
        ;

        $eligibilityConditionChecker = $this->createTestObject();
        $result                      = $eligibilityConditionChecker->checkByConfiguration(
            $entity,
            $programEligibilityConfiguration
        );

        static::assertFalse($result);
    }

    /**
     * @covers ::checkByConfiguration
     */
    public function testCheckByConfigurationWithIneligibleRateTypeCondition(): void
    {
        $entity = $this->reservation->getProject();
        $entity->setFundingMoney(new NullableMoney('EUR', '42'));
        $financingObject = $this->createFinancingObject($this->reservation, true);
        $financingObject->setLoanMoney(new Money('EUR', '0'));
        $this->reservation->addFinancingObject($financingObject);

        $field                           = $this->createLoanMoneyField();
        $rightField1                     = $this->createProjectTotalAmountField();
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);
        $programEligibilityCondition1    = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $field,
            $rightField1,
            'gt',
            'rate'
        ))->setValue('10');

        $programEligibilityConditions = new ArrayCollection([$programEligibilityCondition1]);
        $this->forcePropertyValue(
            $programEligibilityConfiguration,
            'programEligibilityConditions',
            $programEligibilityConditions
        );

        $this->reservationAccessor->getEntity($this->reservation, $rightField1)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $rightField1)
            ->shouldBeCalledOnce()
            ->willReturn($entity->getFundingMoney())
        ;
        $this->reservationAccessor->getEntity($this->reservation, $field)
            ->shouldBeCalledOnce()
            ->willReturn($this->reservation->getFinancingObjects())
        ;
        $this->reservationAccessor->getValue($financingObject, $field)
            ->shouldBeCalledOnce()
            ->willReturn($financingObject->getLoanMoney())
        ;

        $eligibilityConditionChecker = $this->createTestObject();
        $result                      = $eligibilityConditionChecker->checkByConfiguration(
            $entity,
            $programEligibilityConfiguration
        );

        static::assertFalse($result);
    }

    /**
     * @covers ::checkByConfiguration
     */
    public function testCheckByConfigurationWithIneligibleBoolTypeCondition(): void
    {
        $entity = $this->reservation->getBorrower();

        $field                           = $this->createRegistrationNumberField();
        $leftField1                      = $this->createYoungFarmerField();
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);
        $programEligibilityCondition1    = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $leftField1,
            null,
            'eq',
            'value'
        ))->setValue('0');

        $programEligibilityConditions = new ArrayCollection([$programEligibilityCondition1]);
        $this->forcePropertyValue(
            $programEligibilityConfiguration,
            'programEligibilityConditions',
            $programEligibilityConditions
        );

        $this->reservationAccessor->getEntity($this->reservation, $leftField1)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $leftField1)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $eligibilityConditionChecker = $this->createTestObject();
        $result                      = $eligibilityConditionChecker->checkByConfiguration(
            $entity,
            $programEligibilityConfiguration
        );

        static::assertFalse($result);
    }

    /**
     * @covers ::checkByConfiguration
     */
    public function testCheckByConfigurationWithIneligibleListTypeCondition(): void
    {
        $entity = $this->reservation->getBorrower();

        $field                           = $this->createRegistrationNumberField();
        $leftField1                      = $this->createActivityCountryField();
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);
        $programEligibilityCondition1    = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $leftField1,
            null,
            'eq',
            'list'
        ))->addProgramChoiceOption(new ProgramChoiceOption($this->reservation->getProgram(), 'RF', $leftField1));

        $programEligibilityConditions = new ArrayCollection([$programEligibilityCondition1]);
        $this->forcePropertyValue(
            $programEligibilityConfiguration,
            'programEligibilityConditions',
            $programEligibilityConditions
        );

        $this->reservationAccessor->getEntity($this->reservation, $leftField1)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $leftField1)
            ->shouldBeCalledOnce()
            ->willReturn($entity->getAddressCountry())
        ;

        $eligibilityConditionChecker = $this->createTestObject();
        $result                      = $eligibilityConditionChecker->checkByConfiguration(
            $entity,
            $programEligibilityConfiguration
        );

        static::assertFalse($result);
    }

    /**
     * @covers ::checkByConfiguration
     */
    public function testCheckByConfigurationExceptionWithoutRightOperandFieldInRateTypeCondition(): void
    {
        $field                           = $this->createProjectTotalAmountField();
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);
        $programEligibilityCondition1    = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $field,
            null,
            'lte',
            'rate'
        ))->setValue('42');

        $programEligibilityConditions = new ArrayCollection([$programEligibilityCondition1]);
        $this->forcePropertyValue(
            $programEligibilityConfiguration,
            'programEligibilityConditions',
            $programEligibilityConditions
        );

        $this->reservationAccessor->getEntity($this->reservation, $field)->shouldNotBeCalled();
        $this->reservationAccessor->getValue($this->reservation->getProject(), $field)->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityConditionChecker = $this->createTestObject();
        $eligibilityConditionChecker->checkByConfiguration(
            $this->reservation->getProject(),
            $programEligibilityConfiguration
        );
    }

    /**
     * @covers ::checkByConfiguration
     */
    public function testCheckByConfigurationExceptionWithInvalidRightOperandFieldInRateTypeCondition(): void
    {
        $financingObject = $this->createFinancingObject($this->reservation, true);

        $field                           = $this->createProjectTotalAmountField();
        $rightField1                     = $this->createLoanMoneyField();
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);
        $programEligibilityCondition1    = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $field,
            $rightField1,
            'gt',
            'rate'
        ))->setValue('42');

        $programEligibilityConditions = new ArrayCollection([$programEligibilityCondition1]);
        $this->forcePropertyValue(
            $programEligibilityConfiguration,
            'programEligibilityConditions',
            $programEligibilityConditions
        );

        $this->reservationAccessor->getEntity($this->reservation, $rightField1)
            ->shouldBeCalledOnce()
            ->willReturn(new ArrayCollection([$financingObject]))
        ;
        $this->reservationAccessor->getValue($financingObject, $rightField1)->shouldNotBeCalled();
        $this->reservationAccessor->getEntity($this->reservation, $field)->shouldNotBeCalled();
        $this->reservationAccessor->getValue($this->reservation->getProject(), $field)->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityConditionChecker = $this->createTestObject();
        $eligibilityConditionChecker->checkByConfiguration($financingObject, $programEligibilityConfiguration);
    }

    /**
     * @covers ::getIneligibleIdsByConfiguration
     */
    public function testGetIneligibleIdsByConfigurationWithoutConditions(): void
    {
        $field = new Field(
            'alias_1',
            Field::TAG_ELIGIBILITY,
            'test',
            'other',
            'entity',
            'field',
            'type',
            'Name\\Class\\Entity',
            false,
            null,
            null
        );

        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);

        $this->reservationAccessor->getValue(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->reservationAccessor->getEntity($this->reservation, Argument::any())->shouldNotBeCalled();

        $eligibilityConditionChecker = $this->createTestObject();
        $result                      = $eligibilityConditionChecker->getIneligibleIdsByConfiguration(
            $this->reservation->getBorrower(),
            $programEligibilityConfiguration,
            false
        );

        static::assertEmpty($result);
    }

    /**
     * @covers ::getIneligibleIdsByConfiguration
     */
    public function testGetIneligibleIdsByConfigurationWithEligibleConditions(): void
    {
        $this->withBorrower($this->reservation);
        $entity = $this->reservation->getBorrower();

        $field                           = $this->createRegistrationNumberField();
        $leftField1                      = $this->createEmployeesNumberField();
        $leftField2                      = $this->createTurnoverField();
        $rightField2                     = $this->createTotalAssetsField();
        $leftField3                      = $this->createCreationInProgressField();
        $leftField4                      = $this->createActivityDepartmentField();
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);

        $programEligibilityCondition1 = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $leftField1,
            null,
            'gte',
            'value'
        ))->setValue('42');
        $programEligibilityCondition2 = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $leftField2,
            $rightField2,
            'lt',
            'rate'
        ))->setValue('42');
        $programEligibilityCondition3 = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $leftField3,
            null,
            'eq',
            'bool'
        ))->setValue('0');
        $programEligibilityCondition4 = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $leftField4,
            null,
            'eq',
            'list'
        ))->addProgramChoiceOption($this->reservation->getBorrower()->getAddressDepartment());

        $programEligibilityConditions = new ArrayCollection([
            $programEligibilityCondition1,
            $programEligibilityCondition2,
            $programEligibilityCondition3,
            $programEligibilityCondition4,
        ]);
        $this->forcePropertyValue(
            $programEligibilityConfiguration,
            'programEligibilityConditions',
            $programEligibilityConditions
        );

        // condition 1 - value
        $this->reservationAccessor->getEntity($this->reservation, $leftField1)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $leftField1)
            ->shouldBeCalledOnce()
            ->willReturn(42)
        ;

        // condition 2 - rate
        $this->reservationAccessor->getEntity($this->reservation, $rightField2)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $rightField2)
            ->shouldBeCalledOnce()
            ->willReturn('2048')
        ;
        $this->reservationAccessor->getEntity($this->reservation, $leftField2)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $leftField2)->shouldBeCalledOnce()->willReturn('128');

        // condition 3 - bool
        $this->reservationAccessor->getEntity($this->reservation, $leftField3)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $leftField3)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        // condition 4 - list
        $this->reservationAccessor->getEntity($this->reservation, $leftField4)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $leftField4)
            ->shouldBeCalledOnce()
            ->willReturn($entity->getAddressDepartment())
        ;

        $eligibilityConditionChecker = $this->createTestObject();
        $result                      = $eligibilityConditionChecker->getIneligibleIdsByConfiguration(
            $entity,
            $programEligibilityConfiguration,
            false
        );

        static::assertEmpty($result);
    }

    /**
     * @covers ::getIneligibleIdsByConfiguration
     */
    public function testGetIneligibleIdsByConfigurationWithIneligibleConditions(): void
    {
        $entity = $this->reservation->getBorrower();

        $this->reservation->getBorrower()
            ->setYoungFarmer(true)
            ->setTotalAssets(new NullableMoney('EUR', '42'))
            ->setTurnover(new NullableMoney('EUR', '0'))
        ;

        $field                           = $this->createRegistrationNumberField();
        $totalAssetsField                = $this->createTotalAssetsField();
        $turnoverField                   = $this->createTurnoverField();
        $youngFarmerField                = $this->createYoungFarmerField();
        $activityCountryField            = $this->createActivityCountryField();
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);

        $programEligibilityCondition1 = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $totalAssetsField,
            null,
            'gt',
            'value'
        ))->setValue('2048');
        $programEligibilityCondition2 = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $turnoverField,
            $totalAssetsField,
            'gt',
            'rate'
        ))->setValue('10');
        $programEligibilityCondition3 = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $youngFarmerField,
            null,
            'eq',
            'bool'
        ))->setValue('0');
        $programEligibilityCondition4 = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $activityCountryField,
            null,
            'eq',
            'list'
        ))->addProgramChoiceOption(
            new ProgramChoiceOption($this->reservation->getProgram(), 'RF', $activityCountryField)
        );

        $this->forcePropertyValue($programEligibilityCondition1, 'id', '1');
        $this->forcePropertyValue($programEligibilityCondition2, 'id', '2');
        $this->forcePropertyValue($programEligibilityCondition3, 'id', '3');
        $this->forcePropertyValue($programEligibilityCondition4, 'id', '4');
        $this->forcePropertyValue(
            $programEligibilityConfiguration,
            'programEligibilityConditions',
            new ArrayCollection([
                $programEligibilityCondition1,
                $programEligibilityCondition2,
                $programEligibilityCondition3,
                $programEligibilityCondition4,
            ])
        );

        $this->reservationAccessor->getEntity($this->reservation, $totalAssetsField)
            ->shouldBeCalledTimes(2)
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $totalAssetsField)
            ->shouldBeCalledTimes(2)
            ->willReturn('42')
        ;
        $this->reservationAccessor->getEntity($this->reservation, $turnoverField)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $turnoverField)
            ->shouldBeCalledOnce()
            ->willReturn('0')
        ;
        $this->reservationAccessor->getEntity($this->reservation, $youngFarmerField)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $youngFarmerField)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;
        $this->reservationAccessor->getEntity($this->reservation, $activityCountryField)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $activityCountryField)
            ->shouldBeCalledOnce()
            ->willReturn($entity->getAddressCountry())
        ;

        $eligibilityConditionChecker = $this->createTestObject();
        $result                      = $eligibilityConditionChecker->getIneligibleIdsByConfiguration(
            $entity,
            $programEligibilityConfiguration,
            false
        );

        static::assertSame([1, 2, 3, 4], $result);
    }

    private function createTestObject(): EligibilityConditionChecker
    {
        return new EligibilityConditionChecker($this->reservationAccessor->reveal());
    }
}
