<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Provider\Miscellaneous;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Repository\{ProgramEligibilityConfigurationRepository, ProgramEligibilityRepository};

class ProgramEligibilityConfigurationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private const CHANCE_OF_ELIGIBILITY = 80;

    /** @var ProgramEligibilityRepository */
    private ProgramEligibilityRepository $programEligibilityRepository;
    /** @var ProgramEligibilityConfigurationRepository */
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;

    /**
     * @param TokenStorageInterface                     $tokenStorage
     * @param ProgramEligibilityRepository              $programEligibilityRepository
     * @param ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        ProgramEligibilityRepository $programEligibilityRepository,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository
    ) {
        parent::__construct($tokenStorage);
        $this->programEligibilityRepository  = $programEligibilityRepository;
        $this->programEligibilityConfigurationRepository = $programEligibilityConfigurationRepository;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $allProgramEligibilities = $this->programEligibilityRepository->findAll();
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
