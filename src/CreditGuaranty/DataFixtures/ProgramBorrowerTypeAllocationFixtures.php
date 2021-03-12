<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\CreditGuaranty\Entity\ProgramBorrowerTypeAllocation;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;

class ProgramBorrowerTypeAllocationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /** @var ProgramChoiceOptionRepository */
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;

    /**
     * ProgramBorrowerTypeAllocationFixtures constructor.
     *
     * @param TokenStorageInterface         $tokenStorage
     * @param ProgramChoiceOptionRepository $programChoiceOptionRepository
     */
    public function __construct(TokenStorageInterface $tokenStorage, ProgramChoiceOptionRepository $programChoiceOptionRepository)
    {
        parent::__construct($tokenStorage);

        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $maxAllocationRates = ['0.10', '0.20', '0.30', '0.40', '0.50', '0.60', '0.70', '0.80', '0.90'];
        $choiceOptions      = $this->programChoiceOptionRepository->findAll();
        $nbChoiceOptions    = count($choiceOptions);
        $programReferences  = [
            ProgramFixtures::REFERENCE_CANCELLED,
            ProgramFixtures::REFERENCE_COMMERCIALIZED,
            ProgramFixtures::REFERENCE_DRAFT,
            ProgramFixtures::REFERENCE_PAUSED,
        ];

        foreach ($programReferences as $programReference) {
            /** @var Program $program */
            $program = $this->getReference($programReference);
            shuffle($choiceOptions);

            for ($i = 0; $i <= rand(0, $nbChoiceOptions - 1); $i++) {
                $programBorrowerTypeAllocation = new ProgramBorrowerTypeAllocation($program, $choiceOptions[$i], $maxAllocationRates[array_rand($maxAllocationRates)]);
                $manager->persist($programBorrowerTypeAllocation);
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
            ProgramChoiceOptionFixtures::class,
        ];
    }
}
