<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\{AbstractFixtures, DumpedDataFixture};
use Unilend\CreditGuaranty\Entity\{Program, ProgramEligibility};
use Unilend\CreditGuaranty\Repository\EligibilityCriteriaRepository;

class ProgramEligibilityFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /** @var EligibilityCriteriaRepository */
    private EligibilityCriteriaRepository $eligibilityCriteriaRepository;

    /**
     * @param TokenStorageInterface         $tokenStorage
     * @param EligibilityCriteriaRepository $eligibilityCriteriaRepository
     */
    public function __construct(TokenStorageInterface $tokenStorage, EligibilityCriteriaRepository $eligibilityCriteriaRepository)
    {
        parent::__construct($tokenStorage);
        $this->eligibilityCriteriaRepository = $eligibilityCriteriaRepository;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $allProgramCriteria = $this->eligibilityCriteriaRepository->findAll();

        $programReferences = [
            ProgramFixtures::REFERENCE_CANCELLED,
            ProgramFixtures::REFERENCE_COMMERCIALIZED,
            ProgramFixtures::REFERENCE_DRAFT,
            ProgramFixtures::REFERENCE_PAUSED,
        ];

        foreach ($programReferences as $programReference) {
            /** @var Program $program */
            $program = $this->getReference($programReference);

            foreach ($allProgramCriteria as $eligibilityCriteria) {
                $programEligibility = new ProgramEligibility($program, $eligibilityCriteria);
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
            DumpedDataFixture::class,
        ];
    }
}
