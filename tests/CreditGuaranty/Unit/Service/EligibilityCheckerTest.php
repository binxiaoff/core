<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\Unit\Service;

use Doctrine\Common\Collections\ArrayCollection;
use LogicException;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
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

    /** @var string */
    private $category = 'test';

    protected function setUp(): void
    {
        $this->fieldRepository                           = $this->prophesize(FieldRepository::class);
        $this->programEligibilityRepository              = $this->prophesize(ProgramEligibilityRepository::class);
        $this->programEligibilityConfigurationRepository = $this->prophesize(ProgramEligibilityConfigurationRepository::class);
        $this->eligibilityHelper                         = $this->prophesize(EligibilityHelper::class);
        $this->eligibilityConditionChecker               = $this->prophesize(EligibilityConditionChecker::class);
    }

    protected function tearDown(): void
    {
        $this->fieldRepository                           = null;
        $this->programEligibilityRepository              = null;
        $this->programEligibilityConfigurationRepository = null;
        $this->eligibilityHelper                         = null;
        $this->eligibilityConditionChecker               = null;
    }

    public function testCheckByCategoryEligible(): void
    {
        $reservation                      = $this->createReservation();
        $program                          = $reservation->getProgram();
        $field1                           = new Field('alias_1', $this->category, 'other', 'project::fundingMoney', false, null, null);
        $field2                           = new Field('alias_2', $this->category, 'bool', 'borrower::creationInProgress', false, null, null);
        $field3                           = new Field('alias_3', $this->category, 'other', '', false, null, null);
        $field4                           = new Field('alias_4', $this->category, 'list', 'borrower::legalForm', false, null, null);
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
        $this->eligibilityHelper->getEntity($reservation, $field1)->shouldBeCalledOnce()->willReturn($reservation->getProject());
        $this->eligibilityHelper->getValue($program, $reservation->getProject(), $field1)->shouldBeCalledOnce()->willReturn($reservation->getProject()->getFundingMoney());
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($reservation, $programEligibilityConfiguration1)->shouldBeCalledOnce()->willReturn(true);

        // configuration 2 - bool
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field2])->shouldBeCalledOnce()->willReturn($programEligibility2);
        $this->eligibilityHelper->getEntity($reservation, $field2)->shouldBeCalledOnce()->willReturn($reservation->getBorrower());
        $this->eligibilityHelper->getValue($program, $reservation->getBorrower(), $field2)->shouldBeCalledOnce()->willReturn(false);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility2,
            'value'              => 0,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration2);
        $this->eligibilityConditionChecker->checkByConfiguration($reservation, $programEligibilityConfiguration2)->shouldBeCalledOnce()->willReturn(true);

        // configuration 3 - bypassed because targetPropertyAccessPath is empty
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field3])->shouldNotBeCalled();
        $this->eligibilityHelper->getEntity($reservation, $field3)->shouldNotBeCalled();
        $this->eligibilityHelper->getValue($program, Argument::any(), $field3)->shouldNotBeCalled();

        // configuration 4 - list
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field4])->shouldBeCalledOnce()->willReturn($programEligibility4);
        $this->eligibilityHelper->getEntity($reservation, $field4)->shouldBeCalledOnce()->willReturn($reservation->getBorrower());
        $this->eligibilityHelper->getValue($program, $reservation->getBorrower(), $field4)->shouldBeCalledOnce()->willReturn($legalFormOption);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility4,
            'programChoiceOption' => $legalFormOption,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration4);
        $this->eligibilityConditionChecker->checkByConfiguration($reservation, $programEligibilityConfiguration4)->shouldBeCalledOnce()->willReturn(true);

        $eligibilityChecker = $this->createTestObject();
        static::assertTrue($eligibilityChecker->checkByCategory($reservation, $this->category));
    }

    public function testCheckByCategoryWithoutFields(): void
    {
        $reservation = $this->createReservation();

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn([]);
        $this->programEligibilityRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->eligibilityHelper->getEntity(Argument::cetera())->shouldNotBeCalled();
        $this->eligibilityHelper->getValue(Argument::cetera())->shouldNotBeCalled();
        $this->programEligibilityConfigurationRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->eligibilityConditionChecker->checkByConfiguration(Argument::cetera())->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        static::assertTrue($eligibilityChecker->checkByCategory($reservation, $this->category));
    }

    public function testCheckByCategoryWithoutProgramEligibility(): void
    {
        $reservation = $this->createReservation();
        $program     = $reservation->getProgram();
        $field1      = new Field('alias_1', $this->category, 'bool', 'borrower::creationInProgress', false, null, null);
        $fields      = [$field1];

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn(null);
        $this->eligibilityHelper->getEntity(Argument::cetera())->shouldNotBeCalled();
        $this->eligibilityHelper->getValue(Argument::cetera())->shouldNotBeCalled();
        $this->programEligibilityConfigurationRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->eligibilityConditionChecker->checkByConfiguration(Argument::cetera())->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->checkByCategory($reservation, $this->category);
    }

    public function testCheckByCategoryAndOtherTypeWithoutValue(): void
    {
        $reservation         = $this->createReservation();
        $program             = $reservation->getProgram();
        $field1              = new Field('alias_1', $this->category, 'other', 'borrower::taxNumber', false, null, null);
        $fields              = [$field1];
        $programEligibility1 = new ProgramEligibility($program, $field1);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($reservation, $field1)->shouldBeCalledOnce()->willReturn($reservation->getBorrower());
        $this->eligibilityHelper->getValue($program, $reservation->getBorrower(), $field1)->shouldBeCalledOnce()->willReturn(null);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])->shouldNotBeCalled();
        $this->eligibilityConditionChecker->checkByConfiguration(Argument::cetera())->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($reservation, $this->category));
    }

    public function testCheckByCategoryAndOtherTypeWithoutConfiguration(): void
    {
        $reservation         = $this->createReservation();
        $program             = $reservation->getProgram();
        $field1              = new Field('alias_1', $this->category, 'other', 'borrower::companyName', false, null, null);
        $fields              = [$field1];
        $programEligibility1 = new ProgramEligibility($program, $field1);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($reservation, $field1)->shouldBeCalledOnce()->willReturn($reservation->getBorrower());
        $this->eligibilityHelper->getValue($program, $reservation->getBorrower(), $field1)->shouldBeCalledOnce()->willReturn('Borrower Company');
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])->shouldBeCalledOnce()->willReturn(null);
        $this->eligibilityConditionChecker->checkByConfiguration(Argument::cetera())->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->checkByCategory($reservation, $this->category);
    }

    public function testCheckByCategoryAndOtherTypeWithConfigurationIneligible(): void
    {
        $reservation                      = $this->createReservation();
        $financingObject                  = $this->createFinancingObject($reservation);
        $program                          = $reservation->getProgram();
        $field1                           = new Field('alias_1', $this->category, 'other', 'financingObjects::loanDuration', false, null, null);
        $fields                           = [$field1];
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, null, false);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($reservation, $field1)->shouldBeCalledOnce()->willReturn(new ArrayCollection([$financingObject]));
        $this->eligibilityHelper->getValue($program, $financingObject, $field1)->shouldBeCalledOnce()->willReturn(4);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($reservation, $programEligibilityConfiguration1)->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($reservation, $this->category));
    }

    public function testCheckByCategoryAndOtherTypeWithConditionsIneligible(): void
    {
        $reservation                      = $this->createReservation();
        $financingObject                  = $this->createFinancingObject($reservation);
        $program                          = $reservation->getProgram();
        $field1                           = new Field('alias_1', $this->category, 'other', 'financingObjects::loanDuration', false, null, null);
        $fields                           = [$field1];
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, null, true);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($reservation, $field1)->shouldBeCalledOnce()->willReturn(new ArrayCollection([$financingObject]));
        $this->eligibilityHelper->getValue($program, $financingObject, $field1)->shouldBeCalledOnce()->willReturn(4);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($reservation, $programEligibilityConfiguration1)->shouldBeCalledOnce()->willReturn(false);

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($reservation, $this->category));
    }

    public function testCheckByCategoryAndBoolTypeWithoutConfiguration(): void
    {
        $reservation         = $this->createReservation();
        $program             = $reservation->getProgram();
        $field1              = new Field('alias_1', $this->category, 'bool', 'borrowerBusinessActivity::subsidiary', false, null, null);
        $fields              = [$field1];
        $programEligibility1 = new ProgramEligibility($program, $field1);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($reservation, $field1)->shouldBeCalledOnce()->willReturn($reservation->getBorrowerBusinessActivity());
        $this->eligibilityHelper->getValue($program, $reservation->getBorrowerBusinessActivity(), $field1)->shouldBeCalledOnce()->willReturn(true);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
            'value'              => 1,
        ])->shouldBeCalledOnce()->willReturn(null);
        $this->eligibilityConditionChecker->checkByConfiguration(Argument::cetera())->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->checkByCategory($reservation, $this->category);
    }

    public function testCheckByCategoryAndBoolTypeWithConfigurationIneligible(): void
    {
        $reservation                      = $this->createReservation();
        $program                          = $reservation->getProgram();
        $field1                           = new Field('alias_1', $this->category, 'bool', 'borrowerBusinessActivity::receivingGrant', false, null, null);
        $fields                           = [$field1];
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, '1', false);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($reservation, $field1)->shouldBeCalledOnce()->willReturn($reservation->getBorrowerBusinessActivity());
        $this->eligibilityHelper->getValue($program, $reservation->getBorrowerBusinessActivity(), $field1)->shouldBeCalledOnce()->willReturn(true);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
            'value'              => 1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($reservation, $programEligibilityConfiguration1)->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($reservation, $this->category));
    }

    public function testCheckByCategoryAndBoolTypeWithConditionsIneligible(): void
    {
        $reservation                      = $this->createReservation();
        $program                          = $reservation->getProgram();
        $field1                           = new Field('alias_1', $this->category, 'bool', 'borrowerBusinessActivity::receivingGrant', false, null, null);
        $fields                           = [$field1];
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, '1', true);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($reservation, $field1)->shouldBeCalledOnce()->willReturn($reservation->getBorrowerBusinessActivity());
        $this->eligibilityHelper->getValue($program, $reservation->getBorrowerBusinessActivity(), $field1)->shouldBeCalledOnce()->willReturn(true);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
            'value'              => 1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($reservation, $programEligibilityConfiguration1)->shouldBeCalledOnce()->willReturn(false);

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($reservation, $this->category));
    }

    public function testCheckByCategoryAndListTypeWithoutConfiguration(): void
    {
        $reservation          = $this->createReservation();
        $program              = $reservation->getProgram();
        $field1               = new Field('alias_1', $this->category, 'list', 'borrowerBusinessActivity::address::country', false, null, null);
        $fields               = [$field1];
        $programChoiceOption1 = new ProgramChoiceOption($program, 'USA', $field1);
        $programEligibility1  = new ProgramEligibility($program, $field1);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($reservation, $field1)->shouldBeCalledOnce()->willReturn($reservation->getBorrowerBusinessActivity());
        $this->eligibilityHelper->getValue($program, $reservation->getBorrowerBusinessActivity(), $field1)->shouldBeCalledOnce()->willReturn($programChoiceOption1);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility1,
            'programChoiceOption' => $programChoiceOption1,
        ])->shouldBeCalledOnce()->willReturn(null);
        $this->eligibilityConditionChecker->checkByConfiguration(Argument::cetera())->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->checkByCategory($reservation, $this->category);
    }

    public function testCheckByCategoryAndListTypeWithConfigurationIneligible(): void
    {
        $reservation                      = $this->createReservation();
        $program                          = $reservation->getProgram();
        $field1                           = new Field('alias_1', $this->category, 'list', 'project::investmentThematic', false, null, null);
        $fields                           = [$field1];
        $programChoiceOption1             = new ProgramChoiceOption($program, 'investment thematic', $field1);
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, '1', false);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($reservation, $field1)->shouldBeCalledOnce()->willReturn($reservation->getProject());
        $this->eligibilityHelper->getValue($program, $reservation->getProject(), $field1)->shouldBeCalledOnce()->willReturn($programChoiceOption1);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility1,
            'programChoiceOption' => $programChoiceOption1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($reservation, $programEligibilityConfiguration1)->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($reservation, $this->category));
    }

    public function testCheckByCategoryAndListTypeWithConditionsIneligible(): void
    {
        $reservation                      = $this->createReservation();
        $program                          = $reservation->getProgram();
        $field1                           = new Field('alias_1', $this->category, 'list', 'project::investmentThematic', false, null, null);
        $fields                           = [$field1];
        $programChoiceOption1             = new ProgramChoiceOption($program, 'investment thematic', $field1);
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, '1', true);

        $this->fieldRepository->findBy(['category' => $this->category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($reservation, $field1)->shouldBeCalledOnce()->willReturn($reservation->getProject());
        $this->eligibilityHelper->getValue($program, $reservation->getProject(), $field1)->shouldBeCalledOnce()->willReturn($programChoiceOption1);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility1,
            'programChoiceOption' => $programChoiceOption1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($reservation, $programEligibilityConfiguration1)->shouldBeCalledOnce()->willReturn(false);

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($reservation, $this->category));
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
