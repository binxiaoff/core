<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service;

use Doctrine\Common\Collections\ArrayCollection;
use KLS\CreditGuaranty\FEI\Entity\Borrower;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityCondition;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\FEI\Entity\Project;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConditionRepository;
use KLS\CreditGuaranty\FEI\Service\EligibilityConditionChecker;
use KLS\CreditGuaranty\FEI\Service\EligibilityHelper;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ReservationSetTrait;
use LogicException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Service\EligibilityConditionChecker
 *
 * @internal
 */
class EligibilityConditionCheckerTest extends TestCase
{
    use ReservationSetTrait;

    /** @var EligibilityHelper|ObjectProphecy */
    private $eligibilityHelper;

    /** @var EligibilityHelper|ObjectProphecy */
    private $programEligibilityConditionRepository;

    /** @var Reservation */
    private $reservation;

    protected function setUp(): void
    {
        $this->programEligibilityConditionRepository = $this->prophesize(ProgramEligibilityConditionRepository::class);
        $this->eligibilityHelper                     = $this->prophesize(EligibilityHelper::class);
        $this->reservation                           = $this->createReservation();
    }

    protected function tearDown(): void
    {
        $this->programEligibilityConditionRepository = null;
        $this->eligibilityHelper                     = null;
        $this->reservation                           = null;
    }

