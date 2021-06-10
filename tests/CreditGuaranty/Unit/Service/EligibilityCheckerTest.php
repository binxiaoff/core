<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\Unit\Service;

use LogicException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\CompanyGroup;
use Unilend\Core\Entity\CompanyGroupTag;
use Unilend\Core\Entity\Embeddable\Address;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Team;
use Unilend\Core\Entity\User;
use Unilend\CreditGuaranty\Entity\Borrower;
use Unilend\CreditGuaranty\Entity\BorrowerBusinessActivity;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use Unilend\CreditGuaranty\Entity\Project;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Repository\FieldRepository;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityConfigurationRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityRepository;
use Unilend\CreditGuaranty\Service\EligibilityChecker;

/**
 * @coversDefaultClass \Unilend\CreditGuaranty\Service\EligibilityChecker
 *
 * @internal
 */
class EligibilityCheckerTest extends TestCase
{
    /** @var FieldRepository|ObjectProphecy */
    private $fieldRepository;

    /** @var ProgramChoiceOptionRepository|ObjectProphecy */
    private $programChoiceOptionRepository;

    /** @var ProgramEligibilityRepository|ObjectProphecy */
    private $programEligibilityRepository;

    /** @var ProgramEligibilityConfigurationRepository|ObjectProphecy */
    private $programEligibilityConfigurationRepository;

    /** @var PropertyAccessorInterface|ObjectProphecy */
    private $propertyAccessor;

    protected function setUp(): void
    {
        $this->fieldRepository                           = $this->prophesize(FieldRepository::class);
        $this->programChoiceOptionRepository             = $this->prophesize(ProgramChoiceOptionRepository::class);
        $this->programEligibilityRepository              = $this->prophesize(ProgramEligibilityRepository::class);
        $this->programEligibilityConfigurationRepository = $this->prophesize(ProgramEligibilityConfigurationRepository::class);
        $this->propertyAccessor                          = $this->prophesize(PropertyAccessorInterface::class);
    }

    protected function tearDown(): void
    {
        $this->fieldRepository                           = null;
        $this->programChoiceOptionRepository             = null;
        $this->programEligibilityRepository              = null;
        $this->programEligibilityConfigurationRepository = null;
        $this->propertyAccessor                          = null;
    }

    public function testCheckByCategoryEligible(): void
    {
        $category                         = 'test';
        $reservation                      = $this->createReservation();
        $program                          = $reservation->getProgram();
        $field1                           = new Field('alias_1', $category, 'other', 'project::fundingMoney', false, null, null);
        $field2                           = new Field('alias_2', $category, 'bool', 'borrower::creationInProgress', false, null, null);
        $field3                           = new Field('alias_3', $category, 'other', '', false, null, null);
        $field4                           = new Field('alias_4', $category, 'list', 'borrower::legalForm', false, null, null);
        $field5                           = new Field('alias_5', $category, 'list', 'project::projectNafCode', false, null, null);
        $fields                           = [$field1, $field2, $field3, $field4];
        $legalFormOption                  = new ProgramChoiceOption($program, 'legal form', $field4);
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibility2              = new ProgramEligibility($program, $field2);
        $programEligibility4              = new ProgramEligibility($program, $field4);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, null, true);
        $programEligibilityConfiguration2 = new ProgramEligibilityConfiguration($programEligibility2, null, '0', true);
        $programEligibilityConfiguration4 = new ProgramEligibilityConfiguration($programEligibility4, $legalFormOption, null, true);

