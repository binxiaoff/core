<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\Entity\Constant\MathOperator;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityCondition;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConfigurationRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProgramEligibilityConditionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private FieldRepository $fieldRepository;
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;
    private ProgramEligibilityRepository $programEligibilityRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        FieldRepository $fieldRepository,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository,
        ProgramEligibilityRepository $programEligibilityRepository
    ) {
        parent::__construct($tokenStorage);
        $this->fieldRepository                           = $fieldRepository;
        $this->programEligibilityConfigurationRepository = $programEligibilityConfigurationRepository;
        $this->programEligibilityRepository              = $programEligibilityRepository;
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
        $conditions = $this->createConditions();

        /** @var Program $program */
        $program                          = $this->getReference(ProgramFixtures::REFERENCE_COMMERCIALIZED);
        $programEligibilities             = $this->programEligibilityRepository->findBy(['program' => $program]);
        $programEligibilityConfigurations = $this->programEligibilityConfigurationRepository->findBy([
            'programEligibility' => $programEligibilities,
        ]);

        foreach ($programEligibilityConfigurations as $programEligibilityConfiguration) {
            $fieldAlias = $programEligibilityConfiguration->getProgramEligibility()->getField()->getFieldAlias();

            if (false === \array_key_exists($fieldAlias, $conditions)) {
                continue;
            }

            foreach ($conditions[$fieldAlias] as $condition) {
                $programEligibilityCondition = (new ProgramEligibilityCondition(
                    $programEligibilityConfiguration,
                    $condition['leftOperand'],
                    $condition['rightOperand'],
                    $condition['operator'],
                    $condition['type']
                ))->setValue((string) $condition['value']);
                $manager->persist($programEligibilityCondition);
            }
        }

        $manager->flush();
    }

    private function createConditions(): array
    {
        // comparable person
        $employeesNumberField = $this->fieldRepository->findOneBy([
            'fieldAlias' => FieldAlias::EMPLOYEES_NUMBER,
        ]);
        // comparable money
        $tangibleFeiCreditField = $this->fieldRepository->findOneBy([
            'fieldAlias' => FieldAlias::TANGIBLE_FEI_CREDIT,
        ]);
        $intangibleFeiCreditField = $this->fieldRepository->findOneBy([
            'fieldAlias' => FieldAlias::INTANGIBLE_FEI_CREDIT,
        ]);
        // comparable month
        $loanDurationField = $this->fieldRepository->findOneBy([
            'fieldAlias' => FieldAlias::LOAN_DURATION,
        ]);

        return [
            FieldAlias::LEGAL_FORM => [
                [
                    'type'         => ProgramEligibilityCondition::VALUE_TYPE_VALUE,
                    'leftOperand'  => $employeesNumberField,
                    'rightOperand' => null,
                    'operator'     => MathOperator::INFERIOR,
                    'value'        => 500.00,
                ],
                [
                    'type'         => ProgramEligibilityCondition::VALUE_TYPE_RATE,
                    'leftOperand'  => $intangibleFeiCreditField,
                    'rightOperand' => $tangibleFeiCreditField,
                    'operator'     => MathOperator::INFERIOR,
                    'value'        => 0.80,
                ],
            ],
            FieldAlias::PROJECT_TOTAL_AMOUNT => [
                [
                    'type'         => ProgramEligibilityCondition::VALUE_TYPE_VALUE,
                    'leftOperand'  => $loanDurationField,
                    'rightOperand' => null,
                    'operator'     => MathOperator::EQUAL,
                    'value'        => 4,
                ],
            ],
        ];
    }
}