    public function testCheckByEligibilityConfigurationWithoutConditions(): void
    {
        $field                           = new Field('alias_1', Field::TAG_ELIGIBILITY, 'test', 'other', 'entity', 'field', 'type', 'Name\\Class\\Entity', false, null, null);
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])->shouldBeCalledOnce()->willReturn([]);
        $this->eligibilityHelper->getEntity($this->reservation, Argument::any())->shouldNotBeCalled();
        $this->eligibilityHelper->getValue(Argument::any(), Argument::any())->shouldNotBeCalled();

        $eligibilityConditionChecker = $this->createTestObject();
        $result                      = $eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration);

        static::assertTrue($result);
    }

    public function testCheckByEligibilityConfigurationWithConditionsEligible(): void
    {
        $entity                          = $this->reservation->getBorrower();
        $field                           = new Field('alias_1', Field::TAG_ELIGIBILITY, 'test', 'other', 'borrower', 'siret', 'string', Borrower::class, false, null, null);
        $leftField1                      = new Field('left_alias_1', Field::TAG_ELIGIBILITY, 'test', 'other', 'borrower', 'employeesNumber', 'int', Borrower::class, true, 'person', null);
        $leftField2                      = new Field('left_alias_2', Field::TAG_ELIGIBILITY, 'test', 'other', 'borrower', 'turnover', 'MoneyInterface', Borrower::class, true, 'money', null);
        $rightField2                     = new Field('right_alias_2', Field::TAG_ELIGIBILITY, 'test', 'other', 'borrower', 'totalAssets', 'MoneyInterface', Borrower::class, true, 'money', null);
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);
        $programEligibilityCondition1    = new ProgramEligibilityCondition($programEligibilityConfiguration, $leftField1, null, 'eq', 'value', '42');
        $programEligibilityCondition2    = new ProgramEligibilityCondition($programEligibilityConfiguration, $leftField2, $rightField2, 'lt', 'rate', '42');
        $programEligibilityConditions    = new ArrayCollection([$programEligibilityCondition1, $programEligibilityCondition2]);

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConditions);

        // condition 1 - value
        $this->eligibilityHelper->getEntity($this->reservation, $leftField1)->shouldBeCalledOnce()->willReturn($entity);
        $this->eligibilityHelper->getValue($entity, $leftField1)->shouldBeCalledOnce()->willReturn(42);

        // condition 2 - rate
        $this->eligibilityHelper->getEntity($this->reservation, $rightField2)->shouldBeCalledOnce()->willReturn($entity);
        $this->eligibilityHelper->getValue($entity, $rightField2)->shouldBeCalledOnce()->willReturn('2048');
        $this->eligibilityHelper->getEntity($this->reservation, $leftField2)->shouldBeCalledOnce()->willReturn($entity);
        $this->eligibilityHelper->getValue($entity, $leftField2)->shouldBeCalledOnce()->willReturn('128');

        $eligibilityConditionChecker = $this->createTestObject();
        $result                      = $eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration);

        static::assertTrue($result);
    }

    public function testCheckByEligibilityConfigurationWithValueTypeConditionIneligible(): void
    {
        $entity                          = $this->reservation->getBorrower();
        $field                           = new Field('alias_1', Field::TAG_ELIGIBILITY, 'test', 'other', 'borrower', 'siret', 'string', Borrower::class, false, null, null);
        $leftField1                      = new Field('left_alias_1', Field::TAG_ELIGIBILITY, 'test', 'other', 'borrower', 'totalAssets', 'MoneyInterface', Borrower::class, true, 'money', null);
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);
        $programEligibilityCondition1    = new ProgramEligibilityCondition($programEligibilityConfiguration, $leftField1, null, 'gt', 'value', '2048');
        $programEligibilityConditions    = new ArrayCollection([$programEligibilityCondition1]);

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConditions);

        $this->eligibilityHelper->getEntity($this->reservation, $leftField1)->shouldBeCalledOnce()->willReturn($entity);
        $this->eligibilityHelper->getValue($entity, $leftField1)->shouldBeCalledOnce()->willReturn('2048');

        $eligibilityConditionChecker = $this->createTestObject();
        $result                      = $eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration);

        static::assertFalse($result);
    }

    public function testCheckByEligibilityConfigurationWithRateTypeConditionIneligible(): void
    {
        $entity                          = $this->reservation->getProject();
        $financingObject                 = $this->createFinancingObject($this->reservation, true);
        $field                           = new Field('alias_1', Field::TAG_ELIGIBILITY, 'test', 'other', 'financingObjects', 'loanMoney', 'MoneyInterface', FinancingObject::class, true, 'money', null);
        $rightField1                     = new Field('right_alias_1', Field::TAG_ELIGIBILITY, 'test', 'other', 'project', 'fundingMoney', 'MoneyInterface', Project::class, true, 'money', null);
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);
        $programEligibilityCondition1    = new ProgramEligibilityCondition($programEligibilityConfiguration, $field, $rightField1, 'gte', 'rate', '42');
        $programEligibilityConditions    = new ArrayCollection([$programEligibilityCondition1]);

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConditions);

        $this->eligibilityHelper->getEntity($this->reservation, $rightField1)->shouldBeCalledOnce()->willReturn($entity);
        $this->eligibilityHelper->getValue($entity, $rightField1)->shouldBeCalledOnce()->willReturn('42');
        $this->eligibilityHelper->getEntity($this->reservation, $field)->shouldBeCalledOnce()->willReturn(new ArrayCollection([$financingObject]));
        $this->eligibilityHelper->getValue($financingObject, $field)->shouldBeCalledOnce()->willReturn('42');

        $eligibilityConditionChecker = $this->createTestObject();
        $result                      = $eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration);

        static::assertFalse($result);
    }

    public function testCheckByEligibilityConfigurationWithoutRightOperandFieldInRateTypeCondition(): void
    {
        $field                           = new Field('alias_1', Field::TAG_ELIGIBILITY, 'test', 'other', 'project', 'fundingMoney', 'MoneyInterface', Project::class, true, 'money', null);
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);
        $programEligibilityCondition1    = new ProgramEligibilityCondition($programEligibilityConfiguration, $field, null, 'lte', 'rate', '42');
        $programEligibilityConditions    = new ArrayCollection([$programEligibilityCondition1]);

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConditions);

        $this->eligibilityHelper->getEntity($this->reservation, $field)->shouldNotBeCalled();
        $this->eligibilityHelper->getValue($this->reservation->getProject(), $field)->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityConditionChecker = $this->createTestObject();
        $eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration);
    }

    public function testCheckByEligibilityConfigurationWithCollectionRightOperandFieldInRateTypeCondition(): void
    {
        $financingObject = $this->createFinancingObject($this->reservation, true);
        $field           = new Field('alias_1', Field::TAG_ELIGIBILITY, 'test', 'other', 'project', 'fundingMoney', 'MoneyInterface', Project::class, true, 'money', null);
        $rightField1     = new Field(
            'right_alias_1',
            Field::TAG_ELIGIBILITY,
            'test',
            'other',
            'financingObjects',
            'loanMoney',
            'MoneyInterface',
            FinancingObject::class,
            true,
            'money',
            null
        );
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);
        $programEligibilityCondition1    = new ProgramEligibilityCondition($programEligibilityConfiguration, $field, $rightField1, 'gte', 'rate', '42');
        $programEligibilityConditions    = new ArrayCollection([$programEligibilityCondition1]);

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConditions);

        $this->eligibilityHelper->getEntity($this->reservation, $rightField1)->shouldBeCalledOnce()->willReturn(new ArrayCollection([$financingObject]));
        $this->eligibilityHelper->getValue($financingObject, $rightField1)->shouldNotBeCalled();
        $this->eligibilityHelper->getEntity($this->reservation, $field)->shouldNotBeCalled();
        $this->eligibilityHelper->getValue($this->reservation->getProject(), $field)->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityConditionChecker = $this->createTestObject();
        $eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration);
    }

    private function createTestObject(): EligibilityConditionChecker
    {
        return new EligibilityConditionChecker(
            $this->programEligibilityConditionRepository->reveal(),
            $this->eligibilityHelper->reveal()
        );
    }
}
