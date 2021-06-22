<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

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
use Unilend\Core\Entity\NafNace;
use Unilend\Core\Repository\NafNaceRepository;
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
    private NafNaceRepository             $nafNaceRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        FieldRepository $fieldRepository,
        ProgramChoiceOptionRepository $programChoiceOptionRepository,
        ProgramEligibilityRepository $programEligibilityRepository,
        NafNaceRepository $nafNaceRepository
    ) {
        parent::__construct($tokenStorage);
        $this->fieldRepository               = $fieldRepository;
        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
        $this->programEligibilityRepository  = $programEligibilityRepository;
        $this->nafNaceRepository             = $nafNaceRepository;
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
            'borrower'      => $this->createBorrower($program),
            'addedBy'       => $this->getReference(ParticipationFixtures::PARTICIPANT_SAVO)->getParticipant()->getStaff()->current(),
            'currentStatus' => ReservationStatus::STATUS_DRAFT,
        ];
        yield self::RESERVATION_SENT => [
            'program'            => $program,
            'borrower'           => $this->createBorrower($program),
            'addedBy'            => $this->getReference(ParticipationFixtures::PARTICIPANT_SAVO)->getParticipant()->getStaff()->current(),
            'financingObjectsNb' => $this->faker->numberBetween(1, 3),
            'project'            => $this->createProject($program),
            'currentStatus'      => ReservationStatus::STATUS_SENT,
        ];
        yield self::RESERVATION_WAITING_FOR_FEI => [
            'program'            => $program,
            'borrower'           => $this->createBorrower($program),
            'addedBy'            => $this->getReference(ParticipationFixtures::PARTICIPANT_SAVO)->getParticipant()->getStaff()->current(),
            'financingObjectsNb' => $this->faker->numberBetween(1, 3),
            'project'            => $this->createProject($program),
            'currentStatus'      => ReservationStatus::STATUS_WAITING_FOR_FEI,
        ];
        yield self::RESERVATION_REQUEST_FOR_ADDITIONAL_INFORMATION => [
            'program'            => $program,
            'borrower'           => $this->createBorrower($program),
            'addedBy'            => $this->getReference(ParticipationFixtures::PARTICIPANT_TOUL)->getParticipant()->getStaff()->current(),
            'financingObjectsNb' => $this->faker->numberBetween(1, 3),
            'project'            => $this->createProject($program),
            'currentStatus'      => ReservationStatus::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION,
        ];
        yield self::RESERVATION_ACCEPTED_BY_MANAGING_COMPANY => [
            'program'            => $program,
            'borrower'           => $this->createBorrower($program),
            'addedBy'            => $this->getReference(ParticipationFixtures::PARTICIPANT_TOUL)->getParticipant()->getStaff()->current(),
            'financingObjectsNb' => $this->faker->numberBetween(1, 3),
            'project'            => $this->createProject($program),
            'currentStatus'      => ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY,
        ];
        yield self::RESERVATION_CONTRACT_FORMALIZED => [
            'program'            => $program,
            'borrower'           => $this->createBorrower($program),
            'addedBy'            => $this->getReference(ParticipationFixtures::PARTICIPANT_TOUL)->getParticipant()->getStaff()->current(),
            'financingObjectsNb' => $this->faker->numberBetween(1, 3),
            'project'            => $this->createProject($program),
            'currentStatus'      => ReservationStatus::STATUS_CONTRACT_FORMALIZED,
        ];
        yield self::RESERVATION_ARCHIVED => [
            'program'            => $program,
            'borrower'           => $this->createBorrower($program),
            'addedBy'            => $this->getReference(ParticipationFixtures::PARTICIPANT_SAVO)->getParticipant()->getStaff()->current(),
            'financingObjectsNb' => $this->faker->numberBetween(1, 3),
            'project'            => $this->createProject($program),
            'currentStatus'      => ReservationStatus::STATUS_ARCHIVED,
        ];
        yield self::RESERVATION_REFUSED_BY_MANAGING_COMPANY => [
            'program'            => $program,
            'borrower'           => $this->createBorrower($program),
            'addedBy'            => $this->getReference(ParticipationFixtures::PARTICIPANT_SAVO)->getParticipant()->getStaff()->current(),
            'financingObjectsNb' => $this->faker->numberBetween(1, 3),
            'project'            => $this->createProject($program),
            'currentStatus'      => ReservationStatus::STATUS_REFUSED_BY_MANAGING_COMPANY,
        ];
    }

    private function buildReservation(array $reservationData): Reservation
    {
        $reservation = new Reservation($reservationData['program'], $reservationData['borrower'], $reservationData['addedBy']);
        $totalAmount = 0;

        if (false === empty($reservationData['financingObjectsNb'])) {
            for ($i = 0; $i < $reservationData['financingObjectsNb']; ++$i) {
                $financingObject = $this->createFinancingObject($reservation);
                $this->entityManager->persist($financingObject);
                $totalAmount += (float) $financingObject->getLoanMoney()->getAmount();
            }
        }

        if (false === empty($reservationData['project'])) {
            /** @var Project $project */
            $project = $reservationData['project'];
            $project->setFundingMoney(new Money('EUR', (string) $totalAmount));
            $this->entityManager->persist($reservationData['project']);
            $reservation->setProject($reservationData['project']);
        }

        $currentReservationStatus = new ReservationStatus($reservation, $reservationData['currentStatus'], $reservationData['addedBy']);
        $this->createReservationStatuses($currentReservationStatus);
        $reservation->setCurrentStatus($currentReservationStatus);

        return $reservation;
    }

    private function createBorrower(Program $program): Borrower
    {
        /** @var Field $borrowerTypeField */
        $borrowerTypeField = $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::BORROWER_TYPE]);
        /** @var Field $legalFormField */
        $legalFormField = $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::LEGAL_FORM]);
        /** @var Field $activityCountryField */
        $activityCountryField = $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::ACTIVITY_COUNTRY]);
        /** @var Field $companyNafCodeField */
        $companyNafCodeField = $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::COMPANY_NAF_CODE]);
        /** @var NafNace $nafNace */
        $nafNace = $this->nafNaceRepository->find($this->faker->numberBetween(1, 700));

        $nafCode = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $companyNafCodeField,
            'description' => $nafNace->getNafCode(),
        ]) ?? $this->createProgramChoiceOption($program, FieldAlias::COMPANY_NAF_CODE, $nafNace->getNafCode());

        $borrowerTypes = $this->programChoiceOptionRepository->findBy([
            'program' => $program,
            'field'   => $borrowerTypeField,
        ]);
        $legalForms = $this->programChoiceOptionRepository->findBy([
            'program' => $program,
            'field'   => $legalFormField,
        ]);
        $activityCountries = $this->programChoiceOptionRepository->findBy([
            'program' => $program,
            'field'   => $activityCountryField,
        ]);

        $grades = CARatingType::CA_INTERNAL_RETAIL_RATING === $program->getRatingType() ? CAInternalRetailRating::getConstList() : CAInternalRating::getConstList();

        return (new Borrower($this->faker->company, $grades[array_rand($grades)]))
            ->setBeneficiaryName($this->faker->name)
            ->setBorrowerType($borrowerTypes[array_rand($borrowerTypes)])
            ->setYoungFarmer(false)
            ->setCreationInProgress(false)
            ->setSubsidiary(false)
            ->setSiret((string) $this->faker->numberBetween(10000, 99999))
            ->setTaxNumber('12 23 45 678 987')
            ->setLegalForm($legalForms[array_rand($legalForms)])
            ->setCompanyNafCode($nafCode)
            ->setAddressStreet($this->faker->streetAddress)
            ->setAddressCity($this->faker->city)
            ->setAddressPostCode($this->faker->postcode)
            ->setAddressDepartment('department')
            ->setAddressCountry($activityCountries[array_rand($activityCountries)])
            ->setEmployeesNumber($this->faker->randomDigit)
            ->setTurnover(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setTotalAssets(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
        ;
    }

    private function createProject(Program $program): Project
    {
        $investmentThematic = $this->createProgramChoiceOption($program, FieldAlias::INVESTMENT_THEMATIC, 'Project : ' . $this->faker->sentence);
        $investmentType     = $this->createProgramChoiceOption($program, FieldAlias::INVESTMENT_TYPE, 'Type : ' . $this->faker->sentence);
        $aidIntensity       = $this->createProgramChoiceOption($program, FieldAlias::AID_INTENSITY, $this->faker->numberBetween(0, 100) . '%');
        $additionalGuaranty = $this->createProgramChoiceOption($program, FieldAlias::ADDITIONAL_GUARANTY, $this->faker->sentence(3));
        $agriculturalBranch = $this->createProgramChoiceOption($program, FieldAlias::AGRICULTURAL_BRANCH, 'Branch N: ' . $this->faker->sentence);
        $fundingMoney       = new Money('EUR', (string) $this->faker->randomNumber());

        return new Project($investmentThematic, $investmentType, $aidIntensity, $additionalGuaranty, $agriculturalBranch, $fundingMoney);
    }

    private function createFinancingObject(Reservation $reservation): FinancingObject
    {
        $program = $reservation->getProgram();

        /** @var Field $financingObjectField */
        $financingObjectField = $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::FINANCING_OBJECT]);
        /** @var Field $loanTypeField */
        $loanTypeField = $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::LOAN_TYPE]);

        $financingObjects = $this->programChoiceOptionRepository->findBy([
            'program' => $program,
            'field'   => $financingObjectField,
        ]);
        $loanTypes = $this->programChoiceOptionRepository->findBy([
            'program' => $program,
            'field'   => $loanTypeField,
        ]);

        if (empty($financingObjects)) {
            $financingObject = $this->createProgramChoiceOption($program, FieldAlias::FINANCING_OBJECT, $this->faker->text(255));
        } else {
            $financingObject = $financingObjects[array_rand($financingObjects)];
        }

        if (empty($loanTypes)) {
            $loanType = $this->createProgramChoiceOption($program, FieldAlias::LOAN_TYPE, $this->faker->text(255));
        } else {
            $loanType = $loanTypes[array_rand($loanTypes)];
        }

        $loanMoney = new Money('EUR', (string) $this->faker->randomNumber());

        return new FinancingObject(
            $reservation,
            $financingObject,
            $loanType,
            $this->faker->randomNumber(2),
            $loanMoney,
            $this->faker->boolean
        );
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

    private function createProgramChoiceOption(Program $program, string $fieldAlias, string $description): ProgramChoiceOption
    {
        /** @var Field $field */
        $field = $this->fieldRepository->findOneBy(['fieldAlias' => $fieldAlias]);

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
