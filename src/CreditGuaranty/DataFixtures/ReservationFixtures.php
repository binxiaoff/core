<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\Core\DataFixtures\NafNaceFixtures;
use Unilend\Core\Entity\Constant\CAInternalRating;
use Unilend\Core\Entity\Constant\CAInternalRetailRating;
use Unilend\Core\Entity\Constant\CARatingType;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\CreditGuaranty\Entity\Borrower;
use Unilend\CreditGuaranty\Entity\Constant\FieldAlias;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\FinancingObject;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use Unilend\CreditGuaranty\Entity\Project;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Entity\ReservationStatus;
use Unilend\CreditGuaranty\Repository\FieldRepository;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityRepository;

class ReservationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public const RESERVATION_DRAFT                              = 'reservation_draft';
    public const RESERVATION_SENT                               = 'reservation_sent';
    public const RESERVATION_WAITING_FOR_FEI                    = 'reservation_waiting_for_fei';
    public const RESERVATION_REQUEST_FOR_ADDITIONAL_INFORMATION = 'reservation_request_for_additional_information';
    public const RESERVATION_ACCEPTED_BY_MANAGING_COMPANY       = 'reservation_accepted_by_managing_company';
    public const RESERVATION_CONTRACT_FORMALIZED                = 'reservation_contract_formalized';
    public const RESERVATION_ARCHIVED                           = 'reservation_archived';
    public const RESERVATION_REFUSED_BY_MANAGING_COMPANY        = 'reservation_refused_by_managing_company';

    private ObjectManager                 $entityManager;
    private FieldRepository               $fieldRepository;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private ProgramEligibilityRepository  $programEligibilityRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        FieldRepository $fieldRepository,
        ProgramChoiceOptionRepository $programChoiceOptionRepository,
        ProgramEligibilityRepository $programEligibilityRepository
    ) {
        parent::__construct($tokenStorage);
        $this->fieldRepository               = $fieldRepository;
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
            ProgramChoiceOptionFixtures::class,
            ParticipationFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $this->entityManager = $manager;

        foreach ($this->loadData() as $reference => $reservationData) {
            $reservation = $this->buildReservation($reservationData);
            $manager->persist($reservation);
            $this->addReference($reference, $reservation);
        }

        $manager->flush();
    }

    private function loadData(): iterable
    {
        /** @var Program $program */
        $program = $this->getReference('commercialized_program');

        yield self::RESERVATION_DRAFT => [
            'program'       => $program,
            'hasProject'    => false,
            'addedBy'       => $this->getReference(ParticipationFixtures::PARTICIPANT_SAVO)->getParticipant()->getStaff()->current(),
            'currentStatus' => ReservationStatus::STATUS_DRAFT,
        ];
        yield self::RESERVATION_SENT => [
            'program'            => $program,
            'hasProject'         => true,
            'addedBy'            => $this->getReference(ParticipationFixtures::PARTICIPANT_SAVO)->getParticipant()->getStaff()->current(),
            'financingObjectsNb' => $this->faker->numberBetween(1, 3),
            'currentStatus'      => ReservationStatus::STATUS_SENT,
        ];
        yield self::RESERVATION_WAITING_FOR_FEI => [
            'program'            => $program,
            'hasProject'         => true,
            'addedBy'            => $this->getReference(ParticipationFixtures::PARTICIPANT_SAVO)->getParticipant()->getStaff()->current(),
            'financingObjectsNb' => $this->faker->numberBetween(1, 3),
            'currentStatus'      => ReservationStatus::STATUS_WAITING_FOR_FEI,
        ];
        yield self::RESERVATION_REQUEST_FOR_ADDITIONAL_INFORMATION => [
            'program'            => $program,
            'hasProject'         => true,
            'addedBy'            => $this->getReference(ParticipationFixtures::PARTICIPANT_TOUL)->getParticipant()->getStaff()->current(),
            'financingObjectsNb' => $this->faker->numberBetween(1, 3),
            'currentStatus'      => ReservationStatus::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION,
        ];
        yield self::RESERVATION_ACCEPTED_BY_MANAGING_COMPANY => [
            'program'            => $program,
            'hasProject'         => true,
            'addedBy'            => $this->getReference(ParticipationFixtures::PARTICIPANT_TOUL)->getParticipant()->getStaff()->current(),
            'financingObjectsNb' => $this->faker->numberBetween(1, 3),
            'currentStatus'      => ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY,
        ];
        yield self::RESERVATION_CONTRACT_FORMALIZED => [
            'program'            => $program,
            'hasProject'         => true,
            'addedBy'            => $this->getReference(ParticipationFixtures::PARTICIPANT_TOUL)->getParticipant()->getStaff()->current(),
            'financingObjectsNb' => $this->faker->numberBetween(1, 3),
            'currentStatus'      => ReservationStatus::STATUS_CONTRACT_FORMALIZED,
        ];
        yield self::RESERVATION_ARCHIVED => [
            'program'            => $program,
            'hasProject'         => true,
            'addedBy'            => $this->getReference(ParticipationFixtures::PARTICIPANT_SAVO)->getParticipant()->getStaff()->current(),
            'financingObjectsNb' => $this->faker->numberBetween(1, 3),
            'currentStatus'      => ReservationStatus::STATUS_ARCHIVED,
        ];
        yield self::RESERVATION_REFUSED_BY_MANAGING_COMPANY => [
            'program'            => $program,
            'hasProject'         => true,
            'addedBy'            => $this->getReference(ParticipationFixtures::PARTICIPANT_SAVO)->getParticipant()->getStaff()->current(),
            'financingObjectsNb' => $this->faker->numberBetween(1, 3),
            'currentStatus'      => ReservationStatus::STATUS_REFUSED_BY_MANAGING_COMPANY,
        ];
    }

    private function buildReservation(array $reservationData): Reservation
    {
        $reservation = new Reservation($reservationData['program'], $reservationData['addedBy']);
        $totalAmount = 0;

        if (false === empty($reservationData['financingObjectsNb'])) {
            for ($i = 0; $i < $reservationData['financingObjectsNb']; ++$i) {
                $financingObject = $this->createFinancingObject($reservation);
                $this->entityManager->persist($financingObject);
                $totalAmount += (float) $financingObject->getLoanMoney()->getAmount();
            }
        }

        if ($reservationData['hasProject']) {
            $project = $this->createProject($reservation);
            $project->setFundingMoney(new Money('EUR', (string) $totalAmount));
            $reservation->setProject($project);
        }

        $borrower                 = $this->createBorrower($reservation);
        $currentReservationStatus = new ReservationStatus($reservation, $reservationData['currentStatus'], $reservationData['addedBy']);
        $this->createReservationStatuses($currentReservationStatus);

        $reservation->setBorrower($borrower);
        $reservation->setCurrentStatus($currentReservationStatus);

        return $reservation;
    }

    private function createBorrower(Reservation $reservation): Borrower
    {
        $program = $reservation->getProgram();
        $grades  = CARatingType::CA_INTERNAL_RETAIL_RATING === $program->getRatingType() ? CAInternalRetailRating::getConstList() : CAInternalRating::getConstList();

        return (new Borrower($reservation, $this->faker->company, $grades[\array_rand($grades)]))
            ->setBeneficiaryName($this->faker->name)
            ->setBorrowerType($this->findProgramChoiceOption($program, FieldAlias::BORROWER_TYPE))
            ->setYoungFarmer($this->faker->boolean)
            ->setCreationInProgress($this->faker->boolean)
            ->setSubsidiary($this->faker->boolean)
            ->setActivityStartDate(new DateTimeImmutable())
            ->setSiret((string) $this->faker->numberBetween(10000, 99999))
            ->setTaxNumber('12 23 45 678 987')
            ->setLegalForm($this->findProgramChoiceOption($program, FieldAlias::LEGAL_FORM))
            ->setCompanyNafCode($this->findProgramChoiceOption($program, FieldAlias::COMPANY_NAF_CODE))
            ->setAddressStreet($this->faker->streetAddress)
            ->setAddressCity($this->faker->city)
            ->setAddressPostCode($this->faker->postcode)
            ->setAddressDepartment('department')
            ->setAddressCountry($this->findProgramChoiceOption($program, FieldAlias::ACTIVITY_COUNTRY))
            ->setEmployeesNumber($this->faker->randomDigit)
            ->setExploitationSize($this->findProgramChoiceOption($program, FieldAlias::EXPLOITATION_SIZE))
            ->setTurnover(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setTotalAssets(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
        ;
    }

    private function createProject(Reservation $reservation): Project
    {
        $program      = $reservation->getProgram();
        $fundingMoney = new Money('EUR', (string) $this->faker->randomNumber());

        $project = (new Project($reservation, $fundingMoney))
            ->setInvestmentThematic($this->findProgramChoiceOption($program, FieldAlias::INVESTMENT_THEMATIC, 'Project : ' . $this->faker->sentence))
            ->setInvestmentType($this->findProgramChoiceOption($program, FieldAlias::INVESTMENT_TYPE, 'Type : ' . $this->faker->sentence))
            ->setDetail($this->faker->sentence)
            ->setAidIntensity($this->findProgramChoiceOption($program, FieldAlias::AID_INTENSITY))
            ->setAdditionalGuaranty($this->findProgramChoiceOption($program, FieldAlias::ADDITIONAL_GUARANTY, $this->faker->sentence(3)))
            ->setAgriculturalBranch($this->findProgramChoiceOption($program, FieldAlias::AGRICULTURAL_BRANCH, 'Branch N: ' . $this->faker->sentence))
            ->setAddressStreet($this->faker->streetAddress)
            ->setAddressCity($this->faker->city)
            ->setAddressPostCode($this->faker->postcode)
            ->setAddressDepartment('department')
            ->setAddressCountry($this->findProgramChoiceOption($program, FieldAlias::INVESTMENT_COUNTRY))
            ->setContribution(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setEligibleFeiCredit(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setTotalFeiCredit(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setTangibleFeiCredit(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setIntangibleFeiCredit(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setCreditExcludingFei(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setLandValue(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
        ;

        if (true === $this->faker->boolean) {
            $project->setGrant(new NullableMoney('EUR', (string) $this->faker->randomNumber()));
        }

        return $project;
    }

    private function createFinancingObject(Reservation $reservation): FinancingObject
    {
        $program   = $reservation->getProgram();
        $loanMoney = new Money('EUR', (string) $this->faker->randomNumber());

        return (new FinancingObject($reservation, $loanMoney, $this->faker->boolean))
            ->setSupportingGenerationsRenewal($this->faker->boolean)
            ->setFinancingObjectType($this->findProgramChoiceOption($program, FieldAlias::FINANCING_OBJECT_TYPE, $this->faker->sentence))
            ->setLoanNafCode($this->findProgramChoiceOption($program, FieldAlias::LOAN_NAF_CODE))
            ->setBfrValue(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setLoanType($this->findProgramChoiceOption($program, FieldAlias::LOAN_TYPE))
            ->setLoanDuration($this->faker->numberBetween(0, 12))
            ->setLoanDeferral($this->faker->numberBetween(0, 12))
            ->setLoanPeriodicity($this->findProgramChoiceOption($program, FieldAlias::LOAN_PERIODICITY))
            ->setInvestmentLocation($this->findProgramChoiceOption($program, FieldAlias::INVESTMENT_LOCATION))
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

            if (\in_array($currentReservationStatus->getStatus(), $allowedStatuses, true)) {
                break;
            }
        }

        if (ReservationStatus::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION === $currentReservationStatus->getStatus()) {
            $currentReservationStatus->setComment($this->faker->text(200));
        }

        $this->entityManager->persist($currentReservationStatus);
    }

    private function findProgramChoiceOption(Program $program, string $fieldAlias, ?string $description = null): ProgramChoiceOption
    {
        /** @var Field $field */
        $field = $this->fieldRepository->findOneBy(['fieldAlias' => $fieldAlias]);

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
            $programEligibilityConfiguration = new ProgramEligibilityConfiguration($programEligibility, $programChoiceOption, null, true);
            $this->entityManager->persist($programEligibilityConfiguration);
        }

        return $programChoiceOption;
    }
}
