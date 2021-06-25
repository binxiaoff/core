<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Faker\Generator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\Staff;
use Unilend\CreditGuaranty\Entity\Borrower;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\FinancingObject;
use Unilend\CreditGuaranty\Entity\Participation;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use Unilend\CreditGuaranty\Entity\Project;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Entity\ReservationStatus;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityRepository;
use Unilend\Test\Core\DataFixtures\AbstractFixtures;
use Unilend\Test\Core\DataFixtures\NafNaceFixtures;

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

        $this->createCompanyGroup($program, $addedBy);
        $this->loginStaff($addedBy);

        yield self::RESERVATION_DRAFT_1 => [
            'program'       => $program,
            'borrower'      => ['youngFarmer' => true, 'creationInProgress' => true, 'subsidiary' => true],
            'hasProject'    => false,
            'addedBy'       => $addedBy,
            'currentStatus' => ReservationStatus::STATUS_DRAFT,
        ];
        yield self::RESERVATION_DRAFT_2 => [
            'program'       => $program,
            'borrower'      => ['youngFarmer' => false, 'creationInProgress' => true, 'subsidiary' => false],
            'addedBy'       => $addedBy,
            'hasProject'    => false,
            'currentStatus' => ReservationStatus::STATUS_DRAFT,
        ];
        yield self::RESERVATION_SENT_1 => [
            'program'            => $program,
            'borrower'           => ['youngFarmer' => true, 'creationInProgress' => true, 'subsidiary' => true],
            'financingObjectsNb' => 2,
            'financingObjects'   => [['mainLoan' => true], ['mainLoan' => false]],
            'hasProject'         => true,
            'addedBy'            => $addedBy,
            'currentStatus'      => ReservationStatus::STATUS_SENT,
        ];
        yield self::RESERVATION_SENT_2 => [
            'program'            => $program,
            'borrower'           => ['youngFarmer' => false, 'creationInProgress' => false, 'subsidiary' => true],
            'financingObjectsNb' => 2,
            'financingObjects'   => [['mainLoan' => false], ['mainLoan' => true]],
            'hasProject'         => true,
            'addedBy'            => $addedBy,
            'currentStatus'      => ReservationStatus::STATUS_SENT,
        ];
    }

    private function buildReservation(array $reservationData): Reservation
    {
        $reservation = new Reservation($reservationData['program'], $reservationData['addedBy']);
        $totalAmount = 0;

        if (false === empty($reservationData['financingObjectsNb'])) {
            foreach ($reservationData['financingObjects'] as $financingObjectData) {
                $financingObject = $this->createFinancingObject($reservation, $financingObjectData['mainLoan']);
                $this->entityManager->persist($financingObject);
                $totalAmount += (float) $financingObject->getLoanMoney()->getAmount();
            }
        }

        if ($reservationData['hasProject']) {
            $project = $this->createProject($reservation);
            $project->setFundingMoney(new Money('EUR', (string) $totalAmount));
            $reservation->setProject($project);
        }

        $borrower = $this->createBorrower(
            $reservation,
            $reservationData['borrower']['youngFarmer'],
            $reservationData['borrower']['creationInProgress'],
            $reservationData['borrower']['subsidiary']
        );
        $currentReservationStatus = new ReservationStatus($reservation, $reservationData['currentStatus'], $reservationData['addedBy']);
        $this->createReservationStatuses($currentReservationStatus);

        $reservation->setBorrower($borrower);
        $reservation->setCurrentStatus($currentReservationStatus);

        return $reservation;
    }

    private function createBorrower(Reservation $reservation, bool $youngFarmer, bool $creationInProgress, bool $subsidiary): Borrower
    {
        $program = $reservation->getProgram();

        return (new Borrower($reservation, 'Borrower Company', 'B'))
            ->setBeneficiaryName('Borrower Name')
            ->setBorrowerType($this->createOrGetProgramChoiceOption($program, 'field-borrower_type', 'Installé'))
            ->setYoungFarmer($youngFarmer)
            ->setCreationInProgress($creationInProgress)
            ->setSubsidiary($subsidiary)
            ->setAddressStreet($this->faker->streetAddress)
            ->setAddressCity($this->faker->city)
            ->setAddressPostCode($this->faker->postcode)
            ->setAddressDepartment('department')
            ->setAddressCountry($this->createOrGetProgramChoiceOption($program, 'field-activity_country', 'FR'))
            ->setActivityStartDate(new DateTimeImmutable())
            ->setSiret('11111111111111')
            ->setTaxNumber('12 23 45 678 987')
            ->setLegalForm($this->createOrGetProgramChoiceOption($program, 'field-legal_form', 'SAS'))
            ->setCompanyNafCode($this->createOrGetProgramChoiceOption($program, 'field-company_naf_code', '0001A'))
            ->setEmployeesNumber(42)
            ->setExploitationSize($this->createOrGetProgramChoiceOption($program, 'field-exploitation_size', '42'))
            ->setTurnover(new NullableMoney('EUR', '100'))
            ->setTotalAssets(new NullableMoney('EUR', '2048'))
        ;
    }

    private function createProject(Reservation $reservation): Project
    {
        $program      = $reservation->getProgram();
        $fundingMoney = new Money('EUR', (string) $this->faker->randomNumber());

        return (new Project($reservation, $fundingMoney))
            ->setInvestmentThematic($this->createOrGetProgramChoiceOption($program, 'field-investment_thematic', 'Project : ' . $this->faker->sentence))
            ->setInvestmentType($this->createOrGetProgramChoiceOption($program, 'field-investment_type', 'Type : ' . $this->faker->sentence))
            ->setAidIntensity($this->createOrGetProgramChoiceOption($program, 'field-aid_intensity', $this->faker->unique()->numberBetween(0, 100) . '%'))
            ->setAdditionalGuaranty($this->createOrGetProgramChoiceOption($program, 'field-additional_guaranty', $this->faker->sentence(3)))
            ->setAgriculturalBranch($this->createOrGetProgramChoiceOption($program, 'field-agricultural_branch', 'Branch N: ' . $this->faker->sentence))
            ->setAddressStreet($this->faker->streetAddress)
            ->setAddressCity($this->faker->city)
            ->setAddressPostCode($this->faker->postcode)
            ->setAddressDepartment('department')
            ->setAddressCountry($this->createOrGetProgramChoiceOption($program, 'field-investment_country', 'FR'))
            ->setContribution(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setEligibleFeiCredit(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setTotalFeiCredit(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setTangibleFeiCredit(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setIntangibleFeiCredit(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setCreditExcludingFei(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setGrant(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setLandValue(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
        ;
    }

    private function createFinancingObject(Reservation $reservation, bool $mainLoan): FinancingObject
    {
        $program   = $reservation->getProgram();
        $loanMoney = new Money('EUR', '200');

        return (new FinancingObject($reservation, $loanMoney, $mainLoan))
            ->setSupportingGenerationsRenewal(true)
            ->setFinancingObjectType($this->createOrGetProgramChoiceOption($program, 'field-financing_object_type', $this->faker->text(255)))
            ->setLoanNafCode($this->createOrGetProgramChoiceOption($program, 'field-loan_naf_code', '0001A'))
            ->setBfrValue(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setLoanType($this->createOrGetProgramChoiceOption($program, 'field-loan_type', 'short_term'))
            ->setLoanDuration($this->faker->numberBetween(0, 12))
            ->setLoanDeferral($this->faker->numberBetween(0, 12))
            ->setLoanPeriodicity($this->createOrGetProgramChoiceOption($program, 'field-loan_periodicity', 'monthly'))
            ->setInvestmentLocation($this->createOrGetProgramChoiceOption($program, 'field-loan_type', $this->faker->countryCode))
        ;
    }

    private function createReservationStatuses(ReservationStatus $currentReservationStatus): void
    {
        if (ReservationStatus::STATUS_DRAFT === $currentReservationStatus->getStatus()) {
            $this->entityManager->persist($currentReservationStatus);

            return;
        }

        foreach (ReservationStatus::ALLOWED_STATUS as $allowedStatus => $allowedStatuses) {
            $previousReservationStatus = new ReservationStatus($currentReservationStatus->getReservation(), $allowedStatus, $currentReservationStatus->getAddedBy());

            if (ReservationStatus::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION === $allowedStatus) {
                $previousReservationStatus->setComment($this->faker->text(200));
            }

            $this->entityManager->persist($previousReservationStatus);

            if (in_array($currentReservationStatus->getStatus(), $allowedStatuses, true)) {
                break;
            }
        }

        if (ReservationStatus::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION === $currentReservationStatus->getStatus()) {
            $currentReservationStatus->setComment($this->faker->text(200));
        }

        $this->entityManager->persist($currentReservationStatus);
    }

    private function createOrGetProgramChoiceOption(Program $program, string $fieldReference, ?string $description = null): ProgramChoiceOption
    {
        /** @var Field $field */
        $field = $this->getReference($fieldReference);

        if (empty($description)) {
            $programChoiceOptions = $this->programChoiceOptionRepository->findBy([
                'program' => $program,
                'field'   => $field,
            ]);

            return $programChoiceOptions[array_rand($programChoiceOptions)];
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
            $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, $programChoiceOption, null, true);
            $this->entityManager->persist($programEligibilityConfiguration);
        }

        return $programChoiceOption;
    }

    private function createCompanyGroup(Program $program, Staff $addedBy): void
    {
        $company = $addedBy->getCompany();

        foreach ($company->getStaff() as $staff) {
            if (false === $staff->isManager()) {
                continue;
            }

            $staff->addCompanyGroupTag($program->getCompanyGroupTag());
            $this->entityManager->persist($staff);
        }
    }
}
