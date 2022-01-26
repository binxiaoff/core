<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\Entity\Constant\LegalForm;
use KLS\Core\Entity\Constant\LoanType;
use KLS\Core\Entity\Constant\MathOperator;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityCondition;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramChoiceOptionRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConfigurationRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProgramEligibilityConditionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private FieldRepository $fieldRepository;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private ProgramEligibilityRepository $programEligibilityRepository;
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        FieldRepository $fieldRepository,
        ProgramChoiceOptionRepository $programChoiceOptionRepository,
        ProgramEligibilityRepository $programEligibilityRepository,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository
    ) {
        parent::__construct($tokenStorage);
        $this->fieldRepository                           = $fieldRepository;
        $this->programChoiceOptionRepository             = $programChoiceOptionRepository;
        $this->programEligibilityRepository              = $programEligibilityRepository;
        $this->programEligibilityConfigurationRepository = $programEligibilityConfigurationRepository;
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            ProgramEligibilityConfigurationFixtures::class,
        ];
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $programs = $this->getReferences([
            ProgramFixtures::PROGRAM_AGRICULTURE_COMMERCIALIZED,
            ProgramFixtures::PROGRAM_AGRICULTURE_PAUSED,
            ProgramFixtures::PROGRAM_AGRICULTURE_ARCHIVED,
            ProgramFixtures::PROGRAM_CORPORATE_COMMERCIALIZED,
            ProgramFixtures::PROGRAM_CORPORATE_PAUSED,
            ProgramFixtures::PROGRAM_CORPORATE_ARCHIVED,
        ]);

        /** @var Program $program */
        foreach ($programs as $program) {
            foreach ($this->loadData($program) as $conditionItems) {
                $programEligibility = $this->programEligibilityRepository->findOneBy([
                    'program' => $program,
                    'field'   => $conditionItems['field'],
                ]);

                if (false === ($programEligibility instanceof ProgramEligibility)) {
                    continue;
                }

                foreach ($conditionItems['conditions'] as $condition) {
                    $programEligibilityConfiguration = $this->getProgramEligibilityConfiguration(
                        $programEligibility,
                        $condition['configurationValue'],
                        $condition['configurationOption']
                    );

                    if (false === ($programEligibilityConfiguration instanceof ProgramEligibilityConfiguration)) {
                        continue;
                    }

                    $programEligibilityCondition = new ProgramEligibilityCondition(
                        $programEligibilityConfiguration,
                        $condition['leftOperandField'],
                        $condition['rightOperandField'],
                        $condition['operator'],
                        $condition['type']
                    );

                    if (null !== $condition['value']) {
                        $programEligibilityCondition->setValue((string) $condition['value']);
                    }
                    if (
                        null === $programEligibilityCondition->getValue()
                        && false === empty($condition['programChoiceOptions'])
                    ) {
                        foreach ($condition['programChoiceOptions'] as $programChoiceOption) {
                            $programEligibilityCondition->addProgramChoiceOption($programChoiceOption);
                        }
                    }

                    $manager->persist($programEligibilityCondition);
                }
            }

            $manager->flush();
        }
    }

    private function loadData(Program $program): iterable
    {
        $creationInProgressField = $this->fieldRepository->findOneBy([
            'fieldAlias' => FieldAlias::CREATION_IN_PROGRESS,
        ]);
        $loanTypeField = $this->fieldRepository->findOneBy([
            'fieldAlias' => FieldAlias::LOAN_TYPE,
        ]);
        // operand fields
        $legalFormField = $this->fieldRepository->findOneBy([
            'fieldAlias' => FieldAlias::LEGAL_FORM,
        ]);
        $tangibleFeiCreditField = $this->fieldRepository->findOneBy([
            'fieldAlias' => FieldAlias::TANGIBLE_FEI_CREDIT,
        ]);
        $intangibleFeiCreditField = $this->fieldRepository->findOneBy([
            'fieldAlias' => FieldAlias::INTANGIBLE_FEI_CREDIT,
        ]);
        $investmentThematicField = $this->fieldRepository->findOneBy([
            'fieldAlias' => FieldAlias::INVESTMENT_THEMATIC,
        ]);
        $loanDurationField = $this->fieldRepository->findOneBy([
            'fieldAlias' => FieldAlias::LOAN_DURATION,
        ]);
        $youngFarmerField = $this->fieldRepository->findOneBy([
            'fieldAlias' => FieldAlias::YOUNG_FARMER,
        ]);

        // programChoiceOptions
        $investmentThematicOptions = $this->programChoiceOptionRepository->findBy([
            'program' => $program,
            'field'   => $investmentThematicField,
        ]);
        $legalFormOptions = $this->programChoiceOptionRepository->findBy([
            'program'     => $program,
            'field'       => $legalFormField,
            'description' => [LegalForm::SARL, LegalForm::SA, LegalForm::SAS],
        ]);
        $signatureCommitmentOption = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::LOAN_TYPE]),
            'description' => LoanType::SIGNATURE_COMMITMENT,
        ]);

        yield FieldAlias::CREATION_IN_PROGRESS => [
            'field'      => $creationInProgressField,
            'conditions' => [
                [
                    'configurationValue'   => '0',
                    'configurationOption'  => null,
                    'type'                 => ProgramEligibilityCondition::VALUE_TYPE_LIST,
                    'leftOperandField'     => $legalFormField,
                    'rightOperandField'    => null,
                    'operator'             => MathOperator::INFERIOR,
                    'value'                => null,
                    'programChoiceOptions' => $legalFormOptions,
                ],
            ],
        ];
        yield FieldAlias::TANGIBLE_FEI_CREDIT => [
            'field'      => $tangibleFeiCreditField,
            'conditions' => [
                [
                    'configurationValue'   => null,
                    'configurationOption'  => null,
                    'type'                 => ProgramEligibilityCondition::VALUE_TYPE_RATE,
                    'leftOperandField'     => $tangibleFeiCreditField,
                    'rightOperandField'    => $intangibleFeiCreditField,
                    'operator'             => MathOperator::SUPERIOR,
                    'value'                => 0.10,
                    'programChoiceOptions' => [],
                ],
            ],
        ];
        yield FieldAlias::LOAN_DURATION => [
            'field'      => $loanDurationField,
            'conditions' => [
                [
                    'configurationValue'   => null,
                    'configurationOption'  => null,
                    'type'                 => ProgramEligibilityCondition::VALUE_TYPE_VALUE,
                    'leftOperandField'     => $loanDurationField,
                    'rightOperandField'    => null,
                    'operator'             => MathOperator::INFERIOR_OR_EQUAL,
                    'value'                => 70,
                    'programChoiceOptions' => [],
                ],
            ],
        ];

        // ProgramChoiceOption(s) may be missing because they are created depending ProgramEligibilities
        if (false === empty($investmentThematicOptions)) {
            yield FieldAlias::INVESTMENT_THEMATIC => [
                'field'      => $investmentThematicField,
                'conditions' => [
                    [
                        'configurationValue'   => null,
                        'configurationOption'  => $investmentThematicOptions[1],
                        'type'                 => ProgramEligibilityCondition::VALUE_TYPE_LIST,
                        'leftOperandField'     => $investmentThematicField,
                        'rightOperandField'    => null,
                        'operator'             => MathOperator::EQUAL,
                        'value'                => null,
                        'programChoiceOptions' => [$investmentThematicOptions[2], $investmentThematicOptions[3]],
                    ],
                ],
            ];
        }
        if (null !== $signatureCommitmentOption) {
            yield FieldAlias::LOAN_TYPE => [
                'field'      => $loanTypeField,
                'conditions' => [
                    [
                        'configurationValue'   => null,
                        'configurationOption'  => $signatureCommitmentOption,
                        'type'                 => ProgramEligibilityCondition::VALUE_TYPE_BOOL,
                        'leftOperandField'     => $youngFarmerField,
                        'rightOperandField'    => null,
                        'operator'             => MathOperator::EQUAL,
                        'value'                => true,
                        'programChoiceOptions' => [],
                    ],
                ],
            ];
        }
    }

    private function getProgramEligibilityConfiguration(
        ProgramEligibility $programEligibility,
        ?string $configurationValue,
        ?ProgramChoiceOption $configurationOption
    ): ?ProgramEligibilityConfiguration {
        if ($configurationOption instanceof ProgramChoiceOption) {
            return $this->programEligibilityConfigurationRepository->findOneBy([
                'programEligibility'  => $programEligibility,
                'programChoiceOption' => $configurationOption,
            ]);
        }

        return $this->programEligibilityConfigurationRepository->findOneBy([
            'programEligibility' => $programEligibility,
            'value'              => $configurationValue,
        ]);
    }
}
