<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\Core\Entity\Constant\LegalForm;
use KLS\Core\Entity\Constant\LoanType;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Repository\ProgramChoiceOptionRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConfigurationRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProgramEligibilityConfigurationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private const INELIGIBLE_FIELDS = [
        // profile
        FieldAlias::CREATION_IN_PROGRESS => true,
        FieldAlias::LEGAL_FORM           => LegalForm::SELAS,
        // project
        FieldAlias::RECEIVING_GRANT => false,
        FieldAlias::AID_INTENSITY   => '0.20',
        // loan
        FieldAlias::SUPPORTING_GENERATIONS_RENEWAL => false,
        FieldAlias::LOAN_TYPE                      => LoanType::STAND_BY,
    ];

    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private ProgramEligibilityRepository $programEligibilityRepository;
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ProgramChoiceOptionRepository $programChoiceOptionRepository,
        ProgramEligibilityRepository $programEligibilityRepository,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository
    ) {
        parent::__construct($tokenStorage);
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
            ProgramEligibilityFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $allProgramEligibilities          = $this->programEligibilityRepository->findAll();
        $programEligibilityConfigurations = $this->programEligibilityConfigurationRepository->findBy([
            'programEligibility' => $allProgramEligibilities,
        ]);

        foreach ($programEligibilityConfigurations as $programEligibilityConfiguration) {
            $field = $programEligibilityConfiguration->getProgramEligibility()->getField();

            if (false === \array_key_exists($field->getFieldAlias(), self::INELIGIBLE_FIELDS)) {
                // a ProgramEligibilityConfiguration is initialized while creating a ProgramEligibility
                // and is eligible by default
                continue;
            }

            $ineligibleValue = self::INELIGIBLE_FIELDS[$field->getFieldAlias()];

            if (Field::TYPE_BOOL === $field->getType()) {
                if ((bool) $programEligibilityConfiguration->getValue() === $ineligibleValue) {
                    $programEligibilityConfiguration->setEligible(false);
                }
            }

            if (Field::TYPE_LIST === $field->getType()) {
                $programChoiceOption = $this->programChoiceOptionRepository->findOneBy([
                    'program'     => $programEligibilityConfiguration->getProgramEligibility()->getProgram(),
                    'field'       => $field,
                    'description' => $ineligibleValue,
                ]);

                if ($programChoiceOption === $programEligibilityConfiguration->getProgramChoiceOption()) {
                    $programEligibilityConfiguration->setEligible(false);
                }
            }
        }

        $manager->flush();
    }
}
