<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
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
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\Project;
use Unilend\CreditGuaranty\Entity\Reservation;
use Unilend\CreditGuaranty\Entity\ReservationStatus;

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

        if (false === empty($reservationData['project'])) {
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
        $fieldRepository               = $this->entityManager->getRepository(Field::class);
        $programChoiceOptionRepository = $this->entityManager->getRepository(ProgramChoiceOption::class);

        /** @var Field $borrowerTypeField */
        $borrowerTypeField = $fieldRepository->findOneBy(['fieldAlias' => FieldAlias::BORROWER_TYPE]);
        /** @var Field $legalFormField */
        $legalFormField = $fieldRepository->findOneBy(['fieldAlias' => FieldAlias::LEGAL_FORM]);

        $borrowerTypes = $programChoiceOptionRepository->findBy([
            'program' => $program,
            'field'   => $borrowerTypeField,
        ]);

        $legalForms = $programChoiceOptionRepository->findBy([
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
        $fieldRepository   = $this->entityManager->getRepository(Field::class);
        $nafNaceRepository = $this->entityManager->getRepository(NafNace::class);

        /** @var Field $investmentThematicField */
        $investmentThematicField = $fieldRepository->findOneBy(['fieldAlias' => FieldAlias::INVESTMENT_THEMATIC]);

        /** @var NafNace $nafNace */
        $nafNace = $nafNaceRepository->find(1);

        $fundingMoney       = new Money('EUR', (string) $this->faker->randomNumber());
        $investmentThematic = new ProgramChoiceOption($program, 'Project ' . $this->faker->sentence, $investmentThematicField);

        $this->entityManager->persist($investmentThematic);

        return new Project($fundingMoney, $investmentThematic, $nafNace);
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
