<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\CreditGuaranty\Entity\Program;
use KLS\CreditGuaranty\Entity\ProgramEligibility;
use KLS\CreditGuaranty\Repository\FieldRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProgramEligibilityFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private FieldRepository $fieldRepository;

    public function __construct(TokenStorageInterface $tokenStorage, FieldRepository $fieldRepository)
    {
        parent::__construct($tokenStorage);
        $this->fieldRepository = $fieldRepository;
    }

    public function load(ObjectManager $manager): void
    {
        $fields = $this->fieldRepository->findAll();
        /** @var Program $program */
        foreach ($this->getReferences(ProgramFixtures::ALL_PROGRAMS) as $program) {
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
