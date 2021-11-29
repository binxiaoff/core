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
            ReservationFixtures::class,
        ];
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Program $program */
        $program = $this->getReference(ProgramFixtures::REFERENCE_COMMERCIALIZED);

        foreach ($this->loadData($program) as $fieldAlias => $conditionItems) {
            $programEligibility = $this->programEligibilityRepository->findOneBy([
                'program' => $program,
                'field'   => $this->fieldRepository->findOneBy(['fieldAlias' => $fieldAlias]),
            ]);

            foreach ($conditionItems as $condition) {
                $programEligibilityConfiguration = $this->getProgramEligibilityConfiguration(
                    $programEligibility,
                    $condition['configurationValue'],
                    $condition['configurationOption']
                );

                if (false === ($programEligibilityConfiguration instanceof ProgramEligibilityConfiguration)) {
                    continue;
                }

                $leftOperandField = $this->fieldRepository->findOneBy([
                    'fieldAlias' => $condition['leftOperandAlias'],
                ]);
                $rightOperandField = (null !== $condition['rightOperandAlias'])
                    ? $this->fieldRepository->findOneBy(['fieldAlias' => $condition['rightOperandAlias']])
                    : null
                ;

                $programEligibilityCondition = new ProgramEligibilityCondition(
                    $programEligibilityConfiguration,
                    $leftOperandField,
                    $rightOperandField,
                    $condition['operator'],
                    $condition['type']
                );

                if (null !== $condition['value']) {
                    $programEligibilityCondition->setValue((string) $condition['value']);
                } else {
                    foreach ($condition['programChoiceOptions'] as $programChoiceOption) {
                        $programEligibilityCondition->addProgramChoiceOption($programChoiceOption);
                    }
                }

                $manager->persist($programEligibilityCondition);
            }
        }

        $manager->flush();
    }

    private function loadData(Program $program): array
    {
        // programChoiceOptions
        $investmentThematicOptions = $this->programChoiceOptionRepository->findBy([
            'program' => $program,
            'field'   => $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::INVESTMENT_THEMATIC]),
        ]);
        $sarlOption = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::LEGAL_FORM]),
            'description' => LegalForm::SARL,
        ]);
        $saOption = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::LEGAL_FORM]),
            'description' => LegalForm::SA,
        ]);
        $sasOption = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::LEGAL_FORM]),
            'description' => LegalForm::SAS,
        ]);
        $signatureCommitmentOption = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::LOAN_TYPE]),
            'description' => LoanType::SIGNATURE_COMMITMENT,
        ]);

        return [
            FieldAlias::CREATION_IN_PROGRESS => [
                [
                    'configurationValue'   => '0',
                    'configurationOption'  => null,
                    'type'                 => ProgramEligibilityCondition::VALUE_TYPE_LIST,
                    'leftOperandAlias'     => FieldAlias::LEGAL_FORM,
                    'rightOperandAlias'    => null,
                    'operator'             => MathOperator::INFERIOR,
                    'value'                => null,
                    'programChoiceOptions' => [$sarlOption, $saOption, $sasOption],
                ],
            ],
            FieldAlias::TANGIBLE_FEI_CREDIT => [
                [
                    'configurationValue'   => null,
                    'configurationOption'  => null,
                    'type'                 => ProgramEligibilityCondition::VALUE_TYPE_RATE,
                    'leftOperandAlias'     => FieldAlias::TANGIBLE_FEI_CREDIT,
                    'rightOperandAlias'    => FieldAlias::INTANGIBLE_FEI_CREDIT,
                    'operator'             => MathOperator::SUPERIOR,
                    'value'                => 0.10,
                    'programChoiceOptions' => [],
                ],
            ],
            FieldAlias::INVESTMENT_THEMATIC => [
                [
                    'configurationValue'   => null,
                    'configurationOption'  => $investmentThematicOptions[1],
                    'type'                 => ProgramEligibilityCondition::VALUE_TYPE_LIST,
                    'leftOperandAlias'     => FieldAlias::INVESTMENT_THEMATIC,
                    'rightOperandAlias'    => null,
                    'operator'             => MathOperator::EQUAL,
                    'value'                => null,
                    'programChoiceOptions' => [$investmentThematicOptions[2], $investmentThematicOptions[3]],
                ],
            ],
            FieldAlias::LOAN_DURATION => [
                [
                    'configurationValue'   => null,
                    'configurationOption'  => null,
                    'type'                 => ProgramEligibilityCondition::VALUE_TYPE_VALUE,
                    'leftOperandAlias'     => FieldAlias::LOAN_DURATION,
                    'rightOperandAlias'    => null,
                    'operator'             => MathOperator::INFERIOR_OR_EQUAL,
                    'value'                => 70,
                    'programChoiceOptions' => [],
                ],
            ],
            FieldAlias::LOAN_TYPE => [
                [
                    'configurationValue'   => null,
                    'configurationOption'  => $signatureCommitmentOption,
                    'type'                 => ProgramEligibilityCondition::VALUE_TYPE_BOOL,
                    'leftOperandAlias'     => FieldAlias::YOUNG_FARMER,
                    'rightOperandAlias'    => null,
                    'operator'             => MathOperator::EQUAL,
                    'value'                => true,
                    'programChoiceOptions' => [],
                ],
            ],
        ];
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
