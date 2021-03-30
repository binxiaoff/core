<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\CreditGuaranty\Entity\{Program, ProgramEligibility};
use Unilend\CreditGuaranty\Repository\FieldRepository;

class ProgramEligibilityFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private FieldRepository $fieldRepository;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param FieldRepository       $fieldRepository
     */
    public function __construct(TokenStorageInterface $tokenStorage, FieldRepository $fieldRepository)
    {
        parent::__construct($tokenStorage);
        $this->fieldRepository = $fieldRepository;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $fields = $this->fieldRepository->findAll();

        $programReferences = [
            ProgramFixtures::REFERENCE_CANCELLED,
            ProgramFixtures::REFERENCE_COMMERCIALIZED,
            ProgramFixtures::REFERENCE_DRAFT,
            ProgramFixtures::REFERENCE_PAUSED,
        ];

        foreach ($programReferences as $programReference) {
            /** @var Program $program */
            $program = $this->getReference($programReference);

            foreach ($fields as $field) {
                $programEligibility = new ProgramEligibility($program, $field);
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
            FieldFixtures::class,
        ];
    }
}
