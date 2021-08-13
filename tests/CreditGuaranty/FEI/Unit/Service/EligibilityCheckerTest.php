<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service;

use Doctrine\Common\Collections\Collection;
use KLS\CreditGuaranty\FEI\Entity\Borrower;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\FEI\Entity\Project;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConfigurationRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityRepository;
use KLS\CreditGuaranty\FEI\Service\EligibilityChecker;
use KLS\CreditGuaranty\FEI\Service\EligibilityConditionChecker;
use KLS\CreditGuaranty\FEI\Service\EligibilityHelper;
use LogicException;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\Service\EligibilityChecker
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

    public function testCheckCategoryWithoutConditions(): void
    {
        $this->reservation->setBorrower($this->createBorrower($this->reservation));

        $category       = 'profile';
        $withConditions = false;
        $program        = $this->reservation->getProgram();
        $entity         = $this->reservation->getBorrower();

        $field1                           = new Field('company_name', $category, 'other', 'borrower', 'companyName', Borrower::class, false, null, null);
        $field2                           = new Field('creation_in_progress', $category, 'bool', 'borrower', 'creationInProgress', Borrower::class, false, null, null);
        $field3                           = new Field('legal_form', $category, 'list', 'borrower', 'legalForm', Borrower::class, false, null, null);
        $fields                           = [$field1, $field2, $field3];
        $legalFormOption                  = new ProgramChoiceOption($program, 'legal form', $field3);
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibility2              = new ProgramEligibility($program, $field2);
        $programEligibility3              = new ProgramEligibility($program, $field3);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, null, true);
        $programEligibilityConfiguration2 = new ProgramEligibilityConfiguration($programEligibility2, null, '0', true);
        $programEligibilityConfiguration3 = new ProgramEligibilityConfiguration($programEligibility3, $legalFormOption, null, true);

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->fieldRepository->findAll()->shouldNotBeCalled();

        // configuration 1 - other
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($this->reservation, $field1)->shouldBeCalledOnce()->willReturn($entity);
        $this->eligibilityHelper->getValue($entity, $field1)->shouldBeCalledOnce()->willReturn('Borrower Company');
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration1)->shouldNotBeCalled();

        // configuration 2 - bool
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field2])->shouldBeCalledOnce()->willReturn($programEligibility2);
        $this->eligibilityHelper->getEntity($this->reservation, $field2)->shouldBeCalledOnce()->willReturn($entity);
        $this->eligibilityHelper->getValue($entity, $field2)->shouldBeCalledOnce()->willReturn(false);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility2,
            'value'              => 0,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration2);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration2)->shouldNotBeCalled();

        // configuration 3 - list
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field3])->shouldBeCalledOnce()->willReturn($programEligibility3);
        $this->eligibilityHelper->getEntity($this->reservation, $field3)->shouldBeCalledOnce()->willReturn($entity);
        $this->eligibilityHelper->getValue($entity, $field3)->shouldBeCalledOnce()->willReturn($legalFormOption);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility3,
            'programChoiceOption' => $legalFormOption,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration3);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration3)->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        $result             = $eligibilityChecker->check($this->reservation, $withConditions, $category);

        static::assertSame([], $result);
    }

    public function testCheckCategoryWithConditions(): void
    {
        $this->reservation->setBorrower($this->createBorrower($this->reservation));
        $this->reservation->setProject($this->createProject($this->reservation));
        $this->reservation->addFinancingObject($this->createFinancingObject($this->reservation, true));

        $category       = 'loan';
        $withConditions = true;
        $program        = $this->reservation->getProgram();
        $entity         = $this->reservation->getFinancingObjects();

        $field1                           = new Field('loan_duration', $category, 'other', 'financingObjects', 'loanDuration', FinancingObject::class, false, null, null);
        $field2                           = new Field('supporting_generations_renewal', $category, 'bool', 'financingObjects', 'supportingGenerationsRenewal', FinancingObject::class, false, null, null);
        $fields                           = [$field1, $field2];
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibility2              = new ProgramEligibility($program, $field2);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, null, true);
        $programEligibilityConfiguration2 = new ProgramEligibilityConfiguration($programEligibility2, null, '1', true);

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->fieldRepository->findAll()->shouldNotBeCalled();

        // configuration 1 - other
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($this->reservation, $field1)->shouldBeCalledOnce()->willReturn($entity);
        $this->eligibilityHelper->getValue($entity->first(), $field1)->shouldBeCalledOnce()->willReturn(4);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration1)->shouldBeCalledOnce()->willReturn(false);

        // configuration 2 - bool
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field2])->shouldBeCalledOnce()->willReturn($programEligibility2);
        $this->eligibilityHelper->getEntity($this->reservation, $field2)->shouldBeCalledOnce()->willReturn($entity);
        $this->eligibilityHelper->getValue($entity->first(), $field2)->shouldBeCalledOnce()->willReturn(true);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility2,
            'value'              => 1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration2);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration2)->shouldBeCalledOnce()->willReturn(true);

        $eligibilityChecker = $this->createTestObject();
        $result             = $eligibilityChecker->check($this->reservation, $withConditions, $category);

        static::assertSame(['loan' => ['loan_duration']], $result);
    }

    public function testCheckWithoutConditions(): void
    {
        $this->reservation->setBorrower($this->createBorrower($this->reservation));
        $this->reservation->setProject($this->createProject($this->reservation));
        $this->reservation->addFinancingObject($this->createFinancingObject($this->reservation, false));

        $category       = null;
        $withConditions = false;
        $program        = $this->reservation->getProgram();

        $field1                           = new Field('activity_post_code', 'profile', 'other', 'borrower', 'addressPostCode', Borrower::class, false, null, null);
        $field2                           = new Field('receiving_grant', 'project', 'bool', 'project', 'receivingGrant', Project::class, false, null, null);
        $field3                           = new Field('financing_object_type', 'loan', 'list', 'financingObjects', 'financingObjectType', FinancingObject::class, false, null, null);
        $fields                           = [$field1, $field2, $field3];
        $financingObjectTypeOption        = new ProgramChoiceOption($program, 'Object type', $field3);
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibility2              = new ProgramEligibility($program, $field2);
        $programEligibility3              = new ProgramEligibility($program, $field3);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, null, true);
        $programEligibilityConfiguration2 = new ProgramEligibilityConfiguration($programEligibility2, null, '0', false);
        $programEligibilityConfiguration3 = new ProgramEligibilityConfiguration($programEligibility3, $financingObjectTypeOption, null, true);

        $this->fieldRepository->findBy(['category' => Argument::any()])->shouldNotBeCalled();
        $this->fieldRepository->findAll()->shouldBeCalledOnce()->willReturn($fields);

        // configuration 1 - other
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($this->reservation, $field1)->shouldBeCalledOnce()->willReturn($this->reservation->getBorrower());
        $this->eligibilityHelper->getValue($this->reservation->getBorrower(), $field1)->shouldBeCalledOnce()->willReturn('75042');
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration1)->shouldNotBeCalled();

        // configuration 2 - bool
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field2])->shouldBeCalledOnce()->willReturn($programEligibility2);
        $this->eligibilityHelper->getEntity($this->reservation, $field2)->shouldBeCalledOnce()->willReturn($this->reservation->getProject());
        $this->eligibilityHelper->getValue($this->reservation->getProject(), $field2)->shouldBeCalledOnce()->willReturn(false);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility2,
            'value'              => 0,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration2);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration2)->shouldNotBeCalled();

        // configuration 3 - list
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field3])->shouldBeCalledOnce()->willReturn($programEligibility3);
        $this->eligibilityHelper->getEntity($this->reservation, $field3)->shouldBeCalledOnce()->willReturn($this->reservation->getFinancingObjects());
        $this->eligibilityHelper->getValue($this->reservation->getFinancingObjects()->first(), $field3)->shouldBeCalledOnce()->willReturn($financingObjectTypeOption);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility3,
            'programChoiceOption' => $financingObjectTypeOption,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration3);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration3)->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        $result             = $eligibilityChecker->check($this->reservation, $withConditions, $category);

        static::assertSame(['project' => ['receiving_grant']], $result);
    }

    public function testCheckWithConditions(): void
    {
        $this->reservation->setBorrower($this->createBorrower($this->reservation));
        $this->reservation->setProject($this->createProject($this->reservation));
        $this->reservation->addFinancingObject($this->createFinancingObject($this->reservation, true));

        $category       = null;
        $withConditions = true;
        $program        = $this->reservation->getProgram();

        $field1                           = new Field('loan_deferral', 'loan', 'other', 'financingObjects', 'loanDeferral', FinancingObject::class, false, null, null);
        $field2                           = new Field('borrower_type', 'profile', 'list', 'borrower', 'borrowerType', Borrower::class, false, null, null);
        $field3                           = new Field('receiving_grant', 'project', 'bool', 'project', 'receivingGrant', Project::class, false, null, null);
        $fields                           = [$field1, $field2, $field3];
        $borrowerTypeOption               = new ProgramChoiceOption($program, 'borrower type', $field2);
        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibility2              = new ProgramEligibility($program, $field2);
        $programEligibility3              = new ProgramEligibility($program, $field3);
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, null, true);
        $programEligibilityConfiguration2 = new ProgramEligibilityConfiguration($programEligibility2, $borrowerTypeOption, null, true);
        $programEligibilityConfiguration3 = new ProgramEligibilityConfiguration($programEligibility3, null, '0', false);

        $this->fieldRepository->findBy(['category' => Argument::any()])->shouldNotBeCalled();
        $this->fieldRepository->findAll()->shouldBeCalledOnce()->willReturn($fields);

        // configuration 1 - other
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn($programEligibility1);
        $this->eligibilityHelper->getEntity($this->reservation, $field1)->shouldBeCalledOnce()->willReturn($this->reservation->getFinancingObjects());
        $this->eligibilityHelper->getValue($this->reservation->getFinancingObjects()->first(), $field1)->shouldBeCalledOnce()->willReturn(1);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration1);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration1)->shouldBeCalledOnce()->willReturn(true);

        // configuration 2 - list
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field2])->shouldBeCalledOnce()->willReturn($programEligibility2);
        $this->eligibilityHelper->getEntity($this->reservation, $field2)->shouldBeCalledOnce()->willReturn($this->reservation->getBorrower());
        $this->eligibilityHelper->getValue($this->reservation->getBorrower(), $field2)->shouldBeCalledOnce()->willReturn($borrowerTypeOption);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility2,
            'programChoiceOption' => $borrowerTypeOption,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration2);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration2)->shouldBeCalledOnce()->willReturn(false);

        // configuration 3 - bool
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field3])->shouldBeCalledOnce()->willReturn($programEligibility3);
        $this->eligibilityHelper->getEntity($this->reservation, $field3)->shouldBeCalledOnce()->willReturn($this->reservation->getProject());
        $this->eligibilityHelper->getValue($this->reservation->getProject(), $field3)->shouldBeCalledOnce()->willReturn(false);
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility3,
            'value'              => 0,
        ])->shouldBeCalledOnce()->willReturn($programEligibilityConfiguration3);
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration3)->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        $result             = $eligibilityChecker->check($this->reservation, $withConditions, $category);

        static::assertSame(['profile' => ['borrower_type'], 'project' => ['receiving_grant']], $result);
    }

    public function exceptionsProvider(): iterable
    {
        yield 'checking profile' => ['profile', false];
        yield 'checking profile with conditions' => ['profile', true];
        yield 'checking project' => ['project', false];
        yield 'checking project with conditions' => ['project', true];
        yield 'checking loan' => ['loan', false];
        yield 'checking loan with conditions' => ['loan', true];
    }

    /**
     * @dataProvider exceptionsProvider
     */
    public function testCheckNotSupportsCheckingException(string $category, bool $withConditions): void
    {
        $this->fieldRepository->findBy(['category' => $category])->shouldNotBeCalled();
        $this->fieldRepository->findAll()->shouldNotBeCalled();
        $this->programEligibilityRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->eligibilityHelper->getEntity(Argument::cetera())->shouldNotBeCalled();
        $this->eligibilityHelper->getValue(Argument::cetera())->shouldNotBeCalled();
        $this->programEligibilityConfigurationRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->eligibilityConditionChecker->checkByConfiguration(Argument::cetera())->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->check($this->reservation, $withConditions, $category);
    }

    public function testCheckExceptionWithoutProgramEligibility(): void
    {
        $this->reservation->setBorrower($this->createBorrower($this->reservation));

        $category       = 'profile';
        $withConditions = false;
        $program        = $this->reservation->getProgram();

        $field1 = new Field('beneficiary_name', $category, 'other', 'borrower', 'beneficiaryName', Borrower::class, false, null, null);
        $fields = [$field1];

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn($fields);
        $this->fieldRepository->findAll()->shouldNotBeCalled();
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field1])->shouldBeCalledOnce()->willReturn(null);
        $this->eligibilityHelper->getEntity(Argument::cetera())->shouldNotBeCalled();
        $this->eligibilityHelper->getValue(Argument::cetera())->shouldNotBeCalled();
        $this->programEligibilityConfigurationRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->eligibilityConditionChecker->checkByConfiguration(Argument::cetera())->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->check($this->reservation, $withConditions, $category);
    }

    public function configurationExceptionsProvider(): iterable
    {
        yield 'profile - other type' => [
            new Field('beneficiary_name', 'profile', 'other', 'borrower', 'beneficiaryName', Borrower::class, false, null, null),
            'Borrower Name',
        ];
        yield 'project - bool type' => [
            new Field('receiving_grant', 'project', 'other', 'project', 'receivingGrant', Project::class, false, null, null),
            false,
        ];
        yield 'loan - list type' => [
            new Field('financing_object_type', 'loan', 'list', 'project', 'financingObjectType', FinancingObject::class, false, null, null),
            null,
        ];
    }

    /**
     * @dataProvider configurationExceptionsProvider
     *
     * @param mixed $value
     */
    public function testCheckExceptionWithoutProgramEligibilityConfiguration(Field $field, $value): void
    {
        $category             = $field->getCategory();
        $withConditions       = false;
        $program              = $this->reservation->getProgram();
        $entity               = $this->getEntity($field);
        $entityItem           = ($entity instanceof Collection) ? $entity->first() : $entity;
        $programEligibility   = new ProgramEligibility($program, $field);
        $configurationFilters = ['programEligibility' => $programEligibility];

        if (Field::TYPE_BOOL === $field->getType()) {
            $configurationFilters['value'] = (int) $value;
        }

        if (Field::TYPE_LIST === $field->getType()) {
            $value = new ProgramChoiceOption($this->reservation->getProgram(), 'test', $field);

            $configurationFilters['programChoiceOption'] = $value;
        }

        $this->fieldRepository->findBy(['category' => $category])->shouldBeCalledOnce()->willReturn([$field]);
        $this->fieldRepository->findAll()->shouldNotBeCalled();
        $this->programEligibilityRepository->findOneBy(['program' => $program, 'field' => $field])->shouldBeCalledOnce()->willReturn($programEligibility);
        $this->eligibilityHelper->getEntity($this->reservation, $field)->shouldBeCalledOnce()->willReturn($entity);
        $this->eligibilityHelper->getValue($entityItem, $field)->shouldBeCalledOnce()->willReturn($value);
        $this->programEligibilityConfigurationRepository->findOneBy($configurationFilters)->shouldBeCalledOnce()->willReturn(null);
        $this->eligibilityConditionChecker->checkByConfiguration(Argument::cetera())->shouldNotBeCalled();

        static::expectException(LogicException::class);

        $eligibilityChecker = $this->createTestObject();
        $eligibilityChecker->check($this->reservation, $withConditions, $category);
    }

    private function getEntity(Field $field)
    {
        $entity = null;

        switch ($field->getCategory()) {
            case 'profile':
                $this->reservation->setBorrower($this->createBorrower($this->reservation));
                $entity = $this->reservation->getBorrower();

                break;

            case 'project':
                $this->reservation->setProject($this->createProject($this->reservation));
                $entity = $this->reservation->getProject();

                break;

            case 'loan':
                $this->reservation->addFinancingObject($this->createFinancingObject($this->reservation, true));
                $entity = $this->reservation->getFinancingObjects();

                break;
        }

        return $entity;
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
