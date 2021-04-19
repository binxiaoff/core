<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\CreditGuaranty\Repository\ProgramBorrowerTypeAllocationRepository;

class ProgramBorrowerTypeAllocationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    /** @var ProgramBorrowerTypeAllocationRepository */
    private ProgramBorrowerTypeAllocationRepository $programBorrowerTypeAllocationRepository;

    /**
     * ProgramBorrowerTypeAllocationFixtures constructor.
     *
     * @param TokenStorageInterface                   $tokenStorage
     * @param ProgramBorrowerTypeAllocationRepository $programBorrowerTypeAllocationRepository
     */
    public function __construct(TokenStorageInterface $tokenStorage, ProgramBorrowerTypeAllocationRepository $programBorrowerTypeAllocationRepository)
    {
        parent::__construct($tokenStorage);
        $this->programBorrowerTypeAllocationRepository = $programBorrowerTypeAllocationRepository;
    }

    /**
     * @param ObjectManager $manager
     *
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $allProgramBorrowerTypeAllocations = $this->programBorrowerTypeAllocationRepository->findAll();

        foreach ($allProgramBorrowerTypeAllocations as $programBorrowerTypeAllocation) {
            $programBorrowerTypeAllocation->setMaxAllocationRate((string) (random_int(0, 100) / 100));
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
