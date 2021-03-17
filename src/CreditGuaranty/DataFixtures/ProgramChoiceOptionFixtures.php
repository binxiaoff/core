<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\{AbstractFixtures, DumpedDataFixture};
use Unilend\CreditGuaranty\Entity\{Constant\FieldAlias, Program, ProgramChoiceOption};
use Unilend\CreditGuaranty\Repository\FieldConfigurationRepository;

class ProgramChoiceOptionFixtures extends AbstractFixtures implements DependentFixtureInterface
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
        $programReferences = [
            ProgramFixtures::REFERENCE_CANCELLED,
            ProgramFixtures::REFERENCE_COMMERCIALIZED,
            ProgramFixtures::REFERENCE_DRAFT,
            ProgramFixtures::REFERENCE_PAUSED,
        ];

        $descriptions   = [
            'Installé depuis plus de 7 ans', 'Installé depuis moins de 7 ans',
            'En reconversion Bio', 'Agriculture céréalière',
            'Agriculture bovine', 'Producteur de lait',
            'Exploitant céréalier', 'Ostréiculteur',
            'Apiculteur', 'Agriculture durable',
            'Vignoble', 'Jeune agriculteur de moins de 30 ans',
            'Installé depuis moins de 10 ans', 'Installé depuis plus de 10 ans',
        ];
        $nbDescriptions     = count($descriptions);
        $borrowerTypeConfig = $this->fieldConfigurationRepository->findOneBy(['fieldAlias' => FieldAlias::BORROWER_TYPE]);

        foreach ($programReferences as $programReference) {
            /** @var Program $program */
            $program = $this->getReference($programReference);

            for ($i = 0; $i <= rand(0, $nbDescriptions - 1); $i++) {
                $manager->persist(new ProgramChoiceOption($program, $descriptions[$i], $borrowerTypeConfig));
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
          DumpedDataFixture::class,
        ];
    }
}
