<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\{AbstractFixtures, DumpedDataFixture};
use Unilend\CreditGuaranty\Entity\{Program, ProgramEligibility};
use Unilend\CreditGuaranty\Repository\FieldConfigurationRepository;

class ProgramEligibilityFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private FieldConfigurationRepository $fieldConfigurationRepository;

    /**
     * @param TokenStorageInterface        $tokenStorage
     * @param FieldConfigurationRepository $fieldConfigurationRepository
     */
    public function __construct(TokenStorageInterface $tokenStorage, FieldConfigurationRepository $fieldConfigurationRepository)
    {
        parent::__construct($tokenStorage);
        $this->fieldConfigurationRepository = $fieldConfigurationRepository;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $fieldConfigurations = $this->fieldConfigurationRepository->findAll();

        $programReferences = [
            ProgramFixtures::REFERENCE_CANCELLED,
            ProgramFixtures::REFERENCE_COMMERCIALIZED,
            ProgramFixtures::REFERENCE_DRAFT,
            ProgramFixtures::REFERENCE_PAUSED,
        ];

        foreach ($programReferences as $programReference) {
            /** @var Program $program */
            $program = $this->getReference($programReference);

            foreach ($fieldConfigurations as $fieldConfiguration) {
                $programEligibility = new ProgramEligibility($program, $fieldConfiguration);
                $manager->persist($programEligibility);
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
            ProgramFixtures::class,
            ProgramChoiceOptionFixtures::class,
            DumpedDataFixture::class,
        ];
    }
}
