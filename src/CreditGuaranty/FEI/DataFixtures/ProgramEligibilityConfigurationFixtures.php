<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Provider\Miscellaneous;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConfigurationRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProgramEligibilityConfigurationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private const CHANCE_OF_ELIGIBILITY = 80;

    private ProgramEligibilityRepository $programEligibilityRepository;
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ProgramEligibilityRepository $programEligibilityRepository,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository
    ) {
        parent::__construct($tokenStorage);
        $this->programEligibilityRepository              = $programEligibilityRepository;
        $this->programEligibilityConfigurationRepository = $programEligibilityConfigurationRepository;
    }

    public function load(ObjectManager $manager): void
    {
        $allProgramEligibilities          = $this->programEligibilityRepository->findAll();
        $programEligibilityConfigurations = $this->programEligibilityConfigurationRepository->findBy(['programEligibility' => $allProgramEligibilities]);

        foreach ($programEligibilityConfigurations as $programEligibilityConfiguration) {
            if (Field::TYPE_OTHER !== $programEligibilityConfiguration->getProgramEligibility()->getField()->getType()) {
                $programEligibilityConfiguration->setEligible(Miscellaneous::boolean(self::CHANCE_OF_ELIGIBILITY));
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
