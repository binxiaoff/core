<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\Unit\Service;

use Doctrine\Common\Collections\ArrayCollection;
use LogicException;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityCondition;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityConditionRepository;
use Unilend\CreditGuaranty\Service\EligibilityConditionChecker;
use Unilend\CreditGuaranty\Service\EligibilityHelper;

/**
 * @coversDefaultClass \Unilend\CreditGuaranty\Service\EligibilityConditionChecker
 *
 * @internal
 */
class EligibilityConditionCheckerTest extends AbstractEligibilityTest
{
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
        $field                           = new Field('alias_1', 'test', 'other', 'entity::field', false, null, null);
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])->shouldBeCalledOnce()->willReturn([]);
        $this->eligibilityHelper->getEntity($this->reservation, Argument::any())->shouldNotBeCalled();
        $this->eligibilityHelper->getValue($this->reservation->getProgram(), Argument::any(), Argument::any())->shouldNotBeCalled();

        $eligibilityConditionChecker = $this->createTestObject();
        static::assertTrue($eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration));
    }

    public function testCheckByEligibilityConfigurationWithConditionsEligible(): void
    {
        $entity                          = $this->reservation->getBorrower();
        $field                           = new Field('alias_1', 'test', 'other', 'borrower::siret', false, null, null);
        $leftField1                      = new Field('left_alias_1', 'test', 'other', 'borrower::employeesNumber', true, 'person', null);
        $leftField2                      = new Field('left_alias_2', 'test', 'other', 'borrower::turnover::amount', true, 'money', null);
        $rightField2                     = new Field('right_alias_2', 'test', 'other', 'borrower::totalAssets::amount', true, 'money', null);
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
        $this->eligibilityHelper->getValue($this->reservation->getProgram(), $entity, $leftField1)->shouldBeCalledOnce()->willReturn(42);

        // condition 2 - rate
        $this->eligibilityHelper->getEntity($this->reservation, $rightField2)->shouldBeCalledOnce()->willReturn($entity);
        $this->eligibilityHelper->getValue($this->reservation->getProgram(), $entity, $rightField2)->shouldBeCalledOnce()->willReturn('2048');
        $this->eligibilityHelper->getEntity($this->reservation, $leftField2)->shouldBeCalledOnce()->willReturn($entity);
        $this->eligibilityHelper->getValue($this->reservation->getProgram(), $entity, $leftField2)->shouldBeCalledOnce()->willReturn('128');

        $eligibilityConditionChecker = $this->createTestObject();
        static::assertTrue($eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration));
    }

    public function testCheckByEligibilityConfigurationWithValueTypeConditionIneligible(): void
    {
        $entity                          = $this->reservation->getBorrower();
        $field                           = new Field('alias_1', 'test', 'other', 'borrower::siret', false, null, null);
        $leftField1                      = new Field('left_alias_1', 'test', 'other', 'borrower::totalAssets::amount', true, 'money', null);
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);
        $programEligibilityCondition1    = new ProgramEligibilityCondition($programEligibilityConfiguration, $leftField1, null, 'gt', 'value', '2048');
        $programEligibilityConditions    = new ArrayCollection([$programEligibilityCondition1]);

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConditions);

        $this->eligibilityHelper->getEntity($this->reservation, $leftField1)->shouldBeCalledOnce()->willReturn($entity);
        $this->eligibilityHelper->getValue($this->reservation->getProgram(), $entity, $leftField1)->shouldBeCalledOnce()->willReturn('2048');

        $eligibilityConditionChecker = $this->createTestObject();
        static::assertFalse($eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration));
    }

    public function testCheckByEligibilityConfigurationWithRateTypeConditionIneligible(): void
    {
        $entity                          = $this->reservation->getProject();
        $financingObject                 = $this->createFinancingObject($this->reservation);
        $field                           = new Field('alias_1', 'test', 'other', 'financingObjects::loanMoney::amount', true, 'money', null);
        $rightField1                     = new Field('right_alias_1', 'test', 'other', 'project::fundingMoney::amount', true, 'money', null);
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);
        $programEligibilityCondition1    = new ProgramEligibilityCondition($programEligibilityConfiguration, $field, $rightField1, 'gte', 'rate', '42');
        $programEligibilityConditions    = new ArrayCollection([$programEligibilityCondition1]);

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConditions);

        $this->eligibilityHelper->getEntity($this->reservation, $rightField1)->shouldBeCalledOnce()->willReturn($entity);
        $this->eligibilityHelper->getValue($this->reservation->getProgram(), $entity, $rightField1)->shouldBeCalledOnce()->willReturn('42');
        $this->eligibilityHelper->getEntity($this->reservation, $field)->shouldBeCalledOnce()->willReturn(new ArrayCollection([$financingObject]));
        $this->eligibilityHelper->getValue($this->reservation->getProgram(), $financingObject, $field)->shouldBeCalledOnce()->willReturn('42');

        $eligibilityConditionChecker = $this->createTestObject();
        static::assertFalse($eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration));
    }

    public function testCheckByEligibilityConfigurationWithoutRightOperandFieldInRateTypeCondition(): void
    {
        $field                           = new Field('alias_1', 'test', 'other', 'project::fundingMoney::amount', true, 'money', null);
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);
        $programEligibilityCondition1    = new ProgramEligibilityCondition($programEligibilityConfiguration, $field, null, 'lte', 'rate', '42');
        $programEligibilityConditions    = new ArrayCollection([$programEligibilityCondition1]);

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConditions);

        $this->eligibilityHelper->getEntity($this->reservation, $field)->shouldNotBeCalled();
        $this->eligibilityHelper->getValue($this->reservation->getProgram(), $this->reservation->getProject(), $field)->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityConditionChecker = $this->createTestObject();
        $eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration);
    }

    public function testCheckByEligibilityConfigurationWithCollectionRightOperandFieldInRateTypeCondition(): void
    {
        $financingObject                 = $this->createFinancingObject($this->reservation);
        $field                           = new Field('alias_1', 'test', 'other', 'project::fundingMoney::amount', true, 'money', null);
        $rightField1                     = new Field('right_alias_1', 'test', 'other', 'financingObjects::loanMoney::amount', true, 'money', null);
        $programEligibility              = new ProgramEligibility($this->reservation->getProgram(), $field);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, true);
        $programEligibilityCondition1    = new ProgramEligibilityCondition($programEligibilityConfiguration, $field, $rightField1, 'gte', 'rate', '42');
        $programEligibilityConditions    = new ArrayCollection([$programEligibilityCondition1]);

        $this->programEligibilityConditionRepository->findBy([
            'programEligibilityConfiguration' => $programEligibilityConfiguration,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConditions);

        $this->eligibilityHelper->getEntity($this->reservation, $rightField1)->shouldBeCalledOnce()->willReturn(new ArrayCollection([$financingObject]));
        $this->eligibilityHelper->getValue($this->reservation->getProgram(), $financingObject, $rightField1)->shouldNotBeCalled();
        $this->eligibilityHelper->getEntity($this->reservation, $field)->shouldNotBeCalled();
        $this->eligibilityHelper->getValue($this->reservation->getProgram(), $this->reservation->getProject(), $field)->shouldNotBeCalled();

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