        $fundingMoney = new Money('eur', '42');
        $reservation->getBorrower()->setLegalForm($legalFormOption);
        $reservation->getBorrower()->setCreationInProgress(false);
        $reservation->setProject(new Project(
            $fundingMoney,
            new ProgramChoiceOption($program, 'investment thematic', $field1),
            new ProgramChoiceOption($program, 'N42', $field5),
        ));

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programChoiceOptionRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        // configuration 1 - other
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->propertyAccessor->getValue($reservation, 'project')->shouldBeCalledOnce()->willReturn($reservation->getProject());
        $this->propertyAccessor->getValue($reservation->getProject(), 'fundingMoney')->shouldBeCalledOnce()->willReturn($fundingMoney);
        $this->programEligibilityConfigurationRepository->findOneBy(['programEligibility' => $programEligibility1])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        // configuration 2 - bool
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field2])->shouldBeCalledOnce()->willReturn($programEligibility2);
        $this->propertyAccessor->getValue($reservation, 'borrower')->shouldBeCalledTimes(2)->willReturn($reservation->getBorrower());
        $this->propertyAccessor->getValue($reservation->getBorrower(), 'creationInProgress')->shouldBeCalledOnce()->willReturn(false);
        $this->programEligibilityConfigurationRepository->findOneBy(['programEligibility' => $programEligibility2, 'value' => false])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration2);
        // configuration 3 - bypassed
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field3])->shouldNotBeCalled();
        // configuration 4 - list
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field4])->shouldBeCalledOnce()->willReturn($programEligibility4);
        $this->propertyAccessor->getValue($reservation->getBorrower(), 'legalForm')->shouldBeCalledOnce()->willReturn($legalFormOption);
        $this->programEligibilityConfigurationRepository->findOneBy(['programEligibility' => $programEligibility4, 'programChoiceOption' => $legalFormOption])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration4);

        $eligibilityChecker = $this->createTestObject();
        static::assertTrue($eligibilityChecker->checkByCategory($reservation, $category));
    }

    public function testCheckByCategoryWithoutFields(): void
    {
        $category    = 'test';
        $reservation = $this->createReservation();

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn([]);
        $this->programEligibilityRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->propertyAccessor->getValue(Argument::cetera())->shouldNotBeCalled();
        $this->programChoiceOptionRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->programEligibilityConfigurationRepository->findOneBy(Argument::any())->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        static::assertTrue($eligibilityChecker->checkByCategory($reservation, $category));
    }

    public function testCheckByCategoryWithoutProgramEligibility(): void
    {
        $category    = 'test';
        $reservation = $this->createReservation();
        $program     = $reservation->getProgram();
        $field1      = new Field('alias_1', $category, 'bool', 'borrower::creationInProgress', false, null, null);
        $fields      = [$field1];

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn(null);
        $this->propertyAccessor->getValue(Argument::cetera())->shouldNotBeCalled();
        $this->programChoiceOptionRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->programEligibilityConfigurationRepository->findOneBy(Argument::any())->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->checkByCategory($reservation, $category);
    }

    public function testCheckByCategoryWithoutOtherValue(): void
    {
        $category            = 'test';
        $reservation         = $this->createReservation();
        $program             = $reservation->getProgram();
        $field1              = new Field('alias_1', $category, 'other', 'borrower::taxNumber', false, null, null);
        $fields              = [$field1];
        $programEligibility1 = new ProgramEligibility($program, $field1);

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->propertyAccessor->getValue($reservation, 'borrower')->shouldBeCalledOnce()->willReturn($reservation->getBorrower());
        $this->propertyAccessor->getValue($reservation->getBorrower(), 'taxNumber')->shouldBeCalledOnce()->willReturn(null);
        $this->programChoiceOptionRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->programEligibilityConfigurationRepository->findOneBy(['programEligibility' => $programEligibility1])->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($reservation, $category));
    }

    public function testCheckByCategoryWithoutOtherEligibilityConfiguration(): void
    {
        $category            = 'test';
        $reservation         = $this->createReservation();
        $program             = $reservation->getProgram();
        $field1              = new Field('alias_1', $category, 'other', 'borrower::companyName', false, null, null);
        $fields              = [$field1];
        $programEligibility1 = new ProgramEligibility($program, $field1);

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->propertyAccessor->getValue($reservation, 'borrower')->shouldBeCalledOnce()->willReturn($reservation->getBorrower());
        $this->propertyAccessor->getValue($reservation->getBorrower(), 'companyName')->shouldBeCalledOnce()->willReturn('Borrower Company');
        $this->programChoiceOptionRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->programEligibilityConfigurationRepository->findOneBy(['programEligibility' => $programEligibility1])->shouldBeCalledOnce()->willReturn(null);

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->checkByCategory($reservation, $category);
    }

    public function testCheckByCategoryWithOtherIneligible(): void
    {
        $category                         = 'test';
        $reservation                      = $this->createReservation();
        $program                          = $reservation->getProgram();
        $field1                           = new Field('alias_1', $category, 'other', 'borrower::address', false, null, null);
        $fields                           = [$field1];
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, null, false);

        $address = new Address();
        $reservation->getBorrower()->setAddress($address);

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->propertyAccessor->getValue($reservation, 'borrower')->shouldBeCalledOnce()->willReturn($reservation->getBorrower());
        $this->propertyAccessor->getValue($reservation->getBorrower(), 'address')->shouldBeCalledOnce()->willReturn($address);
        $this->programChoiceOptionRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->programEligibilityConfigurationRepository->findOneBy(['programEligibility' => $programEligibility1])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($reservation, $category));
    }

    public function testCheckByCategoryWithoutBoolEligibilityConfiguration(): void
    {
        $category            = 'test';
        $reservation         = $this->createReservation();
        $program             = $reservation->getProgram();
        $field1              = new Field('alias_1', $category, 'bool', 'borrowerBusinessActivity::subsidiary', false, null, null);
        $fields              = [$field1];
        $programEligibility1 = new ProgramEligibility($program, $field1);

        $borrowerBusinessActivity = (new BorrowerBusinessActivity())->setSubsidiary(true);
        $reservation->setBorrowerBusinessActivity($borrowerBusinessActivity);

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->propertyAccessor->getValue($reservation, 'borrowerBusinessActivity')->shouldBeCalledOnce()->willReturn($borrowerBusinessActivity);
        $this->propertyAccessor->getValue($borrowerBusinessActivity, 'subsidiary')->shouldBeCalledOnce()->willReturn(true);
        $this->programChoiceOptionRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->programEligibilityConfigurationRepository->findOneBy(['programEligibility' => $programEligibility1, 'value' => 1])->shouldBeCalledOnce()->willReturn(null);

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->checkByCategory($reservation, $category);
    }

    public function testCheckByCategoryWithBoolIneligible(): void
    {
        $category                         = 'test';
        $reservation                      = $this->createReservation();
        $program                          = $reservation->getProgram();
        $field1                           = new Field('alias_1', $category, 'bool', 'borrowerBusinessActivity::receivingGrant', false, null, null);
        $fields                           = [$field1];
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, '1', false);

        $borrowerBusinessActivity = (new BorrowerBusinessActivity())->setGrant(new NullableMoney('EUR', '42'));
        $reservation->setBorrowerBusinessActivity($borrowerBusinessActivity);

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->propertyAccessor->getValue($reservation, 'borrowerBusinessActivity')->shouldBeCalledOnce()->willReturn($borrowerBusinessActivity);
        $this->propertyAccessor->getValue($borrowerBusinessActivity, 'receivingGrant')->shouldBeCalledOnce()->willReturn(true);
        $this->programChoiceOptionRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->programEligibilityConfigurationRepository->findOneBy(['programEligibility' => $programEligibility1, 'value' => 1])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($reservation, $category));
    }

    public function testCheckByCategoryWithoutListChoiceOption(): void
    {
        $category            = 'test';
        $reservation         = $this->createReservation();
        $program             = $reservation->getProgram();
        $field1              = new Field('alias_1', $category, 'list', 'borrowerBusinessActivity::address::country', false, null, null);
        $fields              = [$field1];
        $programEligibility1 = new ProgramEligibility($program, $field1);

        $borrowerBusinessActivity = (new BorrowerBusinessActivity())->setAddress((new Address())->setCountry('USA'));
        $reservation->setBorrowerBusinessActivity($borrowerBusinessActivity);

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->propertyAccessor->getValue($reservation, 'borrowerBusinessActivity')->shouldBeCalledOnce()->willReturn($borrowerBusinessActivity);
        $this->propertyAccessor->getValue($borrowerBusinessActivity, 'address.country')->shouldBeCalledOnce()->willReturn('USA');
        $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $field1,
            'description' => 'USA',
        ])->shouldBeCalledOnce()->willReturn(null);
        $this->programEligibilityConfigurationRepository->findOneBy(Argument::any())->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->checkByCategory($reservation, $category);
    }

    public function testCheckByCategoryWithoutListEligibilityConfiguration(): void
    {
        $category             = 'test';
        $reservation          = $this->createReservation();
        $program              = $reservation->getProgram();
        $field1               = new Field('alias_1', $category, 'list', 'borrowerBusinessActivity::address::country', false, null, null);
        $fields               = [$field1];
        $programChoiceOption1 = new ProgramChoiceOption($program, 'FR', $field1);
        $programEligibility1  = new ProgramEligibility($program, $field1);

        $reservation->setBorrowerBusinessActivity((new BorrowerBusinessActivity())->setAddress((new Address())->setCountry('FR')));

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->propertyAccessor->getValue($reservation, 'borrowerBusinessActivity')->shouldBeCalledOnce()->willReturn($reservation->getBorrowerBusinessActivity());
        $this->propertyAccessor->getValue($reservation->getBorrowerBusinessActivity(), 'address.country')->shouldBeCalledOnce()->willReturn('FR');
        $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $field1,
            'description' => 'FR',
        ])->shouldBeCalledOnce()->willReturn($programChoiceOption1);
        $this->programEligibilityConfigurationRepository->findOneBy(['programEligibility' => $programEligibility1, 'programChoiceOption' => $programChoiceOption1])->shouldBeCalledOnce()->willReturn(null);

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->checkByCategory($reservation, $category);
    }

    public function testCheckByCategoryWithListIneligible(): void
    {
        $category                         = 'test';
        $reservation                      = $this->createReservation();
        $program                          = $reservation->getProgram();
        $field1                           = new Field('alias_1', $category, 'list', 'project::investmentThematic', false, null, null);
        $field2                           = new Field('alias_2', $category, 'list', 'project::projectNafCode', false, null, null);
        $fields                           = [$field1];
        $programChoiceOption1             = new ProgramChoiceOption($program, 'investment thematic', $field1);
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, null, false);

        $reservation->setProject(new Project(
            new Money('eur', '42'),
            $programChoiceOption1,
            new ProgramChoiceOption($program, 'N42', $field2),
        ));

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->propertyAccessor->getValue($reservation, 'project')->shouldBeCalledOnce()->willReturn($reservation->getProject());
        $this->propertyAccessor->getValue($reservation->getProject(), 'investmentThematic')->shouldBeCalledOnce()->willReturn($programChoiceOption1);
        $this->programChoiceOptionRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->programEligibilityConfigurationRepository->findOneBy(['programEligibility' => $programEligibility1, 'programChoiceOption' => $programChoiceOption1])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->checkByCategory($reservation, $category));
    }

    private function createTestObject(): EligibilityChecker
    {
        return new EligibilityChecker(
            $this->fieldRepository->reveal(),
            $this->programChoiceOptionRepository->reveal(),
            $this->programEligibilityRepository->reveal(),
            $this->programEligibilityConfigurationRepository->reveal(),
            $this->propertyAccessor->reveal()
        );
    }

    private function createReservation(): Reservation
    {
        $teamRoot = Team::createRootTeam(new Company('Company', 'Company', ''));
        $team     = Team::createTeam('Team', $teamRoot);

        return new Reservation(
            new Program(
                'Program',
                new CompanyGroupTag(new CompanyGroup('Company Group'), 'code'),
                new Money('eur', '42'),
                new Staff(new User('user@mail.com'), $team)
            ),
            new Borrower('Borrower Company', 'D'),
            new Staff(new User('user@mail.com'), $team)
        );
    }
}
