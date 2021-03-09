<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Provider\Miscellaneous;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\CreditGuaranty\Entity\{ConstantList\EligibilityCriteria, ProgramEligibilityConfiguration};
use Unilend\CreditGuaranty\Repository\{ProgramChoiceOptionRepository, ProgramEligibilityRepository};

class ProgramEligibilityConfigurationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private const CHANCE_OF_ELIGIBILITY = 80;

    /** @var ProgramEligibilityRepository */
    private ProgramEligibilityRepository $programEligibilityRepository;
    /** @var ProgramChoiceOptionRepository */
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;

    /**
     * @param TokenStorageInterface         $tokenStorage
     * @param ProgramEligibilityRepository  $programEligibilityRepository
     * @param ProgramChoiceOptionRepository $programChoiceOptionRepository
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        ProgramEligibilityRepository $programEligibilityRepository,
        ProgramChoiceOptionRepository $programChoiceOptionRepository
    ) {
        parent::__construct($tokenStorage);
        $this->programEligibilityRepository  = $programEligibilityRepository;
        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $allProgramEligibilities = $this->programEligibilityRepository->findAll();

        foreach ($allProgramEligibilities as $programEligibility) {
            $eligibilityCriteria = $programEligibility->getEligibilityCriteria();
            $programEligibilityConfigurations = [];
            switch ($eligibilityCriteria->getType()) {
                case EligibilityCriteria::TYPE_BOOL:
                    $programEligibilityConfigurations = [
                        new ProgramEligibilityConfiguration(
                            $programEligibility,
                            null,
                            EligibilityCriteria::VALUE_BOOL_YES,
                            Miscellaneous::boolean(self::CHANCE_OF_ELIGIBILITY)
                        ),
                        new ProgramEligibilityConfiguration(
                            $programEligibility,
                            null,
                            EligibilityCriteria::VALUE_BOOL_NO,
                            Miscellaneous::boolean(self::CHANCE_OF_ELIGIBILITY)
                        ),
                    ];
                    break;
                case EligibilityCriteria::TYPE_LIST:
                    if ($eligibilityCriteria->getTargetPropertyAccessPath()) {
                        $choiceOptions = $this->programChoiceOptionRepository->findBy([
                            'fieldAlias' => $eligibilityCriteria->getFieldAlias(),
                            'program' => $programEligibility->getProgram(),
                        ]);

                        foreach ($choiceOptions as $choiceOption) {
                            $programEligibilityConfigurations[] = new ProgramEligibilityConfiguration(
                                $programEligibility,
                                $choiceOption,
                                null,
                                Miscellaneous::boolean(self::CHANCE_OF_ELIGIBILITY)
                            );
                        }
                    }
                    break;
                case EligibilityCriteria::TYPE_OTHER:
                    $programEligibilityConfigurations[] = new ProgramEligibilityConfiguration(
                        $programEligibility,
                        null,
                        null,
                        Miscellaneous::boolean(self::CHANCE_OF_ELIGIBILITY)
                    );
                    break;
                default:
                    break;
            }

            foreach ($programEligibilityConfigurations as $programEligibilityConfiguration) {
                $manager->persist($programEligibilityConfiguration);
            }
        }
        $manager->flush();
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
}
