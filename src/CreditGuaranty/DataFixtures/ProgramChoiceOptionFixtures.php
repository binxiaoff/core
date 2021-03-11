<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\{AbstractFixtures, DumpedDataFixture};
use Unilend\CreditGuaranty\Entity\{Constant\EligibilityFieldAlias, Program, ProgramChoiceOption};
use Unilend\CreditGuaranty\Repository\EligibilityCriteriaRepository;

class ProgramChoiceOptionFixtures extends AbstractFixtures implements DependentFixtureInterface
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
        $programReferences = [
            ProgramFixtures::REFERENCE_CANCELLED,
            ProgramFixtures::REFERENCE_COMMERCIALIZED,
            ProgramFixtures::REFERENCE_DRAFT,
            ProgramFixtures::REFERENCE_PAUSED,
        ];

        foreach ($programReferences as $programReference) {
            /** @var Program $program */
            $program = $this->getReference($programReference);
            $borrowerType = $this->eligibilityCriteriaRepository->findOneBy(['fieldAlias' => EligibilityFieldAlias::BORROWER_TYPE]);

            $manager->persist(new ProgramChoiceOption($program, 'Emprunteur de plus de 7 ans', $borrowerType));
            $manager->persist(new ProgramChoiceOption($program, 'Emprunteur de moins de 7 ans', $borrowerType));
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
