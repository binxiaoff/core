<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Service\Eligibility;

use ApiPlatform\Core\Api\IriConverterInterface;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConfigurationRepository;
use KLS\CreditGuaranty\FEI\Service\Eligibility\EligibilityChecker;
use KLS\CreditGuaranty\FEI\Service\Eligibility\EligibilityConditionChecker;
use KLS\CreditGuaranty\FEI\Service\ReservationAccessor;
use KLS\Test\CreditGuaranty\FEI\Unit\Traits\ReservationSetTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \KLS\CreditGuaranty\FEI\Service\Eligibility\EligibilityChecker
 *
 * @internal
 */
class EligibilityCheckerTest extends TestCase
{
    use ProphecyTrait;
    use ReservationSetTrait;

    /** @var IriConverterInterface|ObjectProphecy */
    private $iriConverter;

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
        $this->iriConverter = $this->prophesize(
            IriConverterInterface::class
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
        $this->iriConverter                              = null;
        $this->programEligibilityConfigurationRepository = null;
        $this->reservationAccessor                       = null;
        $this->eligibilityConditionChecker               = null;
        $this->reservation                               = null;
    }

    /**
     * @covers ::check
     */
    public function testCheck(): void
    {
        $program = $this->reservation->getProgram();

        $this->withBorrower($this->reservation);
        $this->withProject($this->reservation);
        $financingObject1 = $this->createFinancingObject($this->reservation, true);
        $financingObject2 = $this->createFinancingObject($this->reservation, true);
        $this->forcePropertyValue($financingObject1, 'publicId', '1');
        $this->forcePropertyValue($financingObject2, 'publicId', '2');
        $this->reservation->addFinancingObject($financingObject1);
        $this->reservation->addFinancingObject($financingObject2);

        $profileField1              = $this->createBorrowerTypeField();
        $projectField1              = $this->createReceivingGrantField();
        $loanField1                 = $this->createLoanDeferralField();
        $loanField2                 = $this->createLoanTypeField();
        $borrowerTypeOption         = new ProgramChoiceOption($program, 'borrower type', $profileField1);
        $profileProgramEligibility1 = new ProgramEligibility($program, $profileField1);
        $projectProgramEligibility1 = new ProgramEligibility($program, $projectField1);
        $loanProgramEligibility1    = new ProgramEligibility($program, $loanField1);
        $loanProgramEligibility2    = new ProgramEligibility($program, $loanField2);

        $this->forcePropertyValue($program, 'programEligibilities', new ArrayCollection([
            $profileProgramEligibility1,
            $projectProgramEligibility1,
            $loanProgramEligibility1,
            $loanProgramEligibility2,
        ]));

        $profileProgramEligibilityConfiguration1 = new ProgramEligibilityConfiguration(
            $profileProgramEligibility1,
            $borrowerTypeOption,
            null,
            true
        );
        $projectProgramEligibilityConfiguration1 = new ProgramEligibilityConfiguration(
            $projectProgramEligibility1,
            null,
            '0',
            false
        );
        $loanProgramEligibilityConfiguration1 = new ProgramEligibilityConfiguration(
            $loanProgramEligibility1,
            null,
            null,
            true
        );

        // profile category
        $borrower = $this->reservation->getBorrower();
        $this->reservationAccessor->getEntity($this->reservation, $profileField1)
            ->shouldBeCalledOnce()
            ->willReturn($borrower)
        ;
        // borrower type field
        $this->reservationAccessor->getValue($borrower, $profileField1)
            ->shouldBeCalledOnce()
            ->willReturn($borrower->getBorrowerType())
        ;
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility'  => $profileProgramEligibility1,
            'programChoiceOption' => $borrower->getBorrowerType(),
        ])
            ->shouldBeCalledOnce()
            ->willReturn($profileProgramEligibilityConfiguration1)
        ;
        $this->eligibilityConditionChecker->checkByConfiguration($borrower, $profileProgramEligibilityConfiguration1)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        // project category
        $project = $this->reservation->getProject();
        $this->reservationAccessor->getEntity($this->reservation, $projectField1)
            ->shouldBeCalledOnce()
            ->willReturn($project)
        ;
        // receiving grant field
        $this->reservationAccessor->getValue($project, $projectField1)
            ->shouldBeCalledOnce()
            ->willReturn($project->isReceivingGrant())
        ;
        $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $projectProgramEligibility1,
            'value'              => (int) $project->isReceivingGrant(),
        ])
            ->shouldBeCalledOnce()
            ->willReturn($projectProgramEligibilityConfiguration1)
        ;
        $this->eligibilityConditionChecker->checkByConfiguration($project, $projectProgramEligibilityConfiguration1)
            ->shouldNotBeCalled()
        ;

        // loan category
        $financingObjects = $this->reservation->getFinancingObjects();
        $this->reservationAccessor->getEntity($this->reservation, $loanField1)
            ->shouldBeCalledOnce()
            ->willReturn($financingObjects)
        ;
        // loan deferral field
        foreach ($financingObjects as $financingObject) {
            $this->reservationAccessor->getValue($financingObject, $loanField1)
                ->shouldBeCalledOnce()
                ->willReturn($financingObject->getLoanDeferral())
            ;
            $this->programEligibilityConfigurationRepository->findOneBy([
                'programEligibility' => $loanProgramEligibility1,
            ])
                ->shouldBeCalled()
                ->willReturn($loanProgramEligibilityConfiguration1)
            ;
        }
        $this->eligibilityConditionChecker->checkByConfiguration(
            $financingObject1,
            $loanProgramEligibilityConfiguration1
        )
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;
        $this->eligibilityConditionChecker->checkByConfiguration(
            $financingObject2,
            $loanProgramEligibilityConfiguration1
        )
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;
        // loan type field
        foreach ($financingObjects as $financingObject) {
            $this->reservationAccessor->getValue($financingObject, $loanField2)
                ->shouldBeCalledOnce()
                ->willReturn($financingObject->getLoanType())
            ;
            $this->programEligibilityConfigurationRepository->findOneBy([
                'programEligibility'  => $loanProgramEligibility2,
                'programChoiceOption' => $financingObject->getLoanType(),
            ])
                ->shouldBeCalled()
                ->willReturn(null)
            ;
            $this->iriConverter->getIriFromItem($financingObject)
                ->shouldBeCalled()
                ->willReturn('/credit_guaranty/financing_objects/' . $financingObject->getPublicId())
            ;
        }

        $eligibilityChecker = $this->createTestObject();
        $result             = $eligibilityChecker->check($this->reservation);
        $expected           = [
            'project' => [FieldAlias::RECEIVING_GRANT],
            'loan'    => [
                '/credit_guaranty/financing_objects/1' => [FieldAlias::LOAN_DEFERRAL, FieldAlias::LOAN_TYPE],
                '/credit_guaranty/financing_objects/2' => [FieldAlias::LOAN_TYPE],
            ],
        ];

        static::assertSame($expected, $result);
    }

    // check without program eligibilities
    // check without financing object
    // check with a non-existent field type

    /**
     * @covers ::check
     *
     * @dataProvider creationInProgressRelatedFieldsProvider
     */
    public function testCheckWithCreationInProgressRelatedField(array $data, array $expected): void
    {
        $program = $this->reservation->getProgram();

        $this->withBorrower($this->reservation);
        $this->reservation->getBorrower()
            ->setCreationInProgress($data[FieldAlias::CREATION_IN_PROGRESS]['value'])
            ->setActivityStartDate($data[FieldAlias::ACTIVITY_START_DATE]['value'])
            ->setRegistrationNumber($data[FieldAlias::REGISTRATION_NUMBER]['value'])
        ;

        $field1 = $this->createCreationInProgressField();
        $field2 = $this->createActivityStartDateField();
        $field3 = $this->createRegistrationNumberField();

        $programEligibility1 = new ProgramEligibility($program, $field1);
        $programEligibility2 = new ProgramEligibility($program, $field2);
        $programEligibility3 = new ProgramEligibility($program, $field3);

        $this->forcePropertyValue($program, 'programEligibilities', new ArrayCollection([
            $programEligibility1,
            $programEligibility2,
            $programEligibility3,
        ]));

        $programEligibilityConfiguration1 = [
            false => new ProgramEligibilityConfiguration($programEligibility1, null, '0', true),
            true  => new ProgramEligibilityConfiguration($programEligibility1, null, '1', true),
        ];
        $programEligibilityConfiguration2 = new ProgramEligibilityConfiguration(
            $programEligibility2,
            null,
            null,
            true
        );
        $programEligibilityConfiguration3 = new ProgramEligibilityConfiguration(
            $programEligibility3,
            null,
            null,
            true
        );

        $borrower = $this->reservation->getBorrower();
        $this->reservationAccessor->getEntity($this->reservation, $field1)
            ->shouldBeCalledOnce()
            ->willReturn($borrower)
        ;
        // creation in progress field
        $this->reservationAccessor->getValue($borrower, $field1)
            ->shouldBeCalledOnce()
            ->willReturn($borrower->isCreationInProgress())
        ;
        if ($data[FieldAlias::CREATION_IN_PROGRESS]['isValueValid']) {
            $this->programEligibilityConfigurationRepository->findOneBy([
                'programEligibility' => $programEligibility1,
                'value'              => $borrower->isCreationInProgress(),
            ])
                ->shouldBeCalledOnce()
                ->willReturn($programEligibilityConfiguration1[$borrower->isCreationInProgress()])
            ;
            $this->eligibilityConditionChecker->checkByConfiguration(
                $borrower,
                $programEligibilityConfiguration1[$borrower->isCreationInProgress()]
            )
                ->shouldBeCalled()
                ->willReturn(true)
            ;
        } else {
            $this->programEligibilityConfigurationRepository->findOneBy([
                'programEligibility' => $programEligibility1,
                'value'              => $borrower->isCreationInProgress(),
            ])
                ->shouldNotBeCalled()
            ;
        }
        // activity start date field
        $this->reservationAccessor->getValue($borrower, $field2)
            ->shouldBeCalledOnce()
            ->willReturn($borrower->getActivityStartDate())
        ;
        if ($data[FieldAlias::ACTIVITY_START_DATE]['isValueValid']) {
            $this->programEligibilityConfigurationRepository->findOneBy([
                'programEligibility' => $programEligibility2,
            ])
                ->shouldBeCalledOnce()
                ->willReturn($programEligibilityConfiguration2)
            ;
            $this->eligibilityConditionChecker->checkByConfiguration(
                $borrower,
                $programEligibilityConfiguration2
            )
                ->shouldBeCalled()
                ->willReturn(true)
            ;
        } else {
            $this->programEligibilityConfigurationRepository->findOneBy([
                'programEligibility' => $programEligibility2,
            ])
                ->shouldNotBeCalled()
            ;
        }
        // registration number field
        $this->reservationAccessor->getValue($borrower, $field3)
            ->shouldBeCalledOnce()
            ->willReturn($borrower->getRegistrationNumber())
        ;

        if ($data[FieldAlias::REGISTRATION_NUMBER]['isValueValid']) {
            $this->programEligibilityConfigurationRepository->findOneBy([
                'programEligibility' => $programEligibility3,
            ])
                ->shouldBeCalledOnce()
                ->willReturn($programEligibilityConfiguration3)
            ;
            $this->eligibilityConditionChecker->checkByConfiguration(
                $borrower,
                $programEligibilityConfiguration3
            )
                ->shouldBeCalled()
                ->willReturn(true)
            ;
        } else {
            $this->programEligibilityConfigurationRepository->findOneBy([
                'programEligibility' => $programEligibility3,
            ])
                ->shouldNotBeCalled()
            ;
        }
        $this->iriConverter->getIriFromItem(Argument::any())->shouldNotBeCalled();

        $eligibilityChecker = $this->createTestObject();
        $result             = $eligibilityChecker->check($this->reservation);

        static::assertSame($expected, $result);
    }

    public function creationInProgressRelatedFieldsProvider(): iterable
    {
        yield 'creationInProgress true - activityStartDate not null - registrationNumber not null => valid' => [
            [
                FieldAlias::CREATION_IN_PROGRESS => [
                    'value'        => true,
                    'isValueValid' => true,
                ],
                FieldAlias::ACTIVITY_START_DATE => [
                    'value'        => new DateTimeImmutable(),
                    'isValueValid' => true,
                ],
                FieldAlias::REGISTRATION_NUMBER => [
                    'value'        => '42424242424242',
                    'isValueValid' => true,
                ],
            ],
            [],
        ];
        yield 'creationInProgress true - activityStartDate not null - registrationNumber null => valid' => [
            [
                FieldAlias::CREATION_IN_PROGRESS => [
                    'value'        => true,
                    'isValueValid' => true,
                ],
                FieldAlias::ACTIVITY_START_DATE => [
                    'value'        => new DateTimeImmutable(),
                    'isValueValid' => true,
                ],
                FieldAlias::REGISTRATION_NUMBER => [
                    'value'        => null,
                    'isValueValid' => true,
                ],
            ],
            [],
        ];
        yield 'creationInProgress true - activityStartDate null - registrationNumber not null => valid' => [
            [
                FieldAlias::CREATION_IN_PROGRESS => [
                    'value'        => true,
                    'isValueValid' => true,
                ],
                FieldAlias::ACTIVITY_START_DATE => [
                    'value'        => null,
                    'isValueValid' => true,
                ],
                FieldAlias::REGISTRATION_NUMBER => [
                    'value'        => '42424242424242',
                    'isValueValid' => true,
                ],
            ],
            [],
        ];
        yield 'creationInProgress true - activityStartDate null - registrationNumber null => valid' => [
            [
                FieldAlias::CREATION_IN_PROGRESS => [
                    'value'        => true,
                    'isValueValid' => true,
                ],
                FieldAlias::ACTIVITY_START_DATE => [
                    'value'        => null,
                    'isValueValid' => true,
                ],
                FieldAlias::REGISTRATION_NUMBER => [
                    'value'        => null,
                    'isValueValid' => true,
                ],
            ],
            [],
        ];
        yield 'creationInProgress false - activityStartDate not null - registrationNumber not null => valid' => [
            [
                FieldAlias::CREATION_IN_PROGRESS => [
                    'value'        => false,
                    'isValueValid' => true,
                ],
                FieldAlias::ACTIVITY_START_DATE => [
                    'value'        => new DateTimeImmutable(),
                    'isValueValid' => true,
                ],
                FieldAlias::REGISTRATION_NUMBER => [
                    'value'        => '42424242424242',
                    'isValueValid' => true,
                ],
            ],
            [],
        ];
        yield 'creationInProgress false - activityStartDate not null - registrationNumber null => invalid' => [
            [
                FieldAlias::CREATION_IN_PROGRESS => [
                    'value'        => false,
                    'isValueValid' => true,
                ],
                FieldAlias::ACTIVITY_START_DATE => [
                    'value'        => new DateTimeImmutable(),
                    'isValueValid' => true,
                ],
                FieldAlias::REGISTRATION_NUMBER => [
                    'value'        => null,
                    'isValueValid' => false,
                ],
            ],
            ['profile' => [FieldAlias::REGISTRATION_NUMBER]],
        ];
        yield 'creationInProgress false - activityStartDate null - registrationNumber not null => invalid' => [
            [
                FieldAlias::CREATION_IN_PROGRESS => [
                    'value'        => false,
                    'isValueValid' => true,
                ],
                FieldAlias::ACTIVITY_START_DATE => [
                    'value'        => null,
                    'isValueValid' => false,
                ],
                FieldAlias::REGISTRATION_NUMBER => [
                    'value'        => '42424242424242',
                    'isValueValid' => true,
                ],
            ],
            ['profile' => [FieldAlias::ACTIVITY_START_DATE]],
        ];
        yield 'creationInProgress false - activityStartDate null - registrationNumber null => invalid' => [
            [
                FieldAlias::CREATION_IN_PROGRESS => [
                    'value'        => false,
                    'isValueValid' => true,
                ],
                FieldAlias::ACTIVITY_START_DATE => [
                    'value'        => null,
                    'isValueValid' => false,
                ],
                FieldAlias::REGISTRATION_NUMBER => [
                    'value'        => null,
                    'isValueValid' => false,
                ],
            ],
            ['profile' => [FieldAlias::ACTIVITY_START_DATE, FieldAlias::REGISTRATION_NUMBER]],
        ];
    }

    private function createTestObject(): EligibilityChecker
    {
        return new EligibilityChecker(
            $this->iriConverter->reveal(),
            $this->programEligibilityConfigurationRepository->reveal(),
            $this->reservationAccessor->reveal(),
            $this->eligibilityConditionChecker->reveal()
        );
    }
}
