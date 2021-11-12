<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service;

use Doctrine\Common\Collections\Collection;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConfigurationRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityRepository;
use KLS\CreditGuaranty\FEI\Service\EligibilityChecker;
use KLS\CreditGuaranty\FEI\Service\EligibilityConditionChecker;
use KLS\CreditGuaranty\FEI\Service\ReservationAccessor;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ReservationSetTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Service\EligibilityChecker
 *
 * @internal
 */
class EligibilityCheckerTest extends TestCase
{
    use ProphecyTrait;
    use ReservationSetTrait;

    /** @var ProgramEligibilityRepository|ObjectProphecy */
    private $programEligibilityRepository;

    /** @var ProgramEligibilityConfigurationRepository|ObjectProphecy */
    private $programEligibilityConfigurationRepository;

    /** @var ReservationAccessor|ObjectProphecy */
    private $reservationAccessor;

    /** @var EligibilityConditionChecker|ObjectProphecy */
    private $eligibilityConditionChecker;

    /** @var Reservation */
    private $reservation;

    protected function setUp(): void
    {
        $this->programEligibilityRepository = $this->prophesize(
            ProgramEligibilityRepository::class
        );
        $this->programEligibilityConfigurationRepository = $this->prophesize(
            ProgramEligibilityConfigurationRepository::class
        );
        $this->reservationAccessor = $this->prophesize(
            ReservationAccessor::class
        );
        $this->eligibilityConditionChecker = $this->prophesize(
            EligibilityConditionChecker::class
        );
        $this->reservation = $this->createReservation();
    }

    protected function tearDown(): void
    {
        $this->programEligibilityRepository              = null;
        $this->programEligibilityConfigurationRepository = null;
        $this->reservationAccessor                       = null;
        $this->eligibilityConditionChecker               = null;
        $this->reservation                               = null;
    }

    /**
     * @covers ::check
     */
    public function testCheckCategoryWithoutConditions(): void
    {
        $this->withBorrower($this->reservation);

        $category       = 'profile';
        $withConditions = false;
        $program        = $this->reservation->getProgram();
        $entity         = $this->reservation->getBorrower();

        $field1 = $this->createCompanyNameField();
        $field2 = $this->createCreationInProgressField();
        $field3 = $this->createLegalFormField();

        $legalFormOption      = new ProgramChoiceOption($program, 'legal form', $field3);
        $programEligibility1  = new ProgramEligibility($program, $field1);
        $programEligibility2  = new ProgramEligibility($program, $field2);
        $programEligibility3  = new ProgramEligibility($program, $field3);
        $programEligibilities = [$programEligibility1, $programEligibility2, $programEligibility3];

        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration(
            $programEligibility1,
            null,
            null,
            true
        );
        $programEligibilityConfiguration2 = new ProgramEligibilityConfiguration(
            $programEligibility2,
            null,
            '0',
            true
        );
        $programEligibilityConfiguration3 = new ProgramEligibilityConfiguration(
            $programEligibility3,
            $legalFormOption,
            null,
            true
        );

        $this->programEligibilityRepository->findByProgramAndFieldCategory($program, $category)
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilities)
        ;

