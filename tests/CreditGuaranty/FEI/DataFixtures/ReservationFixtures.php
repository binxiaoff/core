<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Faker\Generator;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\Core\Entity\Staff;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Participation;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;
use KLS\CreditGuaranty\FEI\Repository\ProgramChoiceOptionRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityRepository;
use KLS\Test\Core\DataFixtures\AbstractFixtures;
use KLS\Test\Core\DataFixtures\NafNaceFixtures;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ReservationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const RESERVATION_1 = 'reservation-1';
    public const RESERVATION_2 = 'reservation-2';
    public const RESERVATION_3 = 'reservation-3';
    public const RESERVATION_4 = 'reservation-4';

    public const ALL_PROGRAM_COMMERCIALIZED_RESERVATIONS = [
        self::RESERVATION_1,
        self::RESERVATION_2,
        self::RESERVATION_3,
    ];

    private Generator $faker;
    private ObjectManager $entityManager;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private ProgramEligibilityRepository $programEligibilityRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ProgramChoiceOptionRepository $programChoiceOptionRepository,
        ProgramEligibilityRepository $programEligibilityRepository
    ) {
        parent::__construct($tokenStorage);
        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
        $this->programEligibilityRepository  = $programEligibilityRepository;
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            NafNaceFixtures::class,
            ProgramFixtures::class,
            ParticipationFixtures::class,
            ProgramChoiceOptionFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $this->faker = Faker\Factory::create('fr_FR');

        $this->entityManager = $manager;

        foreach ($this->loadDataForProgramCommercialized() as $reference => $reservationData) {
            $reservation = $this->buildReservation($reservationData);

            $this->setPublicId($reservation, $reference);
            $this->addReference($reference, $reservation);

            $manager->persist($reservation);
        }

        $manager->flush();

        foreach ($this->loadDataForProgramPaused() as $reference => $reservationData) {
            $reservation = $this->buildReservation($reservationData);

            $this->setPublicId($reservation, $reference);
            $this->addReference($reference, $reservation);

            $manager->persist($reservation);
        }

        $manager->flush();
    }

    private function loadDataForProgramCommercialized(): iterable
    {
        /** @var Program $program */
        $program = $this->getReference(ProgramFixtures::REFERENCE_COMMERCIALIZED);
        /** @var Participation $participation */
        $participation = $this->getReference(ParticipationFixtures::PARTICIPANT_BASIC);
        /** @var Staff $addedBy */
        $addedBy = $participation->getParticipant()->getStaff()->current();

        $this->loginStaff($addedBy);

        yield self::RESERVATION_1 => [
            'name'     => 'Reservation 1',
            'program'  => $program,
            'borrower' => [
                FieldAlias::YOUNG_FARMER         => false,
                FieldAlias::CREATION_IN_PROGRESS => true,
                FieldAlias::SUBSIDIARY           => false,
                FieldAlias::TURNOVER             => 0,
                FieldAlias::TOTAL_ASSETS         => 0,
            ],
            'project'          => [],
            'financingObjects' => [],
            'addedBy'          => $addedBy,
            'currentStatus'    => ReservationStatus::STATUS_DRAFT,
        ];
        yield self::RESERVATION_2 => [
            'name'     => 'Reservation 2',
            'program'  => $program,
            'borrower' => [
                FieldAlias::YOUNG_FARMER         => true,
                FieldAlias::CREATION_IN_PROGRESS => true,
                FieldAlias::SUBSIDIARY           => true,
                FieldAlias::TURNOVER             => 100,
                FieldAlias::TOTAL_ASSETS         => 2048,
            ],
            'project' => [
                FieldAlias::RECEIVING_GRANT      => true,
                FieldAlias::TOTAL_FEI_CREDIT     => 1000,
                FieldAlias::CREDIT_EXCLUDING_FEI => 3000,
            ],
            'financingObjects' => [
                [
                    FieldAlias::MAIN_LOAN           => true,
                    FieldAlias::LOAN_DURATION       => 6,
                    FieldAlias::INVESTMENT_LOCATION => 'Paris',
                ],
                [
                    FieldAlias::MAIN_LOAN           => false,
                    FieldAlias::LOAN_DURATION       => 6,
                    FieldAlias::INVESTMENT_LOCATION => 'Paris',
                ],
            ],
            'addedBy'       => $addedBy,
            'currentStatus' => ReservationStatus::STATUS_DRAFT,
        ];
        yield self::RESERVATION_3 => [
            'name'     => 'Reservation 3',
            'program'  => $program,
            'borrower' => [
                FieldAlias::YOUNG_FARMER         => true,
                FieldAlias::CREATION_IN_PROGRESS => true,
                FieldAlias::SUBSIDIARY           => true,
                FieldAlias::TURNOVER             => 2048,
                FieldAlias::TOTAL_ASSETS         => 10000,
            ],
            'project' => [
                FieldAlias::RECEIVING_GRANT      => true,
                FieldAlias::TOTAL_FEI_CREDIT     => 100,
                FieldAlias::CREDIT_EXCLUDING_FEI => 42,
            ],
            'financingObjects' => [
                [
                    FieldAlias::MAIN_LOAN           => true,
                    FieldAlias::LOAN_DURATION       => 4,
                    FieldAlias::INVESTMENT_LOCATION => 'Paris',
                ],
            ],
            'addedBy'       => $addedBy,
            'currentStatus' => ReservationStatus::STATUS_SENT,
        ];
    }

    private function loadDataForProgramPaused(): iterable
    {
        /** @var Program $program */
        $program = $this->getReference(ProgramFixtures::REFERENCE_PAUSED);
        /** @var Participation $participation */
        $participation = $this->getReference(ParticipationFixtures::PARTICIPANT_BASIC);
        /** @var Staff $addedBy */
        $addedBy = $participation->getParticipant()->getStaff()->current();

        $this->loginStaff($addedBy);

        yield self::RESERVATION_4 => [
            'name'     => 'Reservation 4',
            'program'  => $program,
            'borrower' => [
                FieldAlias::YOUNG_FARMER         => true,
                FieldAlias::CREATION_IN_PROGRESS => true,
                FieldAlias::SUBSIDIARY           => true,
                FieldAlias::TURNOVER             => 2048,
                FieldAlias::TOTAL_ASSETS         => 10000,
            ],
            'project' => [
                FieldAlias::RECEIVING_GRANT      => true,
                FieldAlias::TOTAL_FEI_CREDIT     => 100,
                FieldAlias::CREDIT_EXCLUDING_FEI => 42,
            ],
            'financingObjects' => [
                [
                    FieldAlias::MAIN_LOAN           => true,
                    FieldAlias::LOAN_DURATION       => 4,
                    FieldAlias::INVESTMENT_LOCATION => 'Paris',
                ],
                [
                    FieldAlias::MAIN_LOAN           => false,
                    FieldAlias::LOAN_DURATION       => 5,
                    FieldAlias::INVESTMENT_LOCATION => 'Seine-et-Marne',
                ],
                [
                    FieldAlias::MAIN_LOAN           => false,
                    FieldAlias::LOAN_DURATION       => 6,
                    FieldAlias::INVESTMENT_LOCATION => 'Val-de-Marne',
                ],
                [
                    FieldAlias::MAIN_LOAN           => false,
                    FieldAlias::LOAN_DURATION       => 7,
                    FieldAlias::INVESTMENT_LOCATION => 'Paris',
                ],
                [
                    FieldAlias::MAIN_LOAN           => false,
                    FieldAlias::LOAN_DURATION       => 8,
                    FieldAlias::INVESTMENT_LOCATION => 'Paris',
                ],
                [
                    FieldAlias::MAIN_LOAN           => false,
                    FieldAlias::LOAN_DURATION       => 9,
                    FieldAlias::INVESTMENT_LOCATION => 'Val-de-Marne',
                ],
                [
                    FieldAlias::MAIN_LOAN           => false,
                    FieldAlias::LOAN_DURATION       => 10,
                    FieldAlias::INVESTMENT_LOCATION => 'Seine-et-Marne',
                ],
                [
                    FieldAlias::MAIN_LOAN           => false,
                    FieldAlias::LOAN_DURATION       => 11,
                    FieldAlias::INVESTMENT_LOCATION => 'Paris',
                ],
                [
                    FieldAlias::MAIN_LOAN           => false,
                    FieldAlias::LOAN_DURATION       => 12,
                    FieldAlias::INVESTMENT_LOCATION => 'Paris',
                ],
            ],
            'addedBy'       => $addedBy,
            'currentStatus' => ReservationStatus::STATUS_CONTRACT_FORMALIZED,
        ];
    }

    private function buildReservation(array $reservationData): Reservation
    {
        $reservation = new Reservation($reservationData['program'], $reservationData['addedBy']);
        $reservation->setName($reservationData['name']);

        $this->withBorrower($reservation, $reservationData['borrower']);

        $totalAmount = 0;

        if (false === empty($reservationData['financingObjects'])) {
            $countFinancingObjects = \count($reservationData['financingObjects']);
            for ($i = 0; $i < $countFinancingObjects; ++$i) {
                $financingObject = $this->createFinancingObject($reservation, $reservationData['financingObjects'][$i]);
                $this->entityManager->persist($financingObject);
                $totalAmount += (float) $financingObject->getLoanMoney()->getAmount();
            }
        }

        if (false === empty($reservationData['project'])) {
            $this->withProject($reservation, $reservationData['project']);
        }

        $reservation->getProject()->setFundingMoney(new NullableMoney('EUR', (string) $totalAmount));

        if (ReservationStatus::STATUS_CONTRACT_FORMALIZED === $reservation->getCurrentStatus()->getStatus()) {
            $reservation->setSigningDate(new DateTimeImmutable());
        }

        $currentReservationStatus = new ReservationStatus(
            $reservation,
            $reservationData['currentStatus'],
            $reservationData['addedBy']
        );
        $this->createReservationStatuses($currentReservationStatus);
        $reservation->setCurrentStatus($currentReservationStatus);

        return $reservation;
    }

    private function withBorrower(Reservation $reservation, array $data): void
    {
        $program = $reservation->getProgram();

        $reservation->getBorrower()
            ->setBeneficiaryName('Borrower Name')
            ->setBorrowerType(
                $this->findProgramChoiceOption($program, FieldAlias::BORROWER_TYPE, 'Installé')
            )
            ->setYoungFarmer($data[FieldAlias::YOUNG_FARMER])
            ->setCreationInProgress($data[FieldAlias::CREATION_IN_PROGRESS])
            ->setSubsidiary($data[FieldAlias::SUBSIDIARY])
            ->setEconomicallyViable(true)
            ->setBenefitingProfitTransfer(true)
            ->setListedOnStockMarket(true)
            ->setInNonCooperativeJurisdiction(true)
            ->setSubjectOfUnperformedRecoveryOrder(true)
            ->setSubjectOfRestructuringPlan(true)
            ->setProjectReceivedFeagaOcmFunding(true)
            ->setLoanSupportingDocumentsDatesAfterApplication(true)
            ->setLoanAllowedRefinanceRestructure(true)
            ->setTransactionAffected(true)
            ->setCompanyName('Borrower Company')
            ->setActivityStartDate(new DateTimeImmutable())
            ->setAddressStreet($this->faker->streetAddress)
            ->setAddressCity($this->faker->city)
            ->setAddressPostCode($this->faker->postcode)
            ->setAddressDepartment(
                $this->findProgramChoiceOption($program, FieldAlias::ACTIVITY_DEPARTMENT, '75')
            )
            ->setAddressCountry($this->findProgramChoiceOption($program, FieldAlias::ACTIVITY_COUNTRY, 'FR'))
            ->setRegistrationNumber('12 23 45 678 987')
            ->setLegalForm($this->findProgramChoiceOption($program, FieldAlias::LEGAL_FORM, 'SAS'))
            ->setCompanyNafCode(
                $this->findProgramChoiceOption($program, FieldAlias::COMPANY_NAF_CODE, '0001A')
            )
            ->setEmployeesNumber(42)
            ->setExploitationSize(
                $this->findProgramChoiceOption($program, FieldAlias::EXPLOITATION_SIZE, '42')
            )
            ->setTurnover(new NullableMoney('EUR', (string) $data[FieldAlias::TURNOVER]))
            ->setTotalAssets(new NullableMoney('EUR', (string) $data[FieldAlias::TOTAL_ASSETS]))
            ->setTargetType($this->findProgramChoiceOption($program, FieldAlias::TARGET_TYPE))
            ->setGrade('B')
        ;
    }

    private function withProject(Reservation $reservation, array $data): void
    {
        $program = $reservation->getProgram();
        $project = $reservation->getProject();

        $project
            ->addInvestmentThematic($this->findProgramChoiceOption(
                $program,
                FieldAlias::INVESTMENT_THEMATIC,
                'Renouvellement et installation'
            ))
            ->addInvestmentThematic($this->findProgramChoiceOption(
                $program,
                FieldAlias::INVESTMENT_THEMATIC,
                'Mieux répondre / renforcer'
            ))
            ->setInvestmentType($this->findProgramChoiceOption(
                $program,
                FieldAlias::INVESTMENT_TYPE,
                'Type : ' . $this->faker->sentence
            ))
            ->setDetail($this->faker->sentence)
            ->setAidIntensity($this->findProgramChoiceOption($program, FieldAlias::AID_INTENSITY, '0.40'))
            ->setAdditionalGuaranty($this->findProgramChoiceOption(
                $program,
                FieldAlias::ADDITIONAL_GUARANTY,
                $this->faker->sentence(3)
            ))
            ->setAgriculturalBranch(
                $this->findProgramChoiceOption(
                    $program,
                    FieldAlias::AGRICULTURAL_BRANCH,
                    'Branch N: ' . $this->faker->sentence
                )
            )
            ->setAddressStreet($this->faker->streetAddress)
            ->setAddressCity($this->faker->city)
            ->setAddressPostCode($this->faker->postcode)
            ->setAddressDepartment(
                $this->findProgramChoiceOption($program, FieldAlias::INVESTMENT_DEPARTMENT, '75')
            )
            ->setAddressCountry(
                $this->findProgramChoiceOption($program, FieldAlias::INVESTMENT_COUNTRY, 'FR')
            )
            ->setFundingMoney(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setContribution(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setEligibleFeiCredit(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setTotalFeiCredit(new NullableMoney('EUR', (string) $data[FieldAlias::TOTAL_FEI_CREDIT]))
            ->setTangibleFeiCredit(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setIntangibleFeiCredit(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setCreditExcludingFei(new NullableMoney('EUR', (string) $data[FieldAlias::CREDIT_EXCLUDING_FEI]))
            ->setLandValue(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
        ;

        if ($data[FieldAlias::RECEIVING_GRANT]) {
            $project->setGrant(new NullableMoney('EUR', (string) $this->faker->randomNumber()));
        }
    }

    private function createFinancingObject(Reservation $reservation, array $data): FinancingObject
    {
        $program   = $reservation->getProgram();
        $loanMoney = new Money('EUR', '200');

        return (
            new FinancingObject($reservation, $loanMoney, $data[FieldAlias::MAIN_LOAN], $this->faker->sentence(3, true))
        )
            ->setSupportingGenerationsRenewal(true)
            ->setFinancingObjectType($this->findProgramChoiceOption(
                $program,
                FieldAlias::FINANCING_OBJECT_TYPE,
                $this->faker->text(255)
            ))
            ->setLoanNafCode($this->findProgramChoiceOption($program, FieldAlias::LOAN_NAF_CODE, '0001A'))
            ->setBfrValue(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setLoanType($this->findProgramChoiceOption($program, FieldAlias::LOAN_TYPE, 'short_term'))
            ->setLoanDuration($data[FieldAlias::LOAN_DURATION])
            ->setLoanDeferral($this->faker->numberBetween(0, 12))
            ->setLoanPeriodicity(
                $this->findProgramChoiceOption($program, FieldAlias::LOAN_PERIODICITY, 'monthly')
            )
            ->setInvestmentLocation($this->findProgramChoiceOption(
                $program,
                FieldAlias::INVESTMENT_LOCATION,
                $data[FieldAlias::INVESTMENT_LOCATION]
            ))
            ->setProductCategoryCode(
                $this->findProgramChoiceOption($program, FieldAlias::PRODUCT_CATEGORY_CODE)
            )
            ->setLoanNumber('1')
            ->setOperationNumber('2')
        ;
    }

    private function createReservationStatuses(ReservationStatus $currentReservationStatus): void
    {
        if (ReservationStatus::STATUS_DRAFT === $currentReservationStatus->getStatus()) {
            $this->entityManager->persist($currentReservationStatus);

            return;
        }

        foreach (ReservationStatus::ALLOWED_STATUS as $allowedStatus => $allowedStatuses) {
            $previousReservationStatus = new ReservationStatus(
                $currentReservationStatus->getReservation(),
                $allowedStatus,
                $currentReservationStatus->getAddedBy()
            );

            if (ReservationStatus::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION === $allowedStatus) {
                $previousReservationStatus->setComment($this->faker->text(200));
            }

            $this->entityManager->persist($previousReservationStatus);

            if (\in_array($currentReservationStatus->getStatus(), $allowedStatuses, true)) {
                break;
            }
        }

        if (ReservationStatus::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION === $currentReservationStatus->getStatus()) {
            $currentReservationStatus->setComment($this->faker->text(200));
        }

        $this->entityManager->persist($currentReservationStatus);
    }

    private function findProgramChoiceOption(
        Program $program,
        string $fieldReference,
        ?string $description = null
    ): ProgramChoiceOption {
        /** @var Field $field */
        $field = $this->getReference($fieldReference);

        if (empty($description)) {
            $programChoiceOptions = $this->programChoiceOptionRepository->findBy([
                'program' => $program,
                'field'   => $field,
            ]);

            return $programChoiceOptions[\array_rand($programChoiceOptions)];
        }

        $programChoiceOption = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $field,
            'description' => $description,
        ]);

        if (false === ($programChoiceOption instanceof ProgramChoiceOption)) {
            $programChoiceOption = new ProgramChoiceOption($program, $description, $field);
            $this->entityManager->persist($programChoiceOption);
        }

        $programEligibility = $this->programEligibilityRepository->findOneBy([
            'program' => $program,
            'field'   => $field,
        ]);

        if (false === ($programEligibility instanceof ProgramEligibility)) {
            $programEligibility = new ProgramEligibility($program, $field);
            $this->entityManager->persist($programEligibility);
        }

        if (0 === $programEligibility->getProgramEligibilityConfigurations()->count()) {
            $programEligibilityConfiguration = new ProgramEligibilityConfiguration(
                $programEligibility,
                $programChoiceOption,
                null,
                true
            );
            $this->entityManager->persist($programEligibilityConfiguration);
        }

        return $programChoiceOption;
    }
}
