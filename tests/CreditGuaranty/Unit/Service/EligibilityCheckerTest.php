<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\Unit\Service;

use Doctrine\Common\Collections\ArrayCollection;
use LogicException;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Unilend\CreditGuaranty\Entity\Borrower;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\FinancingObject;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use Unilend\CreditGuaranty\Entity\Project;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Repository\FieldRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityConfigurationRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityRepository;
use Unilend\CreditGuaranty\Service\EligibilityChecker;
use Unilend\CreditGuaranty\Service\EligibilityConditionChecker;
use Unilend\CreditGuaranty\Service\EligibilityHelper;

/**
 * @coversDefaultClass \Unilend\CreditGuaranty\Service\EligibilityChecker
 *
 * @internal
 */
class EligibilityCheckerTest extends AbstractEligibilityTest
{
    /** @var FieldRepository|ObjectProphecy */
    private $fieldRepository;

    /** @var ProgramEligibilityRepository|ObjectProphecy */
    private $programEligibilityRepository;

    /** @var ProgramEligibilityConfigurationRepository|ObjectProphecy */
    private $programEligibilityConfigurationRepository;

    /** @var EligibilityHelper|ObjectProphecy */
    private $eligibilityHelper;

    /** @var EligibilityConditionChecker|ObjectProphecy */
    private $eligibilityConditionChecker;

    /** @var Reservation */
    private $reservation;

    /** @var string */
    private $category = 'test';

    protected function setUp(): void
    {
        $this->fieldRepository                           = $this->prophesize(FieldRepository::class);
        $this->programEligibilityRepository              = $this->prophesize(ProgramEligibilityRepository::class);
        $this->programEligibilityConfigurationRepository = $this->prophesize(ProgramEligibilityConfigurationRepository::class);
        $this->eligibilityHelper                         = $this->prophesize(EligibilityHelper::class);
        $this->eligibilityConditionChecker               = $this->prophesize(EligibilityConditionChecker::class);
        $this->reservation                               = $this->createReservation();
    }

    protected function tearDown(): void
    {
        $this->fieldRepository                           = null;
        $this->programEligibilityRepository              = null;
        $this->programEligibilityConfigurationRepository = null;
        $this->eligibilityHelper                         = null;
        $this->eligibilityConditionChecker               = null;
        $this->reservation                               = null;
    }

