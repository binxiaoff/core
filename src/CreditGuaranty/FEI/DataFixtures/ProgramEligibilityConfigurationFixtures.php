<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Repository\ProgramChoiceOptionRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConfigurationRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProgramEligibilityConfigurationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private const INELIGIBLE_FIELDS = [
        // profile
        'creation_in_progress' => true,
        'legal_form'           => 'SELAS',
        // project
        'receiving_grant' => false,
        'aid_intensity'   => '0.20',
        // loan
        'supporting_generations_renewal' => false,
        'loan_type'                      => 'stand_by',
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
            ProgramChoiceOptionFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $allProgramEligibilities          = $this->programEligibilityRepository->findAll();
        $programEligibilityConfigurations = $this->programEligibilityConfigurationRepository->findBy(['programEligibility' => $allProgramEligibilities]);

        foreach ($programEligibilityConfigurations as $programEligibilityConfiguration) {
            $field = $programEligibilityConfiguration->getProgramEligibility()->getField();

            if (false === \array_key_exists($field->getFieldAlias(), self::INELIGIBLE_FIELDS)) {
                $programEligibilityConfiguration->setEligible(true);

                continue;
            }

            $ineligibleValue = self::INELIGIBLE_FIELDS[$field->getFieldAlias()];

            if (Field::TYPE_BOOL === $field->getType()) {
                if ((bool) $programEligibilityConfiguration->getValue() === $ineligibleValue) {
                    $programEligibilityConfiguration->setEligible(false);
                } else {
                    $programEligibilityConfiguration->setEligible(true);
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
                } else {
                    $programEligibilityConfiguration->setEligible(true);
                }
            }
        }

        $manager->flush();
    }
}
