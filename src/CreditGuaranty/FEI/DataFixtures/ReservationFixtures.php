<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\DataFixtures\NafNaceFixtures;
use KLS\Core\Entity\Constant\LegalForm;
use KLS\Core\Entity\Constant\LoanType;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Participation;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\FEI\Entity\ProgramGradeAllocation;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramChoiceOptionRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ReservationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private const RESERVATION_DRAFT                              = 'reservation-draft';
    private const RESERVATION_DRAFT_ELIGIBLE                     = 'reservation-draft-eligible';
    private const RESERVATION_DRAFT_INELIGIBLE                   = 'reservation-draft-ineligible';
    private const RESERVATION_SENT                               = 'reservation-sent';
    private const RESERVATION_WAITING_FOR_FEI                    = 'reservation-waiting_for_fei';
    private const RESERVATION_REQUEST_FOR_ADDITIONAL_INFORMATION = 'reservation-request_for_additional_information';
    private const RESERVATION_ACCEPTED_BY_MANAGING_COMPANY       = 'reservation-accepted_by_managing_company';
    private const RESERVATION_CONTRACT_FORMALIZED                = 'reservation-contract_formalized';
    private const RESERVATION_ARCHIVED                           = 'reservation-archived';
    private const RESERVATION_REFUSED_BY_MANAGING_COMPANY        = 'reservation-refused_by_managing_company';

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

        foreach ($this->loadDataForProgramCommercialized() as $reference => $reservationData) {
            $reservation = $this->buildReservation($reservationData);
            $manager->persist($reservation);
            $this->addReference($reference, $reservation);
        }

        $manager->flush();

        foreach ($this->loadDataForProgramPaused() as $reference => $reservationData) {
            $reservation = $this->buildReservation($reservationData);
            $manager->persist($reservation);
            $this->addReference($reference, $reservation);
        }

        $manager->flush();
    }

    private function loadDataForProgramCommercialized(): iterable
    {
        $participationReferences = [ParticipationFixtures::PARTICIPANT_SAVO, ParticipationFixtures::PARTICIPANT_TOUL];

        /** @var Program $program */
        $program = $this->getReference(ProgramFixtures::REFERENCE_COMMERCIALIZED);

        foreach ($participationReferences as $participationReference) {
            /** @var Participation $participation */
            $participation    = $this->getReference($participationReference);
            $participant      = $participation->getParticipant();
            $staff            = $participant->getStaff()->current();
            $companyShortCode = $participant->getShortCode();

            for ($i = 1; $i <= 5; ++$i) {
                yield \sprintf('%s-%s-%s', self::RESERVATION_DRAFT, $companyShortCode, $i) => [
                    'name'     => \sprintf('Reservation brouillon %s (%s)', $i, $companyShortCode),
                    'program'  => $program,
                    'borrower' => [
                        FieldAlias::CREATION_IN_PROGRESS => true,
                        FieldAlias::YOUNG_FARMER         => $this->faker->boolean,
                        FieldAlias::LEGAL_FORM           => LegalForm::SARL,
                        FieldAlias::EMPLOYEES_NUMBER     => 200,
                    ],
                    'project'          => [],
                    'financingObjects' => [],
                    'addedBy'          => $staff,
                    'currentStatus'    => ReservationStatus::STATUS_DRAFT,
                ];
                yield \sprintf('%s-%s-%s', self::RESERVATION_DRAFT_ELIGIBLE, $companyShortCode, $i) => [
                    'name'     => \sprintf('Reservation brouillon éligible %s (%s)', $i, $companyShortCode),
                    'program'  => $program,
                    'borrower' => [
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => true,
                        FieldAlias::LEGAL_FORM           => LegalForm::SA,
                        FieldAlias::EMPLOYEES_NUMBER     => 200,
                    ],
                    'project' => [
                        FieldAlias::INVESTMENT_THEMATIC => [
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[1],
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[3],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => '0.60',
                        FieldAlias::TANGIBLE_FEI_CREDIT   => 1000,
                        FieldAlias::INTANGIBLE_FEI_CREDIT => 500,
                    ],
                    'financingObjects' => [
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::TERM_LOAN,
                            FieldAlias::LOAN_DURATION                  => 70,
                        ],
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::SIGNATURE_COMMITMENT,
                            FieldAlias::LOAN_DURATION                  => 35,
                        ],
                    ],
                    'addedBy'       => $staff,
                    'currentStatus' => ReservationStatus::STATUS_DRAFT,
                ];
                yield \sprintf('%s-%s-%s', self::RESERVATION_DRAFT_INELIGIBLE, $companyShortCode, $i) => [
                    'name'     => \sprintf('Reservation brouillon inéligible %s (%s)', $i, $companyShortCode),
                    'program'  => $program,
                    'borrower' => [
                        FieldAlias::CREATION_IN_PROGRESS => true,
                        FieldAlias::YOUNG_FARMER         => false,
                        FieldAlias::LEGAL_FORM           => LegalForm::SELAS,
                        FieldAlias::EMPLOYEES_NUMBER     => 200,
                    ],
                    'project' => [
                        FieldAlias::INVESTMENT_THEMATIC => [
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[1],
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[4],
                        ],
                        FieldAlias::RECEIVING_GRANT       => false,
                        FieldAlias::AID_INTENSITY         => '0.20',
                        FieldAlias::TANGIBLE_FEI_CREDIT   => 500,
                        FieldAlias::INTANGIBLE_FEI_CREDIT => 1000,
                    ],
                    'financingObjects' => [
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => false,
                            FieldAlias::LOAN_TYPE                      => LoanType::TERM_LOAN,
                            FieldAlias::LOAN_DURATION                  => 70,
                        ],
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::TERM_LOAN,
                            FieldAlias::LOAN_DURATION                  => 120,
                        ],
                    ],
                    'addedBy'       => $staff,
                    'currentStatus' => ReservationStatus::STATUS_DRAFT,
                ];
                yield \sprintf('%s-%s-%s', self::RESERVATION_SENT, $companyShortCode, $i) => [
                    'name'     => \sprintf('Reservation envoyée %s (%s)', $i, $companyShortCode),
                    'program'  => $program,
                    'borrower' => [
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => true,
                        FieldAlias::LEGAL_FORM           => LegalForm::SARL,
                        FieldAlias::EMPLOYEES_NUMBER     => 200,
                    ],
                    'project' => [
                        FieldAlias::INVESTMENT_THEMATIC => [
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[1],
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[2],
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[3],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => '0.80',
                        FieldAlias::TANGIBLE_FEI_CREDIT   => 2000,
                        FieldAlias::INTANGIBLE_FEI_CREDIT => 100,
                    ],
                    'financingObjects' => [
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::SIGNATURE_COMMITMENT,
                            FieldAlias::LOAN_DURATION                  => 24,
                        ],
                    ],
                    'addedBy'       => $staff,
                    'currentStatus' => ReservationStatus::STATUS_SENT,
                ];
                yield \sprintf('%s-%s-%s', self::RESERVATION_WAITING_FOR_FEI, $companyShortCode, $i) => [
                    'name'     => \sprintf('Reservation attente FEI %s (%s)', $i, $companyShortCode),
                    'program'  => $program,
                    'borrower' => [
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => true,
                        FieldAlias::LEGAL_FORM           => LegalForm::SA,
                        FieldAlias::EMPLOYEES_NUMBER     => 100,
                    ],
                    'project' => [
                        FieldAlias::INVESTMENT_THEMATIC => [
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[2],
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[4],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => '0.80',
                        FieldAlias::TANGIBLE_FEI_CREDIT   => 2000,
                        FieldAlias::INTANGIBLE_FEI_CREDIT => 100,
                    ],
                    'financingObjects' => [
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::TERM_LOAN,
                            FieldAlias::LOAN_DURATION                  => 12,
                        ],
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::TERM_LOAN,
                            FieldAlias::LOAN_DURATION                  => 12,
                        ],
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::TERM_LOAN,
                            FieldAlias::LOAN_DURATION                  => 12,
                        ],
                    ],
                    'addedBy'       => $staff,
                    'currentStatus' => ReservationStatus::STATUS_WAITING_FOR_FEI,
                ];
                yield \sprintf(
                    '%s-%s-%s',
                    self::RESERVATION_REQUEST_FOR_ADDITIONAL_INFORMATION,
                    $companyShortCode,
                    $i
                ) => [
                    'name'     => \sprintf('Reservation information supplémentaire %s (%s)', $i, $companyShortCode),
                    'program'  => $program,
                    'borrower' => [
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => true,
                        FieldAlias::LEGAL_FORM           => LegalForm::SAS,
                        FieldAlias::EMPLOYEES_NUMBER     => 150,
                    ],
                    'project' => [
                        FieldAlias::INVESTMENT_THEMATIC => [
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[0],
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[2],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => '0.40',
                        FieldAlias::TANGIBLE_FEI_CREDIT   => 500,
                        FieldAlias::INTANGIBLE_FEI_CREDIT => 20,
                    ],
                    'financingObjects' => [
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::REVOLVING_CREDIT,
                            FieldAlias::LOAN_DURATION                  => 70,
                        ],
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::SHORT_TERM,
                            FieldAlias::LOAN_DURATION                  => 30,
                        ],
                    ],
                    'addedBy'       => $staff,
                    'currentStatus' => ReservationStatus::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION,
                ];
                yield \sprintf('%s-%s-%s', self::RESERVATION_ACCEPTED_BY_MANAGING_COMPANY, $companyShortCode, $i) => [
                    'name'     => \sprintf('Reservation acceptée %s (%s)', $i, $companyShortCode),
                    'program'  => $program,
                    'borrower' => [
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => false,
                        FieldAlias::LEGAL_FORM           => LegalForm::SAS,
                        FieldAlias::EMPLOYEES_NUMBER     => 30,
                    ],
                    'project' => [
                        FieldAlias::INVESTMENT_THEMATIC => [
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[1],
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[2],
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[3],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => '0.80',
                        FieldAlias::TANGIBLE_FEI_CREDIT   => 22000,
                        FieldAlias::INTANGIBLE_FEI_CREDIT => 400,
                    ],
                    'financingObjects' => [
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::REVOLVING_CREDIT,
                            FieldAlias::LOAN_DURATION                  => 4,
                        ],
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::REVOLVING_CREDIT,
                            FieldAlias::LOAN_DURATION                  => 4,
                        ],
                    ],
                    'addedBy'       => $staff,
                    'currentStatus' => ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY,
                ];
                yield \sprintf('%s-%s-%s', self::RESERVATION_ARCHIVED, $companyShortCode, $i) => [
                    'name'     => \sprintf('Reservation archivée/annulée %s (%s)', $i, $companyShortCode),
                    'program'  => $program,
                    'borrower' => [
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => false,
                        FieldAlias::LEGAL_FORM           => LegalForm::SA,
                        FieldAlias::EMPLOYEES_NUMBER     => 450,
                    ],
                    'project' => [
                        FieldAlias::INVESTMENT_THEMATIC => [
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[1],
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[2],
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[3],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => '0.80',
                        FieldAlias::TANGIBLE_FEI_CREDIT   => 6000,
                        FieldAlias::INTANGIBLE_FEI_CREDIT => 700,
                    ],
                    'financingObjects' => [],
                    'addedBy'          => $staff,
                    'currentStatus'    => ReservationStatus::STATUS_ARCHIVED,
                ];
                yield \sprintf('%s-%s-%s', self::RESERVATION_REFUSED_BY_MANAGING_COMPANY, $companyShortCode, $i) => [
                    'name'     => \sprintf('Reservation refusée %s (%s)', $i, $companyShortCode),
                    'program'  => $program,
                    'borrower' => [
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => false,
                        FieldAlias::LEGAL_FORM           => LegalForm::SA,
                        FieldAlias::EMPLOYEES_NUMBER     => 200,
                    ],
                    'project' => [
                        FieldAlias::INVESTMENT_THEMATIC => [
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[1],
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[3],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => '0.80',
                        FieldAlias::TANGIBLE_FEI_CREDIT   => 80000,
                        FieldAlias::INTANGIBLE_FEI_CREDIT => 100,
                    ],
                    'financingObjects' => [
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::SHORT_TERM,
                            FieldAlias::LOAN_DURATION                  => 4,
                        ],
                    ],
                    'addedBy'       => $staff,
                    'currentStatus' => ReservationStatus::STATUS_REFUSED_BY_MANAGING_COMPANY,
                ];
            }

            for ($i = 1; $i <= 20; ++$i) {
                yield \sprintf('%s-%s-%s', self::RESERVATION_CONTRACT_FORMALIZED, $companyShortCode, $i) => [
                    'name'     => \sprintf('Reservation contractualisée %s (%s)', $i, $companyShortCode),
                    'program'  => $program,
                    'borrower' => [
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => true,
                        FieldAlias::LEGAL_FORM           => LegalForm::SA,
                        FieldAlias::EMPLOYEES_NUMBER     => 300,
                    ],
                    'project' => [
                        FieldAlias::INVESTMENT_THEMATIC => [
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[1],
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[2],
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[3],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => '0.80',
                        FieldAlias::TANGIBLE_FEI_CREDIT   => 1000,
                        FieldAlias::INTANGIBLE_FEI_CREDIT => 300,
                    ],
                    'financingObjects' => [
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::TERM_LOAN,
                            FieldAlias::LOAN_DURATION                  => 50,
                        ],
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::SHORT_TERM,
                            FieldAlias::LOAN_DURATION                  => 30,
                        ],
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::REVOLVING_CREDIT,
                            FieldAlias::LOAN_DURATION                  => 12,
                        ],
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::SIGNATURE_COMMITMENT,
                            FieldAlias::LOAN_DURATION                  => 48,
                        ],
                        [
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => LoanType::TERM_LOAN,
                            FieldAlias::LOAN_DURATION                  => 70,
                        ],
                    ],
                    'addedBy'       => $staff,
                    'currentStatus' => ReservationStatus::STATUS_CONTRACT_FORMALIZED,
                ];
            }
        }
    }

    private function loadDataForProgramPaused(): iterable
    {
        $participationReferences = [ParticipationFixtures::PARTICIPANT_SAVO, ParticipationFixtures::PARTICIPANT_TOUL];

        /** @var Program $program */
        $program = $this->getReference(ProgramFixtures::REFERENCE_PAUSED);

        foreach ($participationReferences as $participationReference) {
            /** @var Participation $participation */
            $participation        = $this->getReference($participationReference);
            $staff                = $participation->getParticipant()->getStaff()->current();
            $participationParts   = \explode('_', $participationReference);
            $participantShortCode = \mb_strtolower(\array_pop($participationParts));
            $referenceSuffix      = \sprintf('%s_%s', $program->getId(), $participantShortCode);

            $initFinancingObjects = [
                [
                    FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                    FieldAlias::LOAN_TYPE                      => LoanType::TERM_LOAN,
                    FieldAlias::LOAN_DURATION                  => 50,
                ],
                [
                    FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                    FieldAlias::LOAN_TYPE                      => LoanType::SHORT_TERM,
                    FieldAlias::LOAN_DURATION                  => 30,
                ],
                [
                    FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                    FieldAlias::LOAN_TYPE                      => LoanType::REVOLVING_CREDIT,
                    FieldAlias::LOAN_DURATION                  => 12,
                ],
                [
                    FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                    FieldAlias::LOAN_TYPE                      => LoanType::SIGNATURE_COMMITMENT,
                    FieldAlias::LOAN_DURATION                  => 48,
                ],
                [
                    FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                    FieldAlias::LOAN_TYPE                      => LoanType::TERM_LOAN,
                    FieldAlias::LOAN_DURATION                  => 70,
                ],
            ];

            $financingObjects = [];

            foreach (\range(1, 20) as $index) {
                foreach ($initFinancingObjects as $financingObject) {
                    $financingObjects[] = $financingObject;
                }
            }

            foreach (\range(1, 10) as $index) {
                yield \sprintf('%s_%s_%s', self::RESERVATION_CONTRACT_FORMALIZED, $referenceSuffix, $index) => [
                    'name'     => \sprintf('Reservation contractualisée %s (%s)', $index, $participantShortCode),
                    'program'  => $program,
                    'borrower' => [
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => true,
                        FieldAlias::LEGAL_FORM           => LegalForm::SA,
                        FieldAlias::EMPLOYEES_NUMBER     => 300,
                    ],
                    'project' => [
                        FieldAlias::INVESTMENT_THEMATIC => [
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[1],
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[2],
                            ProgramChoiceOptionFixtures::INVESTMENT_THEMATIC_LIST[3],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => '0.60',
                        FieldAlias::TANGIBLE_FEI_CREDIT   => 1000,
                        FieldAlias::INTANGIBLE_FEI_CREDIT => 300,
                    ],
                    'financingObjects' => $financingObjects,
                    'addedBy'          => $staff,
                    'currentStatus'    => ReservationStatus::STATUS_CONTRACT_FORMALIZED,
                ];
            }
        }
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

        if (ReservationStatus::STATUS_CONTRACT_FORMALIZED === $reservationData['currentStatus']) {
            $reservation->setSigningDate(new DateTimeImmutable());
        }

        return $reservation;
    }

    private function withBorrower(Reservation $reservation, array $data): void
    {
        $program = $reservation->getProgram();
        $grades  = $program->getProgramGradeAllocations()
            ->map(fn (ProgramGradeAllocation $item) => $item->getGrade())
            ->toArray()
        ;

        $reservation->getBorrower()
            ->setBeneficiaryName($this->faker->name)
            ->setBorrowerType($this->findProgramChoiceOption($program, FieldAlias::BORROWER_TYPE))
            ->setYoungFarmer($data[FieldAlias::YOUNG_FARMER])
            ->setCreationInProgress($data[FieldAlias::CREATION_IN_PROGRESS])
            ->setSubsidiary($this->faker->boolean)
            ->setEconomicallyViable($this->faker->boolean)
            ->setListedOnStockMarket($this->faker->boolean)
            ->setBenefitingProfitTransfer($this->faker->boolean)
            ->setInNonCooperativeJurisdiction($this->faker->boolean)
            ->setSubjectOfUnperformedRecoveryOrder($this->faker->boolean)
            ->setSubjectOfRestructuringPlan($this->faker->boolean)
            ->setProjectReceivedFeagaOcmFunding($this->faker->boolean)
            ->setLoanSupportingDocumentsDatesAfterApplication($this->faker->boolean)
            ->setLoanAllowedRefinanceRestructure($this->faker->boolean)
            ->setTransactionAffected($this->faker->boolean)
            ->setCompanyName($this->faker->company)
            ->setActivityStartDate(new DateTimeImmutable())
            ->setRegistrationNumber('12 23 45 678 987')
            ->setLegalForm(
                $this->findProgramChoiceOption($program, FieldAlias::LEGAL_FORM, $data[FieldAlias::LEGAL_FORM])
            )
            ->setCompanyNafCode($this->findProgramChoiceOption($program, FieldAlias::COMPANY_NAF_CODE))
            ->setAddressStreet($this->faker->streetAddress)
            ->setAddressCity($this->faker->city)
            ->setAddressPostCode($this->faker->postcode)
            ->setAddressDepartment($this->findProgramChoiceOption($program, FieldAlias::ACTIVITY_DEPARTMENT))
            ->setAddressCountry($this->findProgramChoiceOption($program, FieldAlias::ACTIVITY_COUNTRY))
            ->setEmployeesNumber($data[FieldAlias::EMPLOYEES_NUMBER])
            ->setExploitationSize($this->findProgramChoiceOption($program, FieldAlias::EXPLOITATION_SIZE))
            ->setTurnover(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setTotalAssets(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setTargetType($this->findProgramChoiceOption($program, FieldAlias::TARGET_TYPE))
            ->setGrade($grades[\array_rand($grades)])
        ;
    }

    private function withProject(Reservation $reservation, array $data): void
    {
        $program = $reservation->getProgram();
        $project = $reservation->getProject();

        foreach ($data[FieldAlias::INVESTMENT_THEMATIC] as $description) {
            $project->addInvestmentThematic(
                $this->findProgramChoiceOption($program, FieldAlias::INVESTMENT_THEMATIC, $description)
            );
        }

        $project
            ->setInvestmentType(
                $this->findProgramChoiceOption($program, FieldAlias::INVESTMENT_TYPE, 'Type: ' . $this->faker->sentence)
            )
            ->setDetail($this->faker->sentence)
            ->setAidIntensity(
                $this->findProgramChoiceOption($program, FieldAlias::AID_INTENSITY, $data[FieldAlias::AID_INTENSITY])
            )
            ->setAdditionalGuaranty(
                $this->findProgramChoiceOption(
                    $program,
                    FieldAlias::ADDITIONAL_GUARANTY,
                    $this->faker->unique()->sentence(3)
                )
            )
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
            ->setAddressDepartment($this->findProgramChoiceOption($program, FieldAlias::INVESTMENT_DEPARTMENT))
            ->setAddressCountry($this->findProgramChoiceOption($program, FieldAlias::INVESTMENT_COUNTRY))
            ->setFundingMoney(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setContribution(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setEligibleFeiCredit(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setTotalFeiCredit(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setTangibleFeiCredit(new NullableMoney('EUR', (string) $data[FieldAlias::TANGIBLE_FEI_CREDIT]))
            ->setIntangibleFeiCredit(new NullableMoney('EUR', (string) $data[FieldAlias::INTANGIBLE_FEI_CREDIT]))
            ->setCreditExcludingFei(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setLandValue(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
        ;

        if ($data[FieldAlias::RECEIVING_GRANT]) {
            $project->setGrant(new NullableMoney('EUR', (string) $this->faker->randomNumber()));
        }
    }

    private function createFinancingObject(Reservation $reservation, array $data): FinancingObject
    {
        $program   = $reservation->getProgram();
        $loanMoney = new Money('EUR', (string) $this->faker->randomNumber(4));

        return (new FinancingObject($reservation, $loanMoney, $this->faker->boolean, $this->faker->sentence(3, true)))
            ->setSupportingGenerationsRenewal($data[FieldAlias::SUPPORTING_GENERATIONS_RENEWAL])
            ->setFinancingObjectType(
                $this->findProgramChoiceOption($program, FieldAlias::FINANCING_OBJECT_TYPE, $this->faker->sentence)
            )
            ->setLoanNafCode($this->findProgramChoiceOption($program, FieldAlias::LOAN_NAF_CODE))
            ->setBfrValue(new NullableMoney('EUR', (string) $this->faker->randomNumber()))
            ->setLoanType($this->findProgramChoiceOption($program, FieldAlias::LOAN_TYPE, $data[FieldAlias::LOAN_TYPE]))
            ->setLoanDuration($data[FieldAlias::LOAN_DURATION])
            ->setLoanDeferral($this->faker->numberBetween(0, 12))
            ->setLoanPeriodicity($this->findProgramChoiceOption($program, FieldAlias::LOAN_PERIODICITY))
            ->setInvestmentLocation($this->findProgramChoiceOption($program, FieldAlias::INVESTMENT_LOCATION))
            ->setProductCategoryCode($this->findProgramChoiceOption($program, FieldAlias::PRODUCT_CATEGORY_CODE))
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
        string $fieldAlias,
        ?string $description = null
    ): ProgramChoiceOption {
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
