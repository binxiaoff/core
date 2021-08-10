<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConfigurationRepository;
use KLS\Test\Core\DataFixtures\AbstractFixtures;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProgramEligibilityConfigurationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository
    ) {
        parent::__construct($tokenStorage);
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
        $programEligibilityConfigurations = $this->programEligibilityConfigurationRepository->findAll();

        foreach ($programEligibilityConfigurations as $programEligibilityConfiguration) {
            if (Field::VALUE_BOOL_NO === $programEligibilityConfiguration->getValue()) {
                $programEligibilityConfiguration->setEligible(false);
                $manager->persist($programEligibilityConfiguration);
            }
        }

        $manager->flush();
    }
}