        // configuration 1 - other
        $this->reservationAccessor->getEntity($this->reservation, $field1)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $field1)
            ->shouldBeCalledOnce()
            ->willReturn('Borrower Company')
        ;
        $this->programEligibilityConfigurationRepository->findOneBy(['programEligibility' => $programEligibility1])
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilityConfiguration1)
        ;
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration1)
            ->shouldNotBeCalled()
        ;

        // configuration 2 - bool
        $this->reservationAccessor->getEntity($this->reservation, $field2)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $field2)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility2,
            'value'              => 0,
        ])
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilityConfiguration2)
        ;
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration2)
            ->shouldNotBeCalled()
        ;

        // configuration 3 - list
        $this->reservationAccessor->getEntity($this->reservation, $field3)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity, $field3)
            ->shouldBeCalledOnce()
            ->willReturn($legalFormOption)
        ;
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility3,
            'programChoiceOption' => $legalFormOption,
        ])
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilityConfiguration3)
        ;
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration3)
            ->shouldNotBeCalled()
        ;

        $eligibilityChecker = $this->createTestObject();
        $result             = $eligibilityChecker->check($this->reservation, $withConditions, $category);

        static::assertSame([], $result);
    }

    /**
     * @covers ::check
     */
    public function testCheckCategoryWithConditions(): void
    {
        $this->withBorrower($this->reservation);
        $this->withProject($this->reservation);
        $this->reservation->addFinancingObject($this->createFinancingObject($this->reservation, true));

        $category       = 'loan';
        $withConditions = true;
        $program        = $this->reservation->getProgram();
        $entity         = $this->reservation->getFinancingObjects();

        $field1 = $this->createLoanDurationField();
        $field2 = $this->createSupportingGenerationsRenewalField();

        $programEligibility1              = new ProgramEligibility($program, $field1);
        $programEligibility2              = new ProgramEligibility($program, $field2);
        $programEligibilities             = [$programEligibility1, $programEligibility2];
        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration($programEligibility1, null, null, true);
        $programEligibilityConfiguration2 = new ProgramEligibilityConfiguration($programEligibility2, null, '1', true);

        $this->programEligibilityRepository->findByProgramAndFieldCategory($program, $category)
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilities)
        ;

        // configuration 1 - other
        $this->reservationAccessor->getEntity($this->reservation, $field1)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity->first(), $field1)
            ->shouldBeCalledOnce()
            ->willReturn(4)
        ;
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilityConfiguration1)
        ;
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration1)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        // configuration 2 - bool
        $this->reservationAccessor->getEntity($this->reservation, $field2)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entity->first(), $field2)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility2,
            'value'              => 1,
        ])
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilityConfiguration2)
        ;
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration2)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $eligibilityChecker = $this->createTestObject();
        $result             = $eligibilityChecker->check($this->reservation, $withConditions, $category);

        static::assertSame(['loan' => ['loan_duration']], $result);
    }

    /**
     * @covers ::check
     */
    public function testCheckWithoutConditions(): void
    {
        $this->withBorrower($this->reservation);
        $this->withProject($this->reservation);
        $this->reservation->addFinancingObject($this->createFinancingObject($this->reservation, false));

        $category       = null;
        $withConditions = false;
        $program        = $this->reservation->getProgram();

        $field1 = $this->createActivityPostCodeField();
        $field2 = $this->createReceivingGrantField();
        $field3 = $this->createFinancingObjectTypeField();

        $programEligibility1       = new ProgramEligibility($program, $field1);
        $programEligibility2       = new ProgramEligibility($program, $field2);
        $programEligibility3       = new ProgramEligibility($program, $field3);
        $programEligibilities      = [$programEligibility1, $programEligibility2, $programEligibility3];
        $financingObjectTypeOption = new ProgramChoiceOption($program, 'Object type', $field3);

        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration(
            $programEligibility1,
            null,
            null,
            true
        );
        $programEligibilityConfiguration2 = new ProgramEligibilityConfiguration(
            $programEligibility2,
            null,
            '0',
            false
        );
        $programEligibilityConfiguration3 = new ProgramEligibilityConfiguration(
            $programEligibility3,
            $financingObjectTypeOption,
            null,
            true
        );

        $this->programEligibilityRepository->findByProgramAndFieldCategory($program, $category)
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilities)
        ;

        // configuration 1 - other
        $this->reservationAccessor->getEntity($this->reservation, $field1)
            ->shouldBeCalledOnce()
            ->willReturn($this->reservation->getBorrower())
        ;
        $this->reservationAccessor->getValue($this->reservation->getBorrower(), $field1)
            ->shouldBeCalledOnce()
            ->willReturn('75042')
        ;
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilityConfiguration1)
        ;
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration1)
            ->shouldNotBeCalled()
        ;

        // configuration 2 - bool
        $this->reservationAccessor->getEntity($this->reservation, $field2)
            ->shouldBeCalledOnce()
            ->willReturn($this->reservation->getProject())
        ;
        $this->reservationAccessor->getValue($this->reservation->getProject(), $field2)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility2,
            'value'              => 0,
        ])
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilityConfiguration2)
        ;
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration2)
            ->shouldNotBeCalled()
        ;

        // configuration 3 - list
        $this->reservationAccessor->getEntity($this->reservation, $field3)
            ->shouldBeCalledOnce()
            ->willReturn($this->reservation->getFinancingObjects())
        ;
        $this->reservationAccessor->getValue($this->reservation->getFinancingObjects()->first(), $field3)
            ->shouldBeCalledOnce()
            ->willReturn($financingObjectTypeOption)
        ;
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility3,
            'programChoiceOption' => $financingObjectTypeOption,
        ])
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilityConfiguration3)
        ;
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration3)
            ->shouldNotBeCalled()
        ;

        $eligibilityChecker = $this->createTestObject();
        $result             = $eligibilityChecker->check($this->reservation, $withConditions, $category);

        static::assertSame(['project' => ['receiving_grant']], $result);
    }

    /**
     * @covers ::check
     */
    public function testCheckWithConditions(): void
    {
        $this->withBorrower($this->reservation);
        $this->withProject($this->reservation);
        $this->reservation->addFinancingObject($this->createFinancingObject($this->reservation, true));

        $category       = null;
        $withConditions = true;
        $program        = $this->reservation->getProgram();

        $field1 = $this->createLoanDeferralField();
        $field2 = $this->createBorrowerTypeField();
        $field3 = $this->createReceivingGrantField();

        $programEligibility1  = new ProgramEligibility($program, $field1);
        $programEligibility2  = new ProgramEligibility($program, $field2);
        $programEligibility3  = new ProgramEligibility($program, $field3);
        $programEligibilities = [$programEligibility1, $programEligibility2, $programEligibility3];
        $borrowerTypeOption   = new ProgramChoiceOption($program, 'borrower type', $field2);

        $programEligibilityConfiguration1 = new ProgramEligibilityConfiguration(
            $programEligibility1,
            null,
            null,
            true
        );
        $programEligibilityConfiguration2 = new ProgramEligibilityConfiguration(
            $programEligibility2,
            $borrowerTypeOption,
            null,
            true
        );
        $programEligibilityConfiguration3 = new ProgramEligibilityConfiguration(
            $programEligibility3,
            null,
            '0',
            false
        );

        $this->programEligibilityRepository->findByProgramAndFieldCategory($program, $category)
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilities)
        ;

        // configuration 1 - other
        $this->reservationAccessor->getEntity($this->reservation, $field1)
            ->shouldBeCalledOnce()
            ->willReturn($this->reservation->getFinancingObjects())
        ;
        $this->reservationAccessor->getValue($this->reservation->getFinancingObjects()->first(), $field1)
            ->shouldBeCalledOnce()
            ->willReturn(1)
        ;
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility1,
        ])
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilityConfiguration1)
        ;
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration1)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        // configuration 2 - list
        $this->reservationAccessor->getEntity($this->reservation, $field2)
            ->shouldBeCalledOnce()
            ->willReturn($this->reservation->getBorrower())
        ;
        $this->reservationAccessor->getValue($this->reservation->getBorrower(), $field2)
            ->shouldBeCalledOnce()
            ->willReturn($borrowerTypeOption)
        ;
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $programEligibility2,
            'programChoiceOption' => $borrowerTypeOption,
        ])
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilityConfiguration2)
        ;
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration2)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        // configuration 3 - bool
        $this->reservationAccessor->getEntity($this->reservation, $field3)
            ->shouldBeCalledOnce()
            ->willReturn($this->reservation->getProject())
        ;
        $this->reservationAccessor->getValue($this->reservation->getProject(), $field3)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility3,
            'value'              => 0,
        ])
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilityConfiguration3)
        ;
        $this->eligibilityConditionChecker->checkByConfiguration($this->reservation, $programEligibilityConfiguration3)
            ->shouldNotBeCalled()
        ;

        $eligibilityChecker = $this->createTestObject();
        $result             = $eligibilityChecker->check($this->reservation, $withConditions, $category);

        static::assertSame(['profile' => ['borrower_type'], 'project' => ['receiving_grant']], $result);
    }

    /**
     * @covers ::check
     *
     * @dataProvider configurationExceptionsProvider
     *
     * @param mixed $value
     */
    public function testCheckWithoutProgramEligibilityConfiguration(Field $field, $value): void
    {
        $category             = $field->getCategory();
        $withConditions       = false;
        $program              = $this->reservation->getProgram();
        $entity               = $this->getEntity($field);
        $entityItem           = ($entity instanceof Collection) ? $entity->first() : $entity;
        $programEligibility   = new ProgramEligibility($program, $field);
        $programEligibilities = [$programEligibility];
        $configurationFilters = ['programEligibility' => $programEligibility];

        if (Field::TYPE_BOOL === $field->getType()) {
            $configurationFilters['value'] = (int) $value;
        }

        if (Field::TYPE_LIST === $field->getType()) {
            $value = new ProgramChoiceOption($this->reservation->getProgram(), 'test', $field);

            $configurationFilters['programChoiceOption'] = $value;
        }

        $this->programEligibilityRepository->findByProgramAndFieldCategory($program, $category)
            ->shouldBeCalledOnce()
            ->willReturn($programEligibilities)
        ;

        $this->reservationAccessor->getEntity($this->reservation, $field)
            ->shouldBeCalledOnce()
            ->willReturn($entity)
        ;
        $this->reservationAccessor->getValue($entityItem, $field)
            ->shouldBeCalledOnce()
            ->willReturn($value)
        ;
        $this->programEligibilityConfigurationRepository->findOneBy($configurationFilters)
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;
        $this->eligibilityConditionChecker->checkByConfiguration(Argument::cetera())
            ->shouldNotBeCalled()
        ;

        $eligibilityChecker = $this->createTestObject();
        $result             = $eligibilityChecker->check($this->reservation, $withConditions, $category);

        static::assertSame(
            [$field->getCategory() => [$field->getFieldAlias()]],
            $result
        );
    }

    public function configurationExceptionsProvider(): iterable
    {
        yield 'profile - other type' => [
            $this->createBeneficiaryNameField(),
            'Borrower Name',
        ];
        yield 'project - bool type' => [
            $this->createReceivingGrantField(),
            false,
        ];
        yield 'loan - list type' => [
            $this->createFinancingObjectTypeField(),
            null,
        ];
    }

    private function getEntity(Field $field)
    {
        $entity = null;

        switch ($field->getCategory()) {
            case 'profile':
                $this->withBorrower($this->reservation);
                $entity = $this->reservation->getBorrower();

                break;

            case 'project':
                $this->withProject($this->reservation);
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
            $this->programEligibilityRepository->reveal(),
            $this->programEligibilityConfigurationRepository->reveal(),
            $this->reservationAccessor->reveal(),
            $this->eligibilityConditionChecker->reveal()
        );
    }
}