    public function testCheckByCategoryEligible(): void
    {
        $program                          = $this->reservation->getProgram();
        $field1                           = new Field('alias_1', $this->category, 'other', 'project', 'fundingMoney::amount', Project::class, false, 'money', null);
        $field2                           = new Field('alias_2', $this->category, 'bool', 'borrower', 'creationInProgress', Borrower::class, false, null, null);
        $field3                           = new Field('alias_3', $this->category, 'other', '', '', '', false, null, null);
        $field4                           = new Field('alias_4', $this->category, 'list', 'borrower', 'legalForm', Borrower::class, false, null, null);
        $fields                           = [$field1, $field2, $field3, $field4];
        $legalFormOption                  = new ProgramChoiceOption($program, 'legal form', $field4);
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibility2              = new ProgramEligibility($program, $field2);
        $programEligibility4              = new ProgramEligibility($program, $field4);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, null, true);
        $programEligibilityConfiguration2 = new ProgramEligibilityConfiguration($programEligibility2, null, '0', true);
        $programEligibilityConfiguration4 = new ProgramEligibilityConfiguration($programEligibility4, $legalFormOption, null, true);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);

        // configuration 1 - other
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($this->reservation, $field1)->shouldBeCalledOnce()->willReturn($this->reservation->getProject());
        $this->eligibilityHelper->getValue($this->reservation->getProject(), $field1)->shouldBeCalledOnce()->willReturn('42');
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration1)->shouldBeCalledOnce()->willReturn(true);

        // configuration 2 - bool
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field2])->shouldBeCalledOnce()->willReturn($programEligibility2);
        $this->eligibilityHelper->getEntity($this->reservation, $field2)->shouldBeCalledOnce()->willReturn($this->reservation->getBorrower());
        $this->eligibilityHelper->getValue($this->reservation->getBorrower(), $field2)->shouldBeCalledOnce()->willReturn(false);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility2,
            'value'              => 0,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration2);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration2)->shouldBeCalledOnce()->willReturn(true);

        // configuration 3 - bypassed because targetPropertyAccessPath is empty
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field3])->shouldNotBeCalled();
        $this->eligibilityHelper->getEntity($this->reservation, $field3)->shouldNotBeCalled();
        $this->eligibilityHelper->getValue(Argument::any(), $field3)->shouldNotBeCalled();

        // configuration 4 - list
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field4])->shouldBeCalledOnce()->willReturn($programEligibility4);
        $this->eligibilityHelper->getEntity($this->reservation, $field4)->shouldBeCalledOnce()->willReturn($this->reservation->getBorrower());
        $this->eligibilityHelper->getValue($this->reservation->getBorrower(), $field4)->shouldBeCalledOnce()->willReturn($legalFormOption);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility4,
            'programChoiceOption' => $legalFormOption,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration4);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration4)->shouldBeCalledOnce()->willReturn(true);

        $eligibilityChecker = $this->createTestObject();
        static::assertTrue($eligibilityChecker->checkByCategory($this->reservation, $this->category));
    }

    public function testCheckByCategoryWithoutFields(): void
    {
        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn([]);
        $this->programEligibilityRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->eligibilityHelper->getEntity(Argument::cetera())->shouldNotBeCalled();
        $this->eligibilityHelper->getValue(Argument::cetera())->shouldNotBeCalled();
        $this->programEligibilityConfigurationRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->eligibilityConditionChecker->checkByConfiguration(Argument::cetera())->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        static::assertTrue($eligibilityChecker->checkByCategory($this->reservation, $this->category));
    }

    public function testCheckByCategoryWithoutProgramEligibility(): void
    {
        $program = $this->reservation->getProgram();
        $field1  = new Field('alias_1', $this->category, 'bool', 'borrower', 'creationInProgress', Borrower::class, false, null, null);
        $fields  = [$field1];

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn(null);
        $this->eligibilityHelper->getEntity(Argument::cetera())->shouldNotBeCalled();
        $this->eligibilityHelper->getValue(Argument::cetera())->shouldNotBeCalled();
        $this->programEligibilityConfigurationRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->eligibilityConditionChecker->checkByConfiguration(Argument::cetera())->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->checkByCategory($this->reservation, $this->category);
    }

    public function testCheckByCategoryAndOtherTypeWithoutValue(): void
    {
        $program             = $this->reservation->getProgram();
        $field1              = new Field('alias_1', $this->category, 'other', 'borrower', 'taxNumber', Borrower::class, false, null, null);
        $fields              = [$field1];
        $programEligibility1 = new ProgramEligibility($program, $field1);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($this->reservation, $field1)->shouldBeCalledOnce()->willReturn($this->reservation->getBorrower());
        $this->eligibilityHelper->getValue($this->reservation->getBorrower(), $field1)->shouldBeCalledOnce()->willReturn(null);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])->shouldNotBeCalled();
        $this->eligibilityConditionChecker->checkByConfiguration(Argument::cetera())->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($this->reservation, $this->category));
    }

    public function testCheckByCategoryAndOtherTypeWithoutConfiguration(): void
    {
        $program             = $this->reservation->getProgram();
        $field1              = new Field('alias_1', $this->category, 'other', 'borrower', 'companyName', Borrower::class, false, null, null);
        $fields              = [$field1];
        $programEligibility1 = new ProgramEligibility($program, $field1);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($this->reservation, $field1)->shouldBeCalledOnce()->willReturn($this->reservation->getBorrower());
        $this->eligibilityHelper->getValue($this->reservation->getBorrower(), $field1)->shouldBeCalledOnce()->willReturn('Borrower Company');
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])->shouldBeCalledOnce()->willReturn(null);
        $this->eligibilityConditionChecker->checkByConfiguration(Argument::cetera())->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->checkByCategory($this->reservation, $this->category);
    }

    public function testCheckByCategoryAndOtherTypeWithConfigurationIneligible(): void
    {
        $financingObject                  = $this->createFinancingObject($this->reservation);
        $program                          = $this->reservation->getProgram();
        $field1                           = new Field('alias_1', $this->category, 'other', 'financingObjects', 'loanDuration', FinancingObject::class, false, null, null);
        $fields                           = [$field1];
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, null, false);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($this->reservation, $field1)->shouldBeCalledOnce()->willReturn(new ArrayCollection([$financingObject]));
        $this->eligibilityHelper->getValue($financingObject, $field1)->shouldBeCalledOnce()->willReturn(4);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration1)->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($this->reservation, $this->category));
    }

    public function testCheckByCategoryAndOtherTypeWithConditionsIneligible(): void
    {
        $financingObject                  = $this->createFinancingObject($this->reservation);
        $program                          = $this->reservation->getProgram();
        $field1                           = new Field('alias_1', $this->category, 'other', 'financingObjects', 'loanDuration', FinancingObject::class, false, null, null);
        $fields                           = [$field1];
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, null, true);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($this->reservation, $field1)->shouldBeCalledOnce()->willReturn(new ArrayCollection([$financingObject]));
        $this->eligibilityHelper->getValue($financingObject, $field1)->shouldBeCalledOnce()->willReturn(4);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration1)->shouldBeCalledOnce()->willReturn(false);

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($this->reservation, $this->category));
    }

    public function testCheckByCategoryAndBoolTypeWithoutConfiguration(): void
    {
        $program             = $this->reservation->getProgram();
        $field1              = new Field('alias_1', $this->category, 'bool', 'borrower', 'subsidiary', Borrower::class, false, null, null);
        $fields              = [$field1];
        $programEligibility1 = new ProgramEligibility($program, $field1);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($this->reservation, $field1)->shouldBeCalledOnce()->willReturn($this->reservation->getBorrower());
        $this->eligibilityHelper->getValue($this->reservation->getBorrower(), $field1)->shouldBeCalledOnce()->willReturn(true);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
            'value'              => 1,
        ])->shouldBeCalledOnce()->willReturn(null);
        $this->eligibilityConditionChecker->checkByConfiguration(Argument::cetera())->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->checkByCategory($this->reservation, $this->category);
    }

    public function testCheckByCategoryAndBoolTypeWithConfigurationIneligible(): void
    {
        $program                          = $this->reservation->getProgram();
        $field1                           = new Field('alias_1', $this->category, 'bool', 'borrower', 'youngFarmer', Borrower::class, false, null, null);
        $fields                           = [$field1];
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, '1', false);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($this->reservation, $field1)->shouldBeCalledOnce()->willReturn($this->reservation->getBorrower());
        $this->eligibilityHelper->getValue($this->reservation->getBorrower(), $field1)->shouldBeCalledOnce()->willReturn(true);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
            'value'              => 1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration1)->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($this->reservation, $this->category));
    }

    public function testCheckByCategoryAndBoolTypeWithConditionsIneligible(): void
    {
        $program                          = $this->reservation->getProgram();
        $field1                           = new Field('alias_1', $this->category, 'bool', 'borrower', 'youngFarmer', Borrower::class, false, null, null);
        $fields                           = [$field1];
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, '1', true);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($this->reservation, $field1)->shouldBeCalledOnce()->willReturn($this->reservation->getBorrower());
        $this->eligibilityHelper->getValue($this->reservation->getBorrower(), $field1)->shouldBeCalledOnce()->willReturn(true);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
            'value'              => 1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration1)->shouldBeCalledOnce()->willReturn(false);

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($this->reservation, $this->category));
    }

    public function testCheckByCategoryAndListTypeWithoutConfiguration(): void
    {
        $program              = $this->reservation->getProgram();
        $field1               = new Field('alias_1', $this->category, 'list', 'borrower', 'addressCountry', Borrower::class, false, null, null);
        $fields               = [$field1];
        $programChoiceOption1 = new ProgramChoiceOption($program, 'USA', $field1);
        $programEligibility1  = new ProgramEligibility($program, $field1);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($this->reservation, $field1)->shouldBeCalledOnce()->willReturn($this->reservation->getBorrower());
        $this->eligibilityHelper->getValue($this->reservation->getBorrower(), $field1)->shouldBeCalledOnce()->willReturn($programChoiceOption1);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility1,
            'programChoiceOption' => $programChoiceOption1,
        ])->shouldBeCalledOnce()->willReturn(null);
        $this->eligibilityConditionChecker->checkByConfiguration(Argument::cetera())->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->checkByCategory($this->reservation, $this->category);
    }

    public function testCheckByCategoryAndListTypeWithConfigurationIneligible(): void
    {
        $program                          = $this->reservation->getProgram();
        $project                          = $this->reservation->getProject();
        $field1                           = new Field('alias_1', $this->category, 'list', 'project', 'investmentThematic', Project::class, false, null, null);
        $fields                           = [$field1];
        $programChoiceOption1             = new ProgramChoiceOption($program, 'investment thematic', $field1);
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, '1', false);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($this->reservation, $field1)->shouldBeCalledOnce()->willReturn($project);
        $this->eligibilityHelper->getValue($project, $field1)->shouldBeCalledOnce()->willReturn($programChoiceOption1);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility1,
            'programChoiceOption' => $programChoiceOption1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration1)->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($this->reservation, $this->category));
    }

    public function testCheckByCategoryAndListTypeWithConditionsIneligible(): void
    {
        $program                          = $this->reservation->getProgram();
        $project                          = $this->reservation->getProject();
        $field1                           = new Field('alias_1', $this->category, 'list', 'project', 'investmentThematic', Project::class, false, null, null);
        $fields                           = [$field1];
        $programChoiceOption1             = new ProgramChoiceOption($program, 'investment thematic', $field1);
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, '1', true);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($this->reservation, $field1)->shouldBeCalledOnce()->willReturn($project);
        $this->eligibilityHelper->getValue($project, $field1)->shouldBeCalledOnce()->willReturn($programChoiceOption1);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility1,
            'programChoiceOption' => $programChoiceOption1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration1)->shouldBeCalledOnce()->willReturn(false);

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($this->reservation, $this->category));
    }

    private function createTestObject(): EligibilityChecker
    {
        return new EligibilityChecker(
            $this->fieldRepository->reveal(),
            $this->programEligibilityRepository->reveal(),
            $this->programEligibilityConfigurationRepository->reveal(),
            $this->eligibilityHelper->reveal(),
            $this->eligibilityConditionChecker->reveal()
        );
    }
}
