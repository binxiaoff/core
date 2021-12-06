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
    public const RESERVATION_DRAFT_1 = 'reservation-draft-1';
    public const RESERVATION_DRAFT_2 = 'reservation-draft-2';
    public const RESERVATION_SENT_1  = 'reservation-sent-1';
    public const RESERVATION_SENT_2  = 'reservation-sent-2';

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

        foreach ($this->loadData() as $reference => $reservationData) {
            $reservation = $this->buildReservation($reservationData);

            $this->setPublicId($reservation, $reference);
            $this->addReference($reference, $reservation);

            $manager->persist($reservation);
        }

        $manager->flush();
    }

    private function loadData(): iterable
    {
        /** @var Program $program */
        $program = $this->getReference(ProgramFixtures::REFERENCE_COMMERCIALIZED);
        /** @var Participation $participation */
        $participation = $this->getReference(ParticipationFixtures::PARTICIPANT_BASIC);
        /** @var Staff $addedBy */
        $addedBy = $participation->getParticipant()->getStaff()->current();

        $this->loginStaff($addedBy);

        yield self::RESERVATION_DRAFT_1 => [
            'name'     => 'Reservation draft_1',
            'program'  => $program,
            'borrower' => [
                FieldAlias::YOUNG_FARMER         => true,
                FieldAlias::CREATION_IN_PROGRESS => true,
                FieldAlias::SUBSIDIARY           => true,
                FieldAlias::TURNOVER             => 0,
                FieldAlias::TOTAL_ASSETS         => 0,
            ],
            'project'          => [],
            'financingObjects' => [],
            'addedBy'          => $addedBy,
            'currentStatus'    => ReservationStatus::STATUS_DRAFT,
        ];
        yield self::RESERVATION_DRAFT_2 => [
            'name'     => 'Reservation draft_2',
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
        yield self::RESERVATION_SENT_1 => [
            'name'     => 'Reservation sent_1',
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
                ['mainLoan' => true, FieldAlias::LOAN_DURATION => 6],
                ['mainLoan' => false, FieldAlias::LOAN_DURATION => 6],
            ],
            'addedBy'       => $addedBy,
            'currentStatus' => ReservationStatus::STATUS_SENT,
        ];
        yield self::RESERVATION_SENT_2 => [
            'name'     => 'Reservation sent_2',
            'program'  => $program,
            'borrower' => [
                FieldAlias::YOUNG_FARMER         => false,
                FieldAlias::CREATION_IN_PROGRESS => false,
                FieldAlias::SUBSIDIARY           => true,
                FieldAlias::TURNOVER             => 2048,
                FieldAlias::TOTAL_ASSETS         => 42,
            ],
            'project' => [
                FieldAlias::RECEIVING_GRANT      => false,
                FieldAlias::TOTAL_FEI_CREDIT     => 100,
                FieldAlias::CREDIT_EXCLUDING_FEI => 42,
            ],
            'financingObjects' => [
                ['mainLoan' => true, FieldAlias::LOAN_DURATION => 2],
            ],
            'addedBy'       => $addedBy,
            'currentStatus' => ReservationStatus::STATUS_SENT,
        ];
    }

    private function buildReservation(array $reservationData): Reservation
    {
        $reservation = new Reservation($reservationData['program'], $reservationData['addedBy']);
        $reservation->setName($reservationData['name']);

        $this->withBorrower($reservation, $reservationData['borrower']);

        $totalAmount = 0;

        if (false === empty($reservationData['financingObjects'])) {
            for ($i = 0; $i < \count($reservationData['financingObjects']); ++$i) {
                $financingObject = $this->createFinancingObject($reservation, $reservationData['financingObjects'][$i]);
                $this->entityManager->persist($financingObject);
                $totalAmount += (float) $financingObject->getLoanMoney()->getAmount();
            }
        }

        if (false === empty($reservationData['project'])) {
            $this->withProject($reservation, $reservationData['project']);
        }

        $reservation->getProject()->setFundingMoney(new NullableMoney('EUR', (string) $totalAmount));

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
            ->setBorrowerType($this->findProgramChoiceOption($program, 'field-borrower_type', 'Installé'))
            ->setYoungFarmer($data[FieldAlias::YOUNG_FARMER])
            ->setCreationInProgress($data[FieldAlias::CREATION_IN_PROGRESS])
            ->setSubsidiary($data[FieldAlias::SUBSIDIARY])
            ->setCompanyName('Borrower Company')
            ->setActivityStartDate(new DateTimeImmutable())
            ->setAddressStreet($this->faker->streetAddress)
            ->setAddressCity($this->faker->city)
            ->setAddressPostCode($this->faker->postcode)
            ->setAddressDepartment($this->findProgramChoiceOption($program, 'field-activity_department', '75'))
            ->setAddressCountry($this->findProgramChoiceOption($program, 'field-activity_country', 'FR'))
            ->setRegistrationNumber('12 23 45 678 987')
            ->setLegalForm($this->findProgramChoiceOption($program, 'field-legal_form', 'SAS'))
            ->setCompanyNafCode($this->findProgramChoiceOption($program, 'field-company_naf_code', '0001A'))
            ->setEmployeesNumber(42)
            ->setExploitationSize($this->findProgramChoiceOption($program, 'field-exploitation_size', '42'))
            ->setTurnover(new NullableMoney('EUR', (string) $data[FieldAlias::TURNOVER]))
            ->setTotalAssets(new NullableMoney('EUR', (string) $data[FieldAlias::TOTAL_ASSETS]))
            ->setGrade('B')
        ;
    }

    private function withProject(Reservation $reservation, array $data): void
    {
        $program = $reservation->getProgram();
        $project = $reservation->getProject();

        $project
            ->addInvestmentThematic(
                $this->findProgramChoiceOption(
                    $program,
                    'field-investment_thematic',
                    'Thématique : ' . $this->faker->sentence
                )
            )
            ->addInvestmentThematic(
                $this->findProgramChoiceOption(
                    $program,
                    'field-investment_thematic',
                    'Thématique : ' . $this->faker->sentence
                )
            )
            ->setInvestmentType(
                $this->findProgramChoiceOption($program, 'field-investment_type', 'Type : ' . $this->faker->sentence)
            )
            ->setDetail($this->faker->sentence)
            ->setAidIntensity($this->findProgramChoiceOption($program, 'field-aid_intensity', '0.40'))
            ->setAdditionalGuaranty(
                $this->findProgramChoiceOption($program, 'field-additional_guaranty', $this->faker->sentence(3))
            )
            ->setAgriculturalBranch(
                $this->findProgramChoiceOption(
                    $program,
                    'field-agricultural_branch',
                    'Branch N: ' . $this->faker->sentence
                )
            )
            ->setAddressStreet($this->faker->streetAddress)
            ->setAddressCity($this->faker->city)
            ->setAddressPostCode($this->faker->postcode)
            ->setAddressDepartment(
                $this->findProgramChoiceOption($program, 'field-investment_department', '75')
            )
            ->setAddressCountry($this->findProgramChoiceOption($program, 'field-investment_country', 'FR'))
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

        return (new FinancingObject($reservation, $loanMoney, $data['mainLoan'], $this->faker->sentence(3, true)))
            ->setSupportingGenerationsRenewal(true)
            ->setFinancingObjectType(
                $this->findProgramChoiceOption($program, 'field-financing_object_type', $this->faker->text(255))
            )
            ->setLoanNafCode($this->findProgramChoiceOption($program, 'field-loan_naf_code', '0001A'))
            ->setBfrValue(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setLoanType($this->findProgramChoiceOption($program, 'field-loan_type', 'short_term'))
            ->setLoanDuration($data[FieldAlias::LOAN_DURATION])
            ->setLoanDeferral($this->faker->numberBetween(0, 12))
            ->setLoanPeriodicity($this->findProgramChoiceOption($program, 'field-loan_periodicity', 'monthly'))
            ->setInvestmentLocation($this->findProgramChoiceOption($program, 'field-investment_location', 'Paris'))
            ->setInvestmentLocation($this->findProgramChoiceOption($program, 'field-product_category_code'))
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
