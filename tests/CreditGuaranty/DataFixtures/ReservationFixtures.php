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
use Unilend\CreditGuaranty\Entity\Constant\FieldAlias;
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
use Unilend\CreditGuaranty\Repository\FieldRepository;
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
    private FieldRepository $fieldRepository;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private ProgramEligibilityRepository $programEligibilityRepository;

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
            'releasedOnInvoice'  => true,
            'hasProject'         => true,
            'addedBy'            => $addedBy,
            'currentStatus'      => ReservationStatus::STATUS_SENT,
        ];
        yield self::RESERVATION_SENT_2 => [
            'program'            => $program,
            'borrower'           => ['youngFarmer' => false, 'creationInProgress' => false, 'subsidiary' => true],
            'financingObjectsNb' => 2,
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
            for ($i = 0; $i < $reservationData['financingObjectsNb']; ++$i) {
                $releasedOnInvoice = $reservationData['releasedOnInvoice'] ?? false;

                $financingObject = $this->createFinancingObject($reservation, $releasedOnInvoice);
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
        $reservation->setBorrower($borrower);

        $currentReservationStatus = new ReservationStatus($reservation, $reservationData['currentStatus'], $reservationData['addedBy']);
        $this->createReservationStatuses($currentReservationStatus);
        $reservation->setCurrentStatus($currentReservationStatus);

        return $reservation;
    }

    private function createBorrower(Reservation $reservation, bool $youngFarmer, bool $creationInProgress, bool $subsidiary): Borrower
    {
        /** @var Field $borrowerTypeField */
        $borrowerTypeField = $this->getReference('field-borrower_type');
        /** @var Field $legalFormField */
        $legalFormField = $this->getReference('field-legal_form');
        /** @var Field $companyNafCodeField */
        $companyNafCodeField = $this->getReference('field-company_naf_code');
        /** @var Field $exploitationSizeField */
        $exploitationSizeField = $this->getReference('field-exploitation_size');
        /** @var Field $activityCountryField */
        $activityCountryField = $this->getReference('field-activity_country');

        $program      = $reservation->getProgram();
        $borrowerType = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $borrowerTypeField,
            'description' => 'Installé',
        ]);
        $legalForm = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $legalFormField,
            'description' => 'SAS',
        ]);
        $companyNafCode = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $companyNafCodeField,
            'description' => '0001A',
        ]);
        $exploitationSize = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $exploitationSizeField,
            'description' => '42',
        ]);
        $activityCountry = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $activityCountryField,
            'description' => 'FR',
        ]);

        return (new Borrower($reservation, 'Borrower Company', 'B'))
            ->setBeneficiaryName('Borrower Name')
            ->setBorrowerType($borrowerType)
            ->setYoungFarmer($youngFarmer)
            ->setCreationInProgress($creationInProgress)
            ->setSubsidiary($subsidiary)
            ->setAddressStreet($this->faker->streetAddress)
            ->setAddressCity($this->faker->city)
            ->setAddressPostCode($this->faker->postcode)
            ->setAddressDepartment('department')
            ->setAddressCountry($activityCountry)
            ->setActivityStartDate(new DateTimeImmutable())
            ->setSiret('11111111111111')
            ->setTaxNumber('12 23 45 678 987')
            ->setLegalForm($legalForm)
            ->setCompanyNafCode($companyNafCode)
            ->setEmployeesNumber(42)
            ->setExploitationSize($exploitationSize)
            ->setTurnover(new NullableMoney('EUR', '100'))
            ->setTotalAssets(new NullableMoney('EUR', '2048'))
        ;
    }

    private function createProject(Reservation $reservation): Project
    {
        $program            = $reservation->getProgram();
        $investmentThematic = $this->createProgramChoiceOption($program, FieldAlias::INVESTMENT_THEMATIC, 'Project : ' . $this->faker->sentence);
        $investmentType     = $this->createProgramChoiceOption($program, FieldAlias::INVESTMENT_TYPE, 'Type : ' . $this->faker->sentence);
        $aidIntensity       = $this->createProgramChoiceOption($program, FieldAlias::AID_INTENSITY, $this->faker->unique()->numberBetween(0, 100) . '%');
        $additionalGuaranty = $this->createProgramChoiceOption($program, FieldAlias::ADDITIONAL_GUARANTY, $this->faker->sentence(3));
        $agriculturalBranch = $this->createProgramChoiceOption($program, FieldAlias::AGRICULTURAL_BRANCH, 'Branch N: ' . $this->faker->sentence);
        $fundingMoney       = new Money('EUR', (string) $this->faker->randomNumber());

        /** @var Field $investmentCountryField */
        $investmentCountryField = $this->getReference('field-investment_country');
        $activityCountry        = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $investmentCountryField,
            'description' => 'FR',
        ]);

        return (new Project($reservation, $fundingMoney))
            ->setInvestmentThematic($investmentThematic)
            ->setInvestmentType($investmentType)
            ->setAidIntensity($aidIntensity)
            ->setAdditionalGuaranty($additionalGuaranty)
            ->setAgriculturalBranch($agriculturalBranch)
            ->setAddressStreet($this->faker->streetAddress)
            ->setAddressCity($this->faker->city)
            ->setAddressPostCode($this->faker->postcode)
            ->setAddressDepartment('department')
            ->setAddressCountry($activityCountry)
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

    private function createFinancingObject(Reservation $reservation, bool $releasedOnInvoice = false): FinancingObject
    {
        $program = $reservation->getProgram();

        /** @var Field $financingObjectField */
        $financingObjectField = $this->getReference('field-financing_object');
        /** @var Field $loanTypeField */
        $loanTypeField = $this->getReference('field-loan_type');

        $loanType = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $reservation->getProgram(),
            'field'       => $loanTypeField,
            'description' => 'short_term',
        ]);

        $financingObject = $this->createProgramChoiceOption($program, $financingObjectField->getFieldAlias(), $this->faker->text(255));
        $loanMoney       = new Money('EUR', '200');

        return new FinancingObject(
            $reservation,
            $financingObject,
            $loanType,
            12,
            $loanMoney,
            $releasedOnInvoice
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

        $programChoiceOption = new ProgramChoiceOption($program, $description, $field);
        $this->entityManager->persist($programChoiceOption);

        $programEligibility = $this->programEligibilityRepository->findOneBy([
            'program' => $program,
            'field'   => $field,
        ]);

        if (null === $programEligibility) {
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
