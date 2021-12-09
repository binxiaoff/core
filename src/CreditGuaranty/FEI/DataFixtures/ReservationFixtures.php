<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\DataFixtures\CompanyFixtures;
use KLS\Core\DataFixtures\NafNaceFixtures;
use KLS\Core\Entity\Constant\LegalForm;
use KLS\Core\Entity\Constant\LoanType;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\CreditGuaranty\FEI\Entity\Borrower;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Interfaces\ProgramAwareInterface;
use KLS\CreditGuaranty\FEI\Entity\Participation;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Entity\ProgramGradeAllocation;
use KLS\CreditGuaranty\FEI\Entity\Project;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramChoiceOptionRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ReservationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private const FORCE_DEFAULT_VALUES = 'forceDefaultValues';

    private ObjectManager $entityManager;
    private FieldRepository $fieldRepository;
    private ObjectRepository $participationRepository;
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
            ParticipationFixtures::class,
            ProgramEligibilityFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $this->entityManager           = $manager;
        $this->participationRepository = $this->entityManager->getRepository(Participation::class);

        $i = 0;

        foreach ($this->loadData() as $reservationData) {
            $reservation = $this->buildReservation($reservationData);
            $manager->persist($reservation);
            ++$i;

            if (0 === $i % 100) {
                $manager->flush();
            }
        }

        $manager->flush();
    }

    private function loadData(): iterable
    {
        yield from $this->loadDataForProgramCommercialized();
        yield from $this->loadDataForProgramPaused();
    }

    private function loadDataForProgramCommercialized(): iterable
    {
        $participations = $this->participationRepository->findBy([
            'program' => $this->getReferences([
                ProgramFixtures::PROGRAM_AGRICULTURE_COMMERCIALIZED,
                ProgramFixtures::PROGRAM_CORPORATE_COMMERCIALIZED,
            ]),
            'participant' => $this->getReferences([
                CompanyFixtures::REFERENCE_PREFIX . CompanyFixtures::CA_SHORTCODE['CA des Savoie'],
                CompanyFixtures::REFERENCE_PREFIX . CompanyFixtures::CA_SHORTCODE['CA Toulouse 31'],
            ]),
        ]);

        $programChoiceOptions      = ProgramChoiceOptionFixtures::ALL;
        $aidIntensityOptions       = $programChoiceOptions[FieldAlias::AID_INTENSITY];
        $investmentThematicOptions = $programChoiceOptions[FieldAlias::INVESTMENT_THEMATIC];
        $legalFormOptions          = $programChoiceOptions[FieldAlias::LEGAL_FORM];
        $loanTypeOptions           = $programChoiceOptions[FieldAlias::LOAN_TYPE];

        /** @var Participation $participation */
        foreach ($participations as $participation) {
            $program          = $participation->getProgram();
            $participant      = $participation->getParticipant();
            $staff            = $participant->getStaff()->current();
            $companyShortCode = $participant->getShortCode();

            for ($i = 1; $i <= 5; ++$i) {
                yield [
                    'name'     => \sprintf('Dossier P%s %s-D %s', $program->getId(), $companyShortCode, $i),
                    'program'  => $program,
                    'borrower' => [
                        self::FORCE_DEFAULT_VALUES       => false,
                        FieldAlias::CREATION_IN_PROGRESS => true,
                        FieldAlias::LEGAL_FORM           => $legalFormOptions[LegalForm::SARL],
                        FieldAlias::EMPLOYEES_NUMBER     => 200,
                    ],
                    'project'          => [],
                    'financingObjects' => [],
                    'addedBy'          => $staff,
                    'currentStatus'    => ReservationStatus::STATUS_DRAFT,
                ];
                yield [
                    'name'     => \sprintf('Dossier P%s %s-DE %s', $program->getId(), $companyShortCode, $i),
                    'program'  => $program,
                    'borrower' => [
                        self::FORCE_DEFAULT_VALUES       => true,
                        FieldAlias::YOUNG_FARMER         => true,
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::LEGAL_FORM           => $legalFormOptions[LegalForm::SA],
                        FieldAlias::EMPLOYEES_NUMBER     => 200,
                    ],
                    'project' => [
                        self::FORCE_DEFAULT_VALUES      => true,
                        FieldAlias::INVESTMENT_THEMATIC => [
                            $investmentThematicOptions['Mieux répondre aux attentes'],
                            $investmentThematicOptions['Accompagner'],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => $aidIntensityOptions['0.40'],
                        FieldAlias::TANGIBLE_FEI_CREDIT   => new NullableMoney('EUR', (string) 1000),
                        FieldAlias::INTANGIBLE_FEI_CREDIT => new NullableMoney('EUR', (string) 500),
                    ],
                    'financingObjects' => [
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::TERM_LOAN],
                            FieldAlias::LOAN_DURATION                  => 70,
                        ],
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::SIGNATURE_COMMITMENT],
                            FieldAlias::LOAN_DURATION                  => 35,
                        ],
                    ],
                    'addedBy'       => $staff,
                    'currentStatus' => ReservationStatus::STATUS_DRAFT,
                ];
                yield [
                    'name'     => \sprintf('Dossier P%s %s-DI %s', $program->getId(), $companyShortCode, $i),
                    'program'  => $program,
                    'borrower' => [
                        self::FORCE_DEFAULT_VALUES       => true,
                        FieldAlias::CREATION_IN_PROGRESS => true,
                        FieldAlias::YOUNG_FARMER         => false,
                        FieldAlias::LEGAL_FORM           => $legalFormOptions[LegalForm::SELAS],
                        FieldAlias::EMPLOYEES_NUMBER     => 200,
                    ],
                    'project' => [
                        self::FORCE_DEFAULT_VALUES      => true,
                        FieldAlias::INVESTMENT_THEMATIC => [
                            $investmentThematicOptions['Mieux répondre aux attentes'],
                            $investmentThematicOptions['Mettre à niveau'],
                        ],
                        FieldAlias::RECEIVING_GRANT       => false,
                        FieldAlias::AID_INTENSITY         => $aidIntensityOptions['0.20'],
                        FieldAlias::TANGIBLE_FEI_CREDIT   => new NullableMoney('EUR', (string) 500),
                        FieldAlias::INTANGIBLE_FEI_CREDIT => new NullableMoney('EUR', (string) 1000),
                    ],
                    'financingObjects' => [
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => false,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::TERM_LOAN],
                            FieldAlias::LOAN_DURATION                  => 70,
                        ],
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::TERM_LOAN],
                            FieldAlias::LOAN_DURATION                  => 120,
                        ],
                    ],
                    'addedBy'       => $staff,
                    'currentStatus' => ReservationStatus::STATUS_DRAFT,
                ];
                yield [
                    'name'     => \sprintf('Dossier P%s %s-S %s', $program->getId(), $companyShortCode, $i),
                    'program'  => $program,
                    'borrower' => [
                        self::FORCE_DEFAULT_VALUES       => true,
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => true,
                        FieldAlias::LEGAL_FORM           => $legalFormOptions[LegalForm::SARL],
                        FieldAlias::EMPLOYEES_NUMBER     => 200,
                    ],
                    'project' => [
                        self::FORCE_DEFAULT_VALUES      => true,
                        FieldAlias::INVESTMENT_THEMATIC => [
                            $investmentThematicOptions['Mieux répondre aux attentes'],
                            $investmentThematicOptions['Transformation'],
                            $investmentThematicOptions['Accompagner'],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => $aidIntensityOptions['0.80'],
                        FieldAlias::TANGIBLE_FEI_CREDIT   => new NullableMoney('EUR', (string) 2000),
                        FieldAlias::INTANGIBLE_FEI_CREDIT => new NullableMoney('EUR', (string) 100),
                    ],
                    'financingObjects' => [
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::SIGNATURE_COMMITMENT],
                            FieldAlias::LOAN_DURATION                  => 24,
                        ],
                    ],
                    'addedBy'       => $staff,
                    'currentStatus' => ReservationStatus::STATUS_SENT,
                ];
                yield [
                    'name'     => \sprintf('Dossier P%s %s-WFE %s', $program->getId(), $companyShortCode, $i),
                    'program'  => $program,
                    'borrower' => [
                        self::FORCE_DEFAULT_VALUES       => true,
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => true,
                        FieldAlias::LEGAL_FORM           => $legalFormOptions[LegalForm::SA],
                        FieldAlias::EMPLOYEES_NUMBER     => 100,
                    ],
                    'project' => [
                        self::FORCE_DEFAULT_VALUES      => true,
                        FieldAlias::INVESTMENT_THEMATIC => [
                            $investmentThematicOptions['Transformation'],
                            $investmentThematicOptions['Mettre à niveau'],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => $aidIntensityOptions['0.80'],
                        FieldAlias::TANGIBLE_FEI_CREDIT   => new NullableMoney('EUR', (string) 2000),
                        FieldAlias::INTANGIBLE_FEI_CREDIT => new NullableMoney('EUR', (string) 100),
                    ],
                    'financingObjects' => [
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::TERM_LOAN],
                            FieldAlias::LOAN_DURATION                  => 12,
                        ],
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::TERM_LOAN],
                            FieldAlias::LOAN_DURATION                  => 12,
                        ],
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::TERM_LOAN],
                            FieldAlias::LOAN_DURATION                  => 12,
                        ],
                    ],
                    'addedBy'       => $staff,
                    'currentStatus' => ReservationStatus::STATUS_WAITING_FOR_FEI,
                ];
                yield [
                    'name'     => \sprintf('Dossier P%s %s-RFAI %s', $program->getId(), $companyShortCode, $i),
                    'program'  => $program,
                    'borrower' => [
                        self::FORCE_DEFAULT_VALUES       => true,
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => true,
                        FieldAlias::LEGAL_FORM           => $legalFormOptions[LegalForm::SAS],
                        FieldAlias::EMPLOYEES_NUMBER     => 150,
                    ],
                    'project' => [
                        self::FORCE_DEFAULT_VALUES      => true,
                        FieldAlias::INVESTMENT_THEMATIC => [
                            $investmentThematicOptions['Renouvellement'],
                            $investmentThematicOptions['Transformation'],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => $aidIntensityOptions['0.40'],
                        FieldAlias::TANGIBLE_FEI_CREDIT   => new NullableMoney('EUR', (string) 500),
                        FieldAlias::INTANGIBLE_FEI_CREDIT => new NullableMoney('EUR', (string) 20),
                    ],
                    'financingObjects' => [
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::REVOLVING_CREDIT],
                            FieldAlias::LOAN_DURATION                  => 70,
                        ],
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::SHORT_TERM],
                            FieldAlias::LOAN_DURATION                  => 30,
                        ],
                    ],
                    'addedBy'       => $staff,
                    'currentStatus' => ReservationStatus::STATUS_REQUEST_FOR_ADDITIONAL_INFORMATION,
                ];
                yield [
                    'name'     => \sprintf('Dossier P%s %s-ABMC %s', $program->getId(), $companyShortCode, $i),
                    'program'  => $program,
                    'borrower' => [
                        self::FORCE_DEFAULT_VALUES       => true,
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => false,
                        FieldAlias::LEGAL_FORM           => $legalFormOptions[LegalForm::SAS],
                        FieldAlias::EMPLOYEES_NUMBER     => 30,
                    ],
                    'project' => [
                        self::FORCE_DEFAULT_VALUES      => true,
                        FieldAlias::INVESTMENT_THEMATIC => [
                            $investmentThematicOptions['Mieux répondre aux attentes'],
                            $investmentThematicOptions['Transformation'],
                            $investmentThematicOptions['Accompagner'],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => $aidIntensityOptions['0.80'],
                        FieldAlias::TANGIBLE_FEI_CREDIT   => new NullableMoney('EUR', (string) 22000),
                        FieldAlias::INTANGIBLE_FEI_CREDIT => new NullableMoney('EUR', (string) 400),
                    ],
                    'financingObjects' => [
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::REVOLVING_CREDIT],
                            FieldAlias::LOAN_DURATION                  => 4,
                        ],
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::REVOLVING_CREDIT],
                            FieldAlias::LOAN_DURATION                  => 4,
                        ],
                    ],
                    'addedBy'       => $staff,
                    'currentStatus' => ReservationStatus::STATUS_ACCEPTED_BY_MANAGING_COMPANY,
                ];
                yield [
                    'name'     => \sprintf('Dossier P%s %s-A %s', $program->getId(), $companyShortCode, $i),
                    'program'  => $program,
                    'borrower' => [
                        self::FORCE_DEFAULT_VALUES       => true,
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => false,
                        FieldAlias::LEGAL_FORM           => $legalFormOptions[LegalForm::SA],
                        FieldAlias::EMPLOYEES_NUMBER     => 450,
                    ],
                    'project' => [
                        self::FORCE_DEFAULT_VALUES      => true,
                        FieldAlias::INVESTMENT_THEMATIC => [
                            $investmentThematicOptions['Mieux répondre aux attentes'],
                            $investmentThematicOptions['Transformation'],
                            $investmentThematicOptions['Accompagner'],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => $aidIntensityOptions['0.80'],
                        FieldAlias::TANGIBLE_FEI_CREDIT   => new NullableMoney('EUR', (string) 6000),
                        FieldAlias::INTANGIBLE_FEI_CREDIT => new NullableMoney('EUR', (string) 700),
                    ],
                    'financingObjects' => [],
                    'addedBy'          => $staff,
                    'currentStatus'    => ReservationStatus::STATUS_ARCHIVED,
                ];
                yield [
                    'name'     => \sprintf('Dossier P%s %s-RBMC %s', $program->getId(), $companyShortCode, $i),
                    'program'  => $program,
                    'borrower' => [
                        self::FORCE_DEFAULT_VALUES       => true,
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => false,
                        FieldAlias::LEGAL_FORM           => $legalFormOptions[LegalForm::SA],
                        FieldAlias::EMPLOYEES_NUMBER     => 200,
                    ],
                    'project' => [
                        self::FORCE_DEFAULT_VALUES      => true,
                        FieldAlias::INVESTMENT_THEMATIC => [
                            $investmentThematicOptions['Mieux répondre aux attentes'],
                            $investmentThematicOptions['Accompagner'],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => $aidIntensityOptions['0.80'],
                        FieldAlias::TANGIBLE_FEI_CREDIT   => new NullableMoney('EUR', (string) 80000),
                        FieldAlias::INTANGIBLE_FEI_CREDIT => new NullableMoney('EUR', (string) 100),
                    ],
                    'financingObjects' => [
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::SHORT_TERM],
                            FieldAlias::LOAN_DURATION                  => 4,
                        ],
                    ],
                    'addedBy'       => $staff,
                    'currentStatus' => ReservationStatus::STATUS_REFUSED_BY_MANAGING_COMPANY,
                ];
            }

            for ($i = 1; $i <= 10; ++$i) {
                yield [
                    'name'     => \sprintf('Dossier P%s %s-CF %s', $program->getId(), $companyShortCode, $i),
                    'program'  => $program,
                    'borrower' => [
                        self::FORCE_DEFAULT_VALUES       => true,
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => true,
                        FieldAlias::LEGAL_FORM           => $legalFormOptions[LegalForm::SA],
                        FieldAlias::EMPLOYEES_NUMBER     => 300,
                    ],
                    'project' => [
                        self::FORCE_DEFAULT_VALUES      => true,
                        FieldAlias::INVESTMENT_THEMATIC => [
                            $investmentThematicOptions['Mieux répondre aux attentes'],
                            $investmentThematicOptions['Transformation'],
                            $investmentThematicOptions['Accompagner'],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => $aidIntensityOptions['0.80'],
                        FieldAlias::TANGIBLE_FEI_CREDIT   => new NullableMoney('EUR', (string) 1000),
                        FieldAlias::INTANGIBLE_FEI_CREDIT => new NullableMoney('EUR', (string) 300),
                    ],
                    'financingObjects' => [
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::TERM_LOAN],
                            FieldAlias::LOAN_DURATION                  => 50,
                        ],
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::SHORT_TERM],
                            FieldAlias::LOAN_DURATION                  => 30,
                        ],
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::REVOLVING_CREDIT],
                            FieldAlias::LOAN_DURATION                  => 12,
                        ],
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::SIGNATURE_COMMITMENT],
                            FieldAlias::LOAN_DURATION                  => 48,
                        ],
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::TERM_LOAN],
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
        $participations = $this->participationRepository->findBy([
            'program' => $this->getReferences([
                ProgramFixtures::PROGRAM_AGRICULTURE_PAUSED,
                ProgramFixtures::PROGRAM_CORPORATE_PAUSED,
            ]),
            'participant' => $this->getReferences([
                CompanyFixtures::REFERENCE_PREFIX . CompanyFixtures::CA_SHORTCODE['CA des Savoie'],
                CompanyFixtures::REFERENCE_PREFIX . CompanyFixtures::CA_SHORTCODE['CA Toulouse 31'],
            ]),
        ]);

        $programChoiceOptions      = ProgramChoiceOptionFixtures::ALL;
        $aidIntensityOptions       = $programChoiceOptions[FieldAlias::AID_INTENSITY];
        $investmentThematicOptions = $programChoiceOptions[FieldAlias::INVESTMENT_THEMATIC];
        $legalFormOptions          = $programChoiceOptions[FieldAlias::LEGAL_FORM];
        $loanTypeOptions           = $programChoiceOptions[FieldAlias::LOAN_TYPE];

        /** @var Participation $participation */
        foreach ($participations as $participation) {
            $program     = $participation->getProgram();
            $participant = $participation->getParticipant();
            $staff       = $participant->getStaff()->current();

            foreach (\range(1, 10) as $index) {
                yield [
                    'name'     => \sprintf('Dossier P%s %s-CF %s', $program->getId(), $participant->getShortCode(), $index),
                    'program'  => $program,
                    'borrower' => [
                        self::FORCE_DEFAULT_VALUES       => true,
                        FieldAlias::CREATION_IN_PROGRESS => false,
                        FieldAlias::YOUNG_FARMER         => true,
                        FieldAlias::LEGAL_FORM           => $legalFormOptions[LegalForm::SA],
                        FieldAlias::EMPLOYEES_NUMBER     => 300,
                    ],
                    'project' => [
                        self::FORCE_DEFAULT_VALUES      => true,
                        FieldAlias::INVESTMENT_THEMATIC => [
                            $investmentThematicOptions['Mieux répondre aux attentes'],
                            $investmentThematicOptions['Transformation'],
                            $investmentThematicOptions['Accompagner'],
                        ],
                        FieldAlias::RECEIVING_GRANT       => true,
                        FieldAlias::AID_INTENSITY         => $aidIntensityOptions['0.80'],
                        FieldAlias::TANGIBLE_FEI_CREDIT   => new NullableMoney('EUR', (string) 1000),
                        FieldAlias::INTANGIBLE_FEI_CREDIT => new NullableMoney('EUR', (string) 300),
                    ],
                    'financingObjects' => [
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::TERM_LOAN],
                            FieldAlias::LOAN_DURATION                  => 50,
                        ],
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::SHORT_TERM],
                            FieldAlias::LOAN_DURATION                  => 30,
                        ],
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::REVOLVING_CREDIT],
                            FieldAlias::LOAN_DURATION                  => 12,
                        ],
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::SIGNATURE_COMMITMENT],
                            FieldAlias::LOAN_DURATION                  => 48,
                        ],
                        [
                            self::FORCE_DEFAULT_VALUES                 => true,
                            FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => true,
                            FieldAlias::LOAN_TYPE                      => $loanTypeOptions[LoanType::TERM_LOAN],
                            FieldAlias::LOAN_DURATION                  => 70,
                        ],
                    ],
                    'addedBy'       => $staff,
                    'currentStatus' => ReservationStatus::STATUS_CONTRACT_FORMALIZED,
                ];
            }
        }
    }

    private function buildReservation(array $reservationData): Reservation
    {
        $reservation = new Reservation($reservationData['program'], $reservationData['addedBy']);
        $reservation->setName($reservationData['name']);

        $currentReservationStatus = new ReservationStatus(
            $reservation,
            $reservationData['currentStatus'],
            $reservationData['addedBy']
        );
        $this->createReservationStatuses($currentReservationStatus);
        $reservation->setCurrentStatus($currentReservationStatus);

        $this->withBorrower($reservation, $reservationData['borrower']);

        if (false === empty($reservationData['project'])) {
            $this->withProject($reservation, $reservationData['project']);
        }

        $totalAmount = 0;

        if (false === empty($reservationData['financingObjects'])) {
            for ($i = 0; $i < \count($reservationData['financingObjects']); ++$i) {
                $financingObject = $this->createFinancingObject($reservation, $reservationData['financingObjects'][$i]);
                $financingObject->setMainLoan(0 === $i);
                $this->entityManager->persist($financingObject);
                $totalAmount += (float) $financingObject->getLoanMoney()->getAmount();
            }
        }

        $reservation->getProject()->setTotalFeiCredit(new NullableMoney('EUR', (string) $totalAmount));

        if (ReservationStatus::STATUS_CONTRACT_FORMALIZED === $reservationData['currentStatus']) {
            $reservation->setSigningDate(new DateTimeImmutable());
        }

        return $reservation;
    }

    private function withBorrower(Reservation $reservation, array $data): void
    {
        $program              = $reservation->getProgram();
        $programEligibilities = $program->getProgramEligibilities()
            ->filter(static fn (ProgramEligibility $pe) => Borrower::class === $pe->getField()->getObjectClass())
        ;
        $grades = $program->getProgramGradeAllocations()
            ->map(fn (ProgramGradeAllocation $item) => $item->getGrade())
            ->toArray()
        ;

        $reservation->getBorrower()
            ->setBeneficiaryName($this->faker->name)
            ->setGrade($grades[\array_rand($grades)])
        ;

        /** @var ProgramEligibility $programEligibility */
        foreach ($programEligibilities as $programEligibility) {
            $field      = $programEligibility->getField();
            $fieldAlias = $field->getFieldAlias();

            // no need to set this property because it is set juste before this loop by default
            // (black-hole mirror front)
            if (FieldAlias::BENEFICIARY_NAME === $fieldAlias) {
                continue;
            }

            $this->setValue(
                $reservation->getBorrower(),
                $field,
                $data[$fieldAlias] ?? null,
                $data[self::FORCE_DEFAULT_VALUES]
            );
        }
    }

    private function withProject(Reservation $reservation, array $data): void
    {
        $program              = $reservation->getProgram();
        $defaultData          = $this->getDefaultData($program, Project::class);
        $programEligibilities = $program->getProgramEligibilities()
            ->filter(static fn (ProgramEligibility $pe) => Project::class === $pe->getField()->getObjectClass())
        ;

        /** @var ProgramEligibility $programEligibility */
        foreach ($programEligibilities as $programEligibility) {
            $field      = $programEligibility->getField();
            $fieldAlias = $field->getFieldAlias();

            if (FieldAlias::RECEIVING_GRANT === $fieldAlias) {
                if ($data[FieldAlias::RECEIVING_GRANT]) {
                    $reservation->getProject()->setGrant($defaultData[FieldAlias::PROJECT_GRANT]);
                }

                continue;
            }

            $this->setValue(
                $reservation->getProject(),
                $field,
                $data[$fieldAlias] ?? null,
                $data[self::FORCE_DEFAULT_VALUES]
            );
        }
    }

    private function createFinancingObject(Reservation $reservation, array $data): FinancingObject
    {
        $program              = $reservation->getProgram();
        $programEligibilities = $program->getProgramEligibilities()
            ->filter(static fn (ProgramEligibility $pe) => FinancingObject::class === $pe->getField()->getObjectClass())
        ;

        $financingObject = new FinancingObject(
            $reservation,
            new Money('EUR', (string) $this->faker->randomNumber(4)),
            $this->faker->boolean,
            $this->faker->sentence(3, true)
        );

        /** @var ProgramEligibility $programEligibility */
        foreach ($programEligibilities as $programEligibility) {
            $field      = $programEligibility->getField();
            $fieldAlias = $field->getFieldAlias();

            // no need to set these properties because they are set just before this loop by default
            if (\in_array($fieldAlias, [FieldAlias::FINANCING_OBJECT_NAME, FieldAlias::LOAN_MONEY])) {
                continue;
            }

            $this->setValue(
                $financingObject,
                $field,
                $data[$fieldAlias] ?? null,
                $data[self::FORCE_DEFAULT_VALUES]
            );
        }

        return $financingObject;
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

    private function setValue(ProgramAwareInterface $entity, Field $field, $value, bool $forceDefaultValues): void
    {
        $program     = $entity->getProgram();
        $defaultData = $this->getDefaultData($program, $field->getObjectClass());
        $fieldAlias  = $field->getFieldAlias();

        if (null !== $value && Field::TYPE_LIST === $field->getType()) {
            $value = $this->findProgramChoiceOption($program, $fieldAlias, $value);
        }
        if (null === $value && $forceDefaultValues) {
            $value = $defaultData[$fieldAlias];
        }

        if (null !== $value) {
            $this->forcePropertyValue($entity, $field->getPropertyPath(), $value);
        }
    }

    /**
     * @param string|array|null $description
     *
     * @return ProgramChoiceOption|ProgramChoiceOption[]|null
     */
    private function findProgramChoiceOption(
        Program $program,
        string $fieldAlias,
        $description = null
    ) {
        /** @var Field $field */
        $field = $this->fieldRepository->findOneBy(['fieldAlias' => $fieldAlias]);

        if (empty($description)) {
            $programChoiceOptions = $this->programChoiceOptionRepository->findBy([
                'program' => $program,
                'field'   => $field,
            ]);

            if (empty($programChoiceOptions)) {
                return null;
            }

            return $programChoiceOptions[\array_rand($programChoiceOptions)];
        }

        $programChoiceOption = (\is_array($description))
            ? $this->programChoiceOptionRepository->findBy([
                'program'     => $program,
                'field'       => $field,
                'description' => $description,
            ])
            : $this->programChoiceOptionRepository->findOneBy([
                'program'     => $program,
                'field'       => $field,
                'description' => $description,
            ])
        ;

        if (\is_array($programChoiceOption) && false === empty($programChoiceOption)) {
            $programChoiceOption = new ArrayCollection($programChoiceOption);
        }

        if (
            false === ($programChoiceOption instanceof ProgramChoiceOption)
            && false === ($programChoiceOption instanceof Collection)
        ) {
            return null;
        }

        return $programChoiceOption;
    }

    private function getDefaultData(Program $program, string $objectClassName): array
    {
        switch ($objectClassName) {
            case Borrower::class:
                return [
                    FieldAlias::BORROWER_TYPE => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::BORROWER_TYPE
                    ),
                    FieldAlias::YOUNG_FARMER                                      => $this->faker->boolean,
                    FieldAlias::SUBSIDIARY                                        => $this->faker->boolean,
                    FieldAlias::CREATION_IN_PROGRESS                              => $this->faker->boolean,
                    FieldAlias::ECONOMICALLY_VIABLE                               => $this->faker->boolean,
                    FieldAlias::LISTED_ON_STOCK_MARKET                            => $this->faker->boolean,
                    FieldAlias::BENEFITING_PROFIT_TRANSFER                        => $this->faker->boolean,
                    FieldAlias::IN_NON_COOPERATIVE_JURISDICTION                   => $this->faker->boolean,
                    FieldAlias::SUBJECT_OF_UNPERFORMED_RECOVERY_ORDER             => $this->faker->boolean,
                    FieldAlias::SUBJECT_OF_RESTRUCTURING_PLAN                     => $this->faker->boolean,
                    FieldAlias::PROJECT_RECEIVED_FEAGA_OCM_FUNDING                => $this->faker->boolean,
                    FieldAlias::LOAN_SUPPORTING_DOCUMENTS_DATES_AFTER_APPLICATION => $this->faker->boolean,
                    FieldAlias::LOAN_ALLOWED_REFINANCE_RESTRUCTURE                => $this->faker->boolean,
                    FieldAlias::TRANSACTION_AFFECTED                              => $this->faker->boolean,
                    FieldAlias::COMPANY_NAME                                      => $this->faker->company,
                    FieldAlias::ACTIVITY_START_DATE                               => new DateTimeImmutable(),
                    FieldAlias::REGISTRATION_NUMBER                               => '12 23 45 678 987',
                    FieldAlias::LEGAL_FORM                                        => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::LEGAL_FORM
                    ),
                    FieldAlias::COMPANY_NAF_CODE => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::COMPANY_NAF_CODE
                    ),
                    FieldAlias::ACTIVITY_STREET     => $this->faker->streetAddress,
                    FieldAlias::ACTIVITY_CITY       => $this->faker->city,
                    FieldAlias::ACTIVITY_POST_CODE  => $this->faker->postcode,
                    FieldAlias::ACTIVITY_DEPARTMENT => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::ACTIVITY_DEPARTMENT
                    ),
                    FieldAlias::ACTIVITY_COUNTRY => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::ACTIVITY_COUNTRY
                    ),
                    FieldAlias::EMPLOYEES_NUMBER  => $this->faker->randomNumber(3),
                    FieldAlias::EXPLOITATION_SIZE => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::EXPLOITATION_SIZE
                    ),
                    FieldAlias::TURNOVER => new NullableMoney(
                        'EUR',
                        (string) $this->faker->randomNumber(6)
                    ),
                    FieldAlias::TOTAL_ASSETS => new NullableMoney(
                        'EUR',
                        (string) $this->faker->randomNumber(5)
                    ),
                    FieldAlias::TARGET_TYPE => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::TARGET_TYPE
                    ),
                ];

            case Project::class:
                return [
                    FieldAlias::INVESTMENT_THEMATIC => $this->programChoiceOptionRepository->findBy([
                        'program' => $program,
                        'field'   => $this->fieldRepository->findOneBy([
                            'fieldAlias' => FieldAlias::INVESTMENT_THEMATIC,
                        ]),
                    ]),
                    FieldAlias::INVESTMENT_TYPE => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::INVESTMENT_TYPE
                    ),
                    FieldAlias::PROJECT_DETAIL        => $this->faker->sentence,
                    FieldAlias::INVESTMENT_STREET     => $this->faker->streetAddress,
                    FieldAlias::INVESTMENT_POST_CODE  => $this->faker->postcode,
                    FieldAlias::INVESTMENT_CITY       => $this->faker->city,
                    FieldAlias::INVESTMENT_DEPARTMENT => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::INVESTMENT_DEPARTMENT
                    ),
                    FieldAlias::INVESTMENT_COUNTRY => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::INVESTMENT_COUNTRY
                    ),
                    FieldAlias::AID_INTENSITY => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::AID_INTENSITY
                    ),
                    FieldAlias::ADDITIONAL_GUARANTY => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::ADDITIONAL_GUARANTY
                    ),
                    FieldAlias::AGRICULTURAL_BRANCH => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::AGRICULTURAL_BRANCH
                    ),
                    FieldAlias::PROJECT_TOTAL_AMOUNT => new NullableMoney(
                        'EUR',
                        (string) $this->faker->randomNumber(4)
                    ),
                    FieldAlias::PROJECT_CONTRIBUTION => new NullableMoney(
                        'EUR',
                        (string) $this->faker->randomNumber(4)
                    ),
                    FieldAlias::ELIGIBLE_FEI_CREDIT => new NullableMoney(
                        'EUR',
                        (string) $this->faker->randomNumber(4)
                    ),
                    FieldAlias::TOTAL_FEI_CREDIT => new NullableMoney(
                        'EUR',
                        (string) $this->faker->randomNumber(4)
                    ),
                    FieldAlias::TANGIBLE_FEI_CREDIT => new NullableMoney(
                        'EUR',
                        (string) $this->faker->randomNumber(4)
                    ),
                    FieldAlias::INTANGIBLE_FEI_CREDIT => new NullableMoney(
                        'EUR',
                        (string) $this->faker->randomNumber(4)
                    ),
                    FieldAlias::CREDIT_EXCLUDING_FEI => new NullableMoney(
                        'EUR',
                        (string) $this->faker->randomNumber(4)
                    ),
                    FieldAlias::PROJECT_GRANT => new NullableMoney(
                        'EUR',
                        (string) $this->faker->randomNumber(4)
                    ),
                    FieldAlias::LAND_VALUE => new NullableMoney(
                        'EUR',
                        (string) $this->faker->randomNumber(4)
                    ),
                ];

            case FinancingObject::class:
                return [
                    FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => $this->faker->boolean,
                    FieldAlias::FINANCING_OBJECT_TYPE          => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::FINANCING_OBJECT_TYPE
                    ),
                    FieldAlias::LOAN_NAF_CODE => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::LOAN_NAF_CODE
                    ),
                    FieldAlias::LOAN_TYPE => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::LOAN_TYPE
                    ),
                    FieldAlias::LOAN_DURATION    => $this->faker->numberBetween(1, 24),
                    FieldAlias::LOAN_DEFERRAL    => $this->faker->numberBetween(0, 12),
                    FieldAlias::LOAN_PERIODICITY => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::LOAN_PERIODICITY
                    ),
                    FieldAlias::BFR_VALUE           => new NullableMoney('EUR', (string) $this->faker->randomNumber(5)),
                    FieldAlias::INVESTMENT_LOCATION => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::INVESTMENT_LOCATION
                    ),
                    FieldAlias::PRODUCT_CATEGORY_CODE => $this->findProgramChoiceOption(
                        $program,
                        FieldAlias::PRODUCT_CATEGORY_CODE
                    ),
                ];
        }

        return [];
    }
}
