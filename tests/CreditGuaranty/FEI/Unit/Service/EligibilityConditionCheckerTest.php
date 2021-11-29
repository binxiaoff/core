<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service;

use Doctrine\Common\Collections\ArrayCollection;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityCondition;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConditionRepository;
use KLS\CreditGuaranty\FEI\Service\EligibilityConditionChecker;
use KLS\CreditGuaranty\FEI\Service\ReservationAccessor;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ReservationSetTrait;
use LogicException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Service\EligibilityConditionChecker
 *
 * @internal
 */
class EligibilityConditionCheckerTest extends TestCase
{
    use ProphecyTrait;
    use ReservationSetTrait;

    /** @var ReservationAccessor|ObjectProphecy */
    private $reservationAccessor;

    /** @var ReservationAccessor|ObjectProphecy */
    private $programEligibilityConditionRepository;

    /** @var Reservation */
    private $reservation;

    protected function setUp(): void
    {
        $this->programEligibilityConditionRepository = $this->prophesize(ProgramEligibilityConditionRepository::class);
        $this->reservationAccessor                   = $this->prophesize(ReservationAccessor::class);
        $this->reservation                           = $this->createReservation();
    }

    protected function tearDown(): void
    {
        $this->programEligibilityConditionRepository = null;
        $this->reservationAccessor                   = null;
        $this->reservation                           = null;
    }

    /**
     * @covers ::checkByConfiguration
     */
    public function testCheckByEligibilityConfigurationWithoutConditions(): void
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

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])
            ->shouldBeCalledOnce()
            ->willReturn([])
        ;
        $this->reservationAccessor->getEntity($this->reservation, Argument::any())->shouldNotBeCalled();
        $this->reservationAccessor->getValue(Argument::any(), Argument::any())->shouldNotBeCalled();

        $eligibilityConditionChecker = $this->createTestObject();
        $result                      = $eligibilityConditionChecker->checkByConfiguration(
            $this->reservation,
            $programEligibilityConfiguration
        );

        static::assertTrue($result);
    }

    /**
     * @covers ::checkByConfiguration
     */
    public function testCheckByEligibilityConfigurationWithEligibleConditions(): void
    {
        $this->withBorrower($this->reservation);
        $entity = $this->reservation->getBorrower();

        $field                           = $this->createSiretField();
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
            'eq',
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

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilityConditions)
        ;

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
            $this->reservation,
            $programEligibilityConfiguration
        );

        static::assertTrue($result);
    }

    /**
     * @covers ::checkByConfiguration
     */
    public function testCheckByEligibilityConfigurationWithIneligibleValueTypeCondition(): void
    {
        $entity = $this->reservation->getBorrower();

        $field                           = $this->createSiretField();
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

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConditions);

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
            $this->reservation,
            $programEligibilityConfiguration
        );

        static::assertFalse($result);
    }

    /**
     * @covers ::checkByConfiguration
     */
    public function testCheckByEligibilityConfigurationWithIneligibleRateTypeCondition(): void
    {
        $entity          = $this->reservation->getProject();
        $financingObject = $this->createFinancingObject($this->reservation, true);

        $field                           = $this->createLoanMoneyField();
        $rightField1                     = $this->createProjectTotalAmountField();
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);
        $programEligibilityCondition1    = (new ProgramEligibilityCondition(
            $programEligibilityConfiguration,
            $field,
            $rightField1,
            'gte',
            'rate'
        ))->setValue('42');

        $programEligibilityConditions = new ArrayCollection([$programEligibilityCondition1]);

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConditions);

        $this->reservationAccessor->getEntity($this->reservation, $rightField1)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $rightField1)
            ->shouldBeCalledOnce()
            ->willReturn('42')
        ;
        $this->reservationAccessor->getEntity($this->reservation, $field)
            ->shouldBeCalledOnce()
            ->willReturn(new ArrayCollection([$financingObject]))
        ;
        $this->reservationAccessor->getValue($financingObject, $field)
            ->shouldBeCalledOnce()
            ->willReturn('42')
        ;

        $eligibilityConditionChecker = $this->createTestObject();
        $result                      = $eligibilityConditionChecker->checkByConfiguration(
            $this->reservation,
            $programEligibilityConfiguration
        );

        static::assertFalse($result);
    }

    /**
     * @covers ::checkByConfiguration
     */
    public function testCheckByEligibilityConfigurationWithIneligibleBoolTypeCondition(): void
    {
        $entity = $this->reservation->getBorrower();

        $field                           = $this->createSiretField();
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

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConditions);

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
            $this->reservation,
            $programEligibilityConfiguration
        );

        static::assertFalse($result);
    }

    /**
     * @covers ::checkByConfiguration
     */
    public function testCheckByEligibilityConfigurationWithIneligibleListTypeCondition(): void
    {
        $entity = $this->reservation->getBorrower();

        $field                           = $this->createSiretField();
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

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConditions);

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
            $this->reservation,
            $programEligibilityConfiguration
        );

        static::assertFalse($result);
    }

    /**
     * @covers ::checkByConfiguration
     */
    public function testCheckByEligibilityConfigurationExceptionWithoutRightOperandFieldInRateTypeCondition(): void
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

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilityConditions)
        ;

        $this->reservationAccessor->getEntity($this->reservation, $field)->shouldNotBeCalled();
        $this->reservationAccessor->getValue($this->reservation->getProject(), $field)->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityConditionChecker = $this->createTestObject();
        $eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration);
    }

    /**
     * @covers ::checkByConfiguration
     */
    public function testCheckByEligibilityConfigurationExceptionWithInvalidRightOperandFieldInRateTypeCondition(): void
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
            'gte',
            'rate'
        ))->setValue('42');

        $programEligibilityConditions = new ArrayCollection([$programEligibilityCondition1]);

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilityConditions)
        ;

        $this->reservationAccessor->getEntity($this->reservation, $rightField1)
            ->shouldBeCalledOnce()
            ->willReturn(new ArrayCollection([$financingObject]))
        ;
        $this->reservationAccessor->getValue($financingObject, $rightField1)->shouldNotBeCalled();
        $this->reservationAccessor->getEntity($this->reservation, $field)->shouldNotBeCalled();
        $this->reservationAccessor->getValue($this->reservation->getProject(), $field)->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityConditionChecker = $this->createTestObject();
        $eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration);
    }

    private function createTestObject(): EligibilityConditionChecker
    {
        return new EligibilityConditionChecker(
            $this->programEligibilityConditionRepository->reveal(),
            $this->reservationAccessor->reveal()
        );
    }
}
