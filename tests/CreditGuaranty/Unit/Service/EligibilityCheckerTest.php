<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\Unit\Service;

use LogicException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\CompanyGroup;
use Unilend\Core\Entity\CompanyGroupTag;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\NafNace;
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

    /** @var ProgramEligibilityRepository|ObjectProphecy */
    private $programEligibilityRepository;

    /** @var ProgramEligibilityConfigurationRepository|ObjectProphecy */
    private $programEligibilityConfigurationRepository;

    protected function setUp(): void
    {
        $this->fieldRepository                           = $this->prophesize(FieldRepository::class);
        $this->programEligibilityRepository              = $this->prophesize(ProgramEligibilityRepository::class);
        $this->programEligibilityConfigurationRepository = $this->prophesize(ProgramEligibilityConfigurationRepository::class);
    }

    protected function tearDown(): void
    {
        $this->fieldRepository                           = null;
        $this->programEligibilityRepository              = null;
        $this->programEligibilityConfigurationRepository = null;
    }

    public function testCheckEligible(): void
    {
        $category                         = 'test';
        $reservation                      = $this->createReservation();
        $program                          = $reservation->getProgram();
        $field1                           = new Field('alias_1', $category, 'other', 'Unilend\\CreditGuaranty\\Entity\\Project::fundingMoney', false, null, null);
        $field2                           = new Field('alias_2', $category, 'bool', 'Unilend\\CreditGuaranty\\Entity\\BorrowerBusinessActivity::subsidiary', false, null, null);
        $field3                           = new Field('alias_3', $category, 'list', 'Unilend\\CreditGuaranty\\Entity\\Borrower::legalForm', false, null, null);
        $fields                           = [$field1, $field2, $field3];
        $legalFormOption                  = new ProgramChoiceOption($program, 'legal form', $field3);
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibility2              = new ProgramEligibility($program, $field2);
        $programEligibility3              = new ProgramEligibility($program, $field3);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, null, true);
        $programEligibilityConfiguration2 = new ProgramEligibilityConfiguration($programEligibility2, null, '0', true);
        $programEligibilityConfiguration3 = new ProgramEligibilityConfiguration($programEligibility3, $legalFormOption, null, true);

        $reservation->getBorrower()->setLegalForm($legalFormOption);
        $reservation->setBorrowerBusinessActivity((new BorrowerBusinessActivity())->setSubsidiary(false));
        $reservation->setProject(new Project(
            new Money('eur', '42'),
            new ProgramChoiceOption($program, 'investment thematic', $field1),
            new NafNace('N42', 'N.42', 'Naf', 'Nace')
        ));

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);

        // configuration 1 - other
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);

        // configuration 2 - bool
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field2])->shouldBeCalledOnce()->willReturn($programEligibility2);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility2,
            'value'              => 0,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration2);

        // configuration 2 - list
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field3])->shouldBeCalledOnce()->willReturn($programEligibility3);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility3,
            'programChoiceOption' => $legalFormOption,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration3);

        $eligibilityChecker = $this->createTestObject();
        static::assertTrue($eligibilityChecker->check($reservation, $category));
    }

    public function testCheckWithEmptyFields(): void
    {
        $category    = 'test';
        $reservation = $this->createReservation();

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn([]);
        $this->programEligibilityRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->programEligibilityConfigurationRepository->findOneBy(Argument::any())->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        static::assertTrue($eligibilityChecker->check($reservation, $category));
    }

    public function testCheckWithoutProgramEligibility(): void
    {
        $category    = 'test';
        $reservation = $this->createReservation();
        $program     = $reservation->getProgram();
        $field1      = new Field('alias_1', $category, 'bool', '', false, null, null);
        $field2      = new Field('alias_2', $category, 'other', 'Unilend\\CreditGuaranty\\Entity\\Borrower::companyName', false, null, null);
        $fields      = [$field1, $field2];

        $program->setPublicId();
        $field2->setPublicId();

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldNotBeCalled();
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field2])->shouldBeCalledOnce()->willReturn(null);
        $this->programEligibilityConfigurationRepository->findOneBy(Argument::any())->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->check($reservation, $category);
    }

    public function testCheckOtherTypeWithoutValue(): void
    {
        $category           = 'test';
        $reservation        = $this->createReservation();
        $program            = $reservation->getProgram();
        $field1             = new Field('alias_1', $category, 'other', 'Unilend\\CreditGuaranty\\Entity\\Borrower::beneficiaryName', false, null, null);
        $fields             = [$field1];
        $programEligibility = new ProgramEligibility($program, $field1);

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility);
        $this->programEligibilityConfigurationRepository->findOneBy(['programEligibility' => $programEligibility])->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->check($reservation, $category));
    }

    public function testCheckOtherTypeWithoutProgramEligibilityConfiguration(): void
    {
        $category           = 'test';
        $reservation        = $this->createReservation();
        $program            = $reservation->getProgram();
        $field1             = new Field('alias_1', $category, 'other', 'Unilend\\CreditGuaranty\\Entity\\Borrower::companyName', false, null, null);
        $fields             = [$field1];
        $programEligibility = new ProgramEligibility($program, $field1);

        $programEligibility->setPublicId();

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility);
        $this->programEligibilityConfigurationRepository->findOneBy(['programEligibility' => $programEligibility])->shouldBeCalledOnce()->willReturn(null);

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->check($reservation, $category);
    }

    public function testCheckOtherTypeIneligible(): void
    {
        $category                         = 'test';
        $reservation                      = $this->createReservation();
        $program                          = $reservation->getProgram();
        $field1                           = new Field('alias_1', $category, 'other', 'Unilend\\CreditGuaranty\\Entity\\Borrower::beneficiaryName', false, null, null);
        $field2                           = new Field('alias_2', $category, 'other', 'Unilend\\CreditGuaranty\\Entity\\Borrower::companyName', false, null, null);
        $fields                           = [$field1, $field2];
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibility2              = new ProgramEligibility($program, $field2);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, null, true);
        $programEligibilityConfiguration2 = new ProgramEligibilityConfiguration($programEligibility2, null, null, false);

        $reservation->getBorrower()->setBeneficiaryName('Borrower Name');

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);

        // configuration 1 - eligible
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);

        // configuration 2 - ineligible
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field2])->shouldBeCalledOnce()->willReturn($programEligibility2);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility2,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration2);

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->check($reservation, $category));
    }

    public function testCheckBooleanTypeWithoutProgramEligibilityConfiguration(): void
    {
        $category           = 'test';
        $reservation        = $this->createReservation();
        $program            = $reservation->getProgram();
        $field1             = new Field('alias_1', $category, 'bool', 'Unilend\\CreditGuaranty\\Entity\\Borrower::creationInProgress', false, null, null);
        $fields             = [$field1];
        $programEligibility = new ProgramEligibility($program, $field1);

        $reservation->getBorrower()->setCreationInProgress(true);
        $programEligibility->setPublicId();

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility);
        $this->programEligibilityConfigurationRepository->findOneBy(['programEligibility' => $programEligibility, 'value' => 1])->shouldBeCalledOnce()->willReturn(null);

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->check($reservation, $category);
    }

    public function testCheckBooleanTypeIneligible(): void
    {
        $category                        = 'test';
        $reservation                     = $this->createReservation();
        $program                         = $reservation->getProgram();
        $field1                          = new Field('alias_1', $category, 'bool', 'Unilend\\CreditGuaranty\\Entity\\Borrower::creationInProgress', false, null, null);
        $fields                          = [$field1];
        $programEligibility              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, null, null, false);

        $reservation->getBorrower()->setCreationInProgress(true);

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility,
            'value'              => 1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration);

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->check($reservation, $category));
    }

    public function testCheckListTypeWithoutValue(): void
    {
        $category           = 'test';
        $reservation        = $this->createReservation();
        $program            = $reservation->getProgram();
        $field1             = new Field('alias_1', $category, 'list', 'Unilend\\CreditGuaranty\\Entity\\Borrower::borrowerType', false, null, null);
        $fields             = [$field1];
        $programEligibility = new ProgramEligibility($program, $field1);

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility);
        $this->programEligibilityConfigurationRepository->findOneBy(Argument::any())->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->check($reservation, $category));
    }

    public function testCheckListTypeIneligible(): void
    {
        $category                        = 'test';
        $reservation                     = $this->createReservation();
        $program                         = $reservation->getProgram();
        $field1                          = new Field('alias_1', $category, 'list', 'Unilend\\CreditGuaranty\\Entity\\Borrower::borrowerType', false, null, null);
        $fields                          = [$field1];
        $borrowerTypeOption              = new ProgramChoiceOption($program, 'borrower type', $field1);
        $programEligibility              = new ProgramEligibility($program, $field1);
        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, $borrowerTypeOption, null, false);

        $reservation->getBorrower()->setBorrowerType($borrowerTypeOption);

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility,
            'programChoiceOption' => $borrowerTypeOption,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration);

        $eligibilityChecker = $this->createTestObject();
        static::assertFalse($eligibilityChecker->check($reservation, $category));
    }

    private function createTestObject(): EligibilityChecker
    {
        return new EligibilityChecker(
            $this->fieldRepository->reveal(),
            $this->programEligibilityRepository->reveal(),
            $this->programEligibilityConfigurationRepository->reveal()
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
