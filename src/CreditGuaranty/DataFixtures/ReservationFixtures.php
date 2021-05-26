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
use Unilend\Core\Entity\Embeddable\Address;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\NafNace;
use Unilend\CreditGuaranty\Entity\Borrower;
use Unilend\CreditGuaranty\Entity\BorrowerBusinessActivity;
use Unilend\CreditGuaranty\Entity\Constant\FieldAlias;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\FinancingObject;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\Project;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Entity\ReservationStatus;
use Unilend\CreditGuaranty\Repository\FieldRepository;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;

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

    private ObjectManager $entityManager;
    private FieldRepository $fieldRepository;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        FieldRepository $fieldRepository,
        ProgramChoiceOptionRepository $programChoiceOptionRepository
    ) {
        parent::__construct($tokenStorage);
        $this->fieldRepository               = $fieldRepository;
        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
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
            'program'                  => $program,
            'borrower'                 => $this->createBorrower($program, $this->createAddress()),
            'addedBy'                  => $this->getReference(ParticipationFixtures::PARTICIPANT_SAVO)->getParticipant()->getStaff()->current(),
            'borrowerBusinessActivity' => $this->createBorrowerBusinessActivity($this->createAddress()),
            'currentStatus'            => ReservationStatus::STATUS_DRAFT,
        ];
        yield self::RESERVATION_SENT => [
            'program'                  => $program,
            'borrower'                 => $this->createBorrower($program, $this->createAddress()),
            'addedBy'                  => $this->getReference(ParticipationFixtures::PARTICIPANT_SAVO)->getParticipant()->getStaff()->current(),
            'borrowerBusinessActivity' => $this->createBorrowerBusinessActivity($this->createAddress()),
            'project'                  => $this->createProject($program),
            'currentStatus'            => ReservationStatus::STATUS_SENT,
        ];
        yield self::RESERVATION_WAITING_FOR_FEI => [
            'program'                  => $program,
            'borrower'                 => $this->createBorrower($program, $this->createAddress()),
            'addedBy'                  => $this->getReference(ParticipationFixtures::PARTICIPANT_SAVO)->getParticipant()->getStaff()->current(),
            'borrowerBusinessActivity' => $this->createBorrowerBusinessActivity($this->createAddress()),
            'project'                  => $this->createProject($program),
            'currentStatus'            => ReservationStatus::STATUS_WAITING_FOR_FEI,
        ];
        yield self::RESERVATION_REQUEST_FOR_ADDITIONAL_INFORMATION => [
            'program'                  => $program,
            'borrower'                 => $this->createBorrower($program, $this->createAddress()),
            'addedBy'                  => $this->getReference(ParticipationFixtures::PARTICIPANT_TOUL)->getParticipant()->getStaff()->current(),
            'borrowerBusinessActivity' => $this->createBorrowerBusinessActivity($this->createAddress()),
            'project'                  => $this->createProject($program),
            'currentStatus'            => ReservationStatus::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION,
        ];
        yield self::RESERVATION_ACCEPTED_BY_MANAGING_COMPANY => [
            'program'                  => $program,
            'borrower'                 => $this->createBorrower($program, $this->createAddress()),
            'addedBy'                  => $this->getReference(ParticipationFixtures::PARTICIPANT_TOUL)->getParticipant()->getStaff()->current(),
            'borrowerBusinessActivity' => $this->createBorrowerBusinessActivity($this->createAddress()),
            'project'                  => $this->createProject($program),
            'currentStatus'            => ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY,
        ];
        yield self::RESERVATION_CONTRACT_FORMALIZED => [
            'program'                  => $program,
            'borrower'                 => $this->createBorrower($program, $this->createAddress()),
            'addedBy'                  => $this->getReference(ParticipationFixtures::PARTICIPANT_TOUL)->getParticipant()->getStaff()->current(),
            'borrowerBusinessActivity' => $this->createBorrowerBusinessActivity($this->createAddress()),
            'financingObjectsNb'       => 2,
            'project'                  => $this->createProject($program),
            'currentStatus'            => ReservationStatus::STATUS_CONTRACT_FORMALIZED,
        ];
        yield self::RESERVATION_ARCHIVED => [
            'program'                  => $program,
            'borrower'                 => $this->createBorrower($program, $this->createAddress()),
            'addedBy'                  => $this->getReference(ParticipationFixtures::PARTICIPANT_SAVO)->getParticipant()->getStaff()->current(),
            'borrowerBusinessActivity' => $this->createBorrowerBusinessActivity($this->createAddress()),
            'project'                  => $this->createProject($program),
            'currentStatus'            => ReservationStatus::STATUS_ARCHIVED,
        ];
        yield self::RESERVATION_REFUSED_BY_MANAGING_COMPANY => [
            'program'                  => $program,
            'borrower'                 => $this->createBorrower($program, $this->createAddress()),
            'addedBy'                  => $this->getReference(ParticipationFixtures::PARTICIPANT_SAVO)->getParticipant()->getStaff()->current(),
            'borrowerBusinessActivity' => $this->createBorrowerBusinessActivity($this->createAddress()),
            'financingObjectsNb'       => 1,
            'project'                  => $this->createProject($program),
            'currentStatus'            => ReservationStatus::STATUS_REFUSED_BY_MANAGING_COMPANY,
        ];
    }

    private function buildReservation(array $reservationData): Reservation
    {
        $reservation = new Reservation($reservationData['program'], $reservationData['borrower'], $reservationData['addedBy']);

        if (false === empty($reservationData['borrowerBusinessActivity'])) {
            $this->entityManager->persist($reservationData['borrowerBusinessActivity']);
            $reservation->setBorrowerBusinessActivity($reservationData['borrowerBusinessActivity']);
        }

        $totalAmount = 0;

        if (array_key_exists('financingObjectsNb', $reservationData)) {
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

    private function createAddress(): Address
    {
        return (new Address())
            ->setRoadNumber((string) $this->faker->randomDigit)
            ->setRoadName($this->faker->streetAddress)
            ->setCity($this->faker->city)
            ->setPostCode($this->faker->countryCode)
        ;
    }

    private function createBorrower(Program $program, Address $address): Borrower
    {
        /** @var Field $borrowerTypeField */
        $borrowerTypeField = $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::BORROWER_TYPE]);
        /** @var Field $legalFormField */
        $legalFormField = $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::LEGAL_FORM]);

        $borrowerTypes = $this->programChoiceOptionRepository->findBy([
            'program' => $program,
            'field'   => $borrowerTypeField,
        ]);
        $legalForms = $this->programChoiceOptionRepository->findBy([
            'program' => $program,
            'field'   => $legalFormField,
        ]);

        $grades = CARatingType::CA_INTERNAL_RETAIL_RATING === $program->getRatingType() ? CAInternalRetailRating::getConstList() : CAInternalRating::getConstList();

        return (new Borrower($this->faker->company, $grades[array_rand($grades)]))
            ->setBorrowerType($borrowerTypes[array_rand($borrowerTypes)])
            ->setLegalForm($legalForms[array_rand($legalForms)])
            ->setTaxNumber('12 23 45 678 987')
            ->setBeneficiaryName($this->faker->name)
            ->setAddress($address)
            ->setCreationInProgress(false)
        ;
    }

    private function createBorrowerBusinessActivity(Address $address): BorrowerBusinessActivity
    {
        return (new BorrowerBusinessActivity())
            ->setSiret((string) $this->faker->numberBetween(10000, 99999))
            ->setAddress($address)
            ->setEmployeesNumber($this->faker->randomDigit)
            ->setLastYearTurnover(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setFiveYearsAverageTurnover(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setTotalAssets(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setGrant(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setSubsidiary(false)
        ;
    }

    private function createProject(Program $program): Project
    {
        $nafNaceRepository = $this->entityManager->getRepository(NafNace::class);

        /** @var NafNace $nafNace */
        $nafNace = $nafNaceRepository->find(1);

        /** @var Field $investmentThematicField */
        $investmentThematicField = $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::INVESTMENT_THEMATIC]);

        $fundingMoney       = new Money('EUR', (string) $this->faker->randomNumber());
        $investmentThematic = new ProgramChoiceOption($program, 'Project ' . $this->faker->sentence, $investmentThematicField);

        $this->entityManager->persist($investmentThematic);

        return new Project($fundingMoney, $investmentThematic, $nafNace);
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
            $financingObject = new ProgramChoiceOption($program, $this->faker->text(255), $financingObjectField);
            $this->entityManager->persist($financingObject);
        } else {
            $financingObject = $financingObjects[array_rand($financingObjects)];
        }

        if (empty($loanTypes)) {
            $loanType = new ProgramChoiceOption($program, $this->faker->text(255), $loanTypeField);
            $this->entityManager->persist($loanType);
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
            $this->entityManager->persist($previousReservationStatus);

            if (in_array($currentReservationStatus->getStatus(), $allowedStatuses, true)) {
                break;
            }
        }

        $this->entityManager->persist($currentReservationStatus);
    }
}
