<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\Entity\Constant\MathOperator;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityCondition;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConfigurationRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityRepository;
use KLS\Test\Core\DataFixtures\AbstractFixtures;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProgramEligibilityConditionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private FieldRepository $fieldRepository;
    private ProgramEligibilityRepository $programEligibilityRepository;
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        FieldRepository $fieldRepository,
        ProgramEligibilityRepository $programEligibilityRepository,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository
    ) {
        parent::__construct($tokenStorage);
        $this->fieldRepository                           = $fieldRepository;
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

        foreach ($this->loadData() as $fieldAlias => $conditionItems) {
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

    private function loadData(): array
    {
        return [
            FieldAlias::TURNOVER => [
                [
                    'configurationValue'   => null,
                    'configurationOption'  => null,
                    'type'                 => ProgramEligibilityCondition::VALUE_TYPE_RATE,
                    'leftOperandAlias'     => FieldAlias::TURNOVER,
                    'rightOperandAlias'    => FieldAlias::TOTAL_ASSETS,
                    'operator'             => MathOperator::INFERIOR,
                    'value'                => 0.2,
                    'programChoiceOptions' => [],
                ],
            ],
            FieldAlias::TOTAL_FEI_CREDIT => [
                [
                    'configurationValue'   => null,
                    'configurationOption'  => null,
                    'type'                 => ProgramEligibilityCondition::VALUE_TYPE_RATE,
                    'leftOperandAlias'     => FieldAlias::TOTAL_FEI_CREDIT,
                    'rightOperandAlias'    => FieldAlias::CREDIT_EXCLUDING_FEI,
                    'operator'             => MathOperator::SUPERIOR,
                    'value'                => 0.4,
                    'programChoiceOptions' => [],
                ],
            ],
            FieldAlias::LOAN_DURATION => [
                [
                    'configurationValue'   => null,
                    'configurationOption'  => null,
                    'type'                 => ProgramEligibilityCondition::VALUE_TYPE_VALUE,
                    'leftOperandAlias'     => FieldAlias::LOAN_DURATION,
                    'rightOperandAlias'    => null,
                    'operator'             => MathOperator::SUPERIOR_OR_EQUAL,
                    'value'                => 4,
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
