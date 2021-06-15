<?php

declare(strict_types=1);

namespace Unilend\Test\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityConfigurationRepository;
use Unilend\Test\Core\DataFixtures\AbstractFixtures;

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
