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
use Unilend\CreditGuaranty\Entity\Embeddable\Address;
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
            'borrower'      => $this->createBorrower($program, $this->createAddress(), true, true, true),
            'addedBy'       => $addedBy,
            'currentStatus' => ReservationStatus::STATUS_DRAFT,
        ];
        yield self::RESERVATION_DRAFT_2 => [
            'program'       => $program,
            'borrower'      => $this->createBorrower($program, $this->createAddress(), false, true),
            'addedBy'       => $addedBy,
            'currentStatus' => ReservationStatus::STATUS_DRAFT,
        ];
        yield self::RESERVATION_SENT_1 => [
            'program'            => $program,
            'borrower'           => $this->createBorrower($program, $this->createAddress(), true, true, true),
            'financingObjectsNb' => 2,
            'releasedOnInvoice'  => true,
            'project'            => $this->createProject($program),
            'addedBy'            => $addedBy,
            'currentStatus'      => ReservationStatus::STATUS_SENT,
        ];
        yield self::RESERVATION_SENT_2 => [
            'program'            => $program,
            'borrower'           => $this->createBorrower($program, $this->createAddress(), false, false, true),
            'financingObjectsNb' => 2,
            'project'            => $this->createProject($program),
            'addedBy'            => $addedBy,
            'currentStatus'      => ReservationStatus::STATUS_SENT,
        ];
    }

    private function buildReservation(array $reservationData): Reservation
    {
        $reservation = new Reservation($reservationData['program'], $reservationData['borrower'], $reservationData['addedBy']);
        $totalAmount = 0;

        if (array_key_exists('financingObjectsNb', $reservationData)) {
            for ($i = 0; $i < $reservationData['financingObjectsNb']; ++$i) {
                $releasedOnInvoice = $reservationData['releasedOnInvoice'] ?? false;

                $financingObject = $this->createFinancingObject($reservation, $releasedOnInvoice);
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

    private function createAddress(): Address
    {
        return (new Address())
            ->setRoadNumber((string) $this->faker->randomDigit)
            ->setRoadName($this->faker->streetAddress)
            ->setCity($this->faker->city)
            ->setCity($this->faker->city)
            ->setPostCode($this->faker->countryCode)
            ->setCountry('FR') // => from predefinedItems of activity_country fieldAlias
        ;
    }

    private function createBorrower(
        Program $program,
        Address $address,
        bool $youngFarmer = false,
        bool $creationInProgress = false,
        bool $subsidiary = false
    ): Borrower {
        /** @var Field $borrowerTypeField */
        $borrowerTypeField = $this->getReference('field-borrower_type');
        /** @var Field $legalFormField */
        $legalFormField = $this->getReference('field-legal_form');
        /** @var Field $companyNafCodeField */
        $companyNafCodeField = $this->getReference('field-company_naf_code');
        /** @var Field $exploitationSizeField */
        $exploitationSizeField = $this->getReference('field-exploitation_size');

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

        return (new Borrower('Borrower Company', 'B'))
            ->setBeneficiaryName('Borrower Name')
            ->setBorrowerType($borrowerType)
            ->setYoungFarmer($youngFarmer)
            ->setCreationInProgress($creationInProgress)
            ->setSubsidiary($subsidiary)
            ->setAddress($address)
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

    private function createProject(Program $program): Project
    {
        /** @var Field $projectNafCodeField */
        $projectNafCodeField = $this->getReference('field-project_naf_code');

        $projectNafCode = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $projectNafCodeField,
            'description' => '0001A',
        ]);

        $fundingMoney       = new Money('EUR', (string) $this->faker->randomNumber());
        $investmentThematic = $this->createProgramChoiceOption($program, FieldAlias::INVESTMENT_THEMATIC, 'Project ' . $this->faker->sentence);

        return new Project($fundingMoney, $investmentThematic, $projectNafCode);
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
