<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\Core\DataFixtures\NafNaceFixtures;
use Unilend\Core\DataFixtures\StaffFixtures;
use Unilend\Core\Entity\Constant\CAInternalRating;
use Unilend\Core\Entity\Constant\CAInternalRetailRating;
use Unilend\Core\Entity\Constant\CARatingType;
use Unilend\Core\Entity\Embeddable\Address;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Embeddable\NullableMoney;
use Unilend\Core\Entity\NafNace;
use Unilend\Core\Entity\Staff;
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
    public const RESERVATION_REFUSED_BY_MANAGING_COMPANY        = 'reservation_refused_by_managing_company';

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            NafNaceFixtures::class,
            ProgramFixtures::class,
            ProgramChoiceOptionFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->loadData($manager) as $reference => $reservationData) {
            $reservation = $this->buildReservation($manager, $reservationData);
            $manager->persist($reservation);
            $this->addReference($reference, $reservation);
        }

        $manager->flush();
    }

    private function loadData(ObjectManager $manager): iterable
    {
        /** @var Staff $addedBy */
        $addedBy = $this->getReference(StaffFixtures::ADMIN);

        /** @var Program $program */
        $program = $this->getReference('commercialized_program');

        yield self::RESERVATION_DRAFT => [
            'program'                  => $program,
            'borrower'                 => $this->createBorrower($manager, $program, $this->createAddress()),
            'addedBy'                  => $addedBy,
            'borrowerBusinessActivity' => $this->createBorrowerBusinessActivity($this->createAddress()),
            'currentStatus'            => ReservationStatus::STATUS_DRAFT,
        ];
        yield self::RESERVATION_SENT => [
            'program'                  => $program,
            'borrower'                 => $this->createBorrower($manager, $program, $this->createAddress()),
            'addedBy'                  => $addedBy,
            'borrowerBusinessActivity' => $this->createBorrowerBusinessActivity($this->createAddress()),
            'project'                  => $this->createProject($manager, $program),
            'currentStatus'            => ReservationStatus::STATUS_SENT,
        ];
        yield self::RESERVATION_WAITING_FOR_FEI => [
            'program'                  => $program,
            'borrower'                 => $this->createBorrower($manager, $program, $this->createAddress()),
            'addedBy'                  => $addedBy,
            'borrowerBusinessActivity' => $this->createBorrowerBusinessActivity($this->createAddress()),
            'project'                  => $this->createProject($manager, $program),
            'currentStatus'            => ReservationStatus::STATUS_WAITING_FOR_FEI,
        ];
        yield self::RESERVATION_REQUEST_FOR_ADDITIONAL_INFORMATION => [
            'program'                  => $program,
            'borrower'                 => $this->createBorrower($manager, $program, $this->createAddress()),
            'addedBy'                  => $addedBy,
            'borrowerBusinessActivity' => $this->createBorrowerBusinessActivity($this->createAddress()),
            'project'                  => $this->createProject($manager, $program),
            'currentStatus'            => ReservationStatus::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION,
        ];
        yield self::RESERVATION_ACCEPTED_BY_MANAGING_COMPANY => [
            'program'                  => $program,
            'borrower'                 => $this->createBorrower($manager, $program, $this->createAddress()),
            'addedBy'                  => $addedBy,
            'borrowerBusinessActivity' => $this->createBorrowerBusinessActivity($this->createAddress()),
            'project'                  => $this->createProject($manager, $program),
            'currentStatus'            => ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY,
        ];
        yield self::RESERVATION_CONTRACT_FORMALIZED => [
            'program'                  => $program,
            'borrower'                 => $this->createBorrower($manager, $program, $this->createAddress()),
            'addedBy'                  => $addedBy,
            'borrowerBusinessActivity' => $this->createBorrowerBusinessActivity($this->createAddress()),
            'project'                  => $this->createProject($manager, $program),
            'currentStatus'            => ReservationStatus::STATUS_CONTRACT_FORMALIZED,
        ];
        yield self::RESERVATION_REFUSED_BY_MANAGING_COMPANY => [
            'program'                  => $program,
            'borrower'                 => $this->createBorrower($manager, $program, $this->createAddress()),
            'addedBy'                  => $addedBy,
            'borrowerBusinessActivity' => $this->createBorrowerBusinessActivity($this->createAddress()),
            'project'                  => $this->createProject($manager, $program),
            'currentStatus'            => ReservationStatus::STATUS_REFUSED_BY_MANAGING_COMPANY,
        ];
    }

    private function buildReservation(ObjectManager $manager, array $reservationData): Reservation
    {
        $reservation = new Reservation($reservationData['program'], $reservationData['borrower'], $reservationData['addedBy']);

        if (false === empty($reservationData['borrowerBusinessActivity'])) {
            $manager->persist($reservationData['borrowerBusinessActivity']);
            $reservation->setBorrowerBusinessActivity($reservationData['borrowerBusinessActivity']);
        }

        if (false === empty($reservationData['project'])) {
            $manager->persist($reservationData['project']);
            $reservation->setProject($reservationData['project']);
        }

        $reservationStatus = new ReservationStatus($reservation, $reservationData['currentStatus'], $reservationData['addedBy']);
        $manager->persist($reservationStatus);
        $reservation->setCurrentStatus($reservationStatus);

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

    private function createBorrower(ObjectManager $manager, Program $program, Address $address): Borrower
    {
        $fieldRepository               = $manager->getRepository(Field::class);
        $programChoiceOptionRepository = $manager->getRepository(ProgramChoiceOption::class);

        /** @var Field $borrowerTypeField */
        $borrowerTypeField = $fieldRepository->findOneBy(['fieldAlias' => FieldAlias::BORROWER_TYPE]);
        /** @var Field $legalFormField */
        $legalFormField = $fieldRepository->findOneBy(['fieldAlias' => FieldAlias::LEGAL_FORM]);

        /** @var ProgramChoiceOption $borrowerType */
        $borrowerType = $programChoiceOptionRepository->findOneBy([
            'program' => $program,
            'field'   => $borrowerTypeField,
        ]);

        /** @var ProgramChoiceOption $legalForm */
        $legalForm = $programChoiceOptionRepository->findOneBy([
            'program' => $program,
            'field'   => $legalFormField,
        ]);

        $grades = CARatingType::CA_INTERNAL_RETAIL_RATING === $program->getRatingType() ? CAInternalRetailRating::getConstList() : CAInternalRating::getConstList();

        return (new Borrower($this->faker->company, $grades[array_rand($grades)]))
            ->setBorrowerType($borrowerType)
            ->setLegalForm($legalForm)
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

    private function createProject(ObjectManager $manager, Program $program): Project
    {
        $fieldRepository   = $manager->getRepository(Field::class);
        $nafNaceRepository = $manager->getRepository(NafNace::class);

        /** @var Field $investmentThematicField */
        $investmentThematicField = $fieldRepository->findOneBy(['fieldAlias' => FieldAlias::INVESTMENT_THEMATIC]);

        /** @var NafNace $nafNace */
        $nafNace = $nafNaceRepository->find(1);

        $fundingMoney       = new Money('EUR', (string) $this->faker->randomNumber());
        $investmentThematic = new ProgramChoiceOption($program, 'Project ' . $this->faker->sentence, $investmentThematicField);

        $manager->persist($investmentThematic);

        return new Project($fundingMoney, $investmentThematic, $nafNace);
    }
}
