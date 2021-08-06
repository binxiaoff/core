<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Core\DataFixtures\AbstractFixtures;
use Unilend\CreditGuaranty\Entity\Constant\FieldAlias;
use Unilend\CreditGuaranty\Entity\ProgramBorrowerTypeAllocation;
use Unilend\CreditGuaranty\Repository\FieldRepository;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityConfigurationRepository;

class ProgramBorrowerTypeAllocationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;
    private ProgramChoiceOptionRepository             $programChoiceOptionRepository;
    private FieldRepository                           $fieldRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository,
        ProgramChoiceOptionRepository $programChoiceOptionRepository,
        FieldRepository $fieldRepository
    ) {
        parent::__construct($tokenStorage);
        $this->programEligibilityConfigurationRepository = $programEligibilityConfigurationRepository;
        $this->programChoiceOptionRepository             = $programChoiceOptionRepository;
        $this->fieldRepository                           = $fieldRepository;
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $programBorrowerTypeField          = $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::BORROWER_TYPE]);
        $programBorrowerTypeChoiceOptions  = $this->programChoiceOptionRepository->findBy(['field' => $programBorrowerTypeField]);
        $programBorrowerTypeConfigurations = $this->programEligibilityConfigurationRepository->findBy(['programChoiceOption' => $programBorrowerTypeChoiceOptions]);

        foreach ($programBorrowerTypeConfigurations as $programBorrowerTypeConfiguration) {
            $programBorrowerTypeAllocation = new ProgramBorrowerTypeAllocation(
                $programBorrowerTypeConfiguration->getProgramEligibility()->getProgram(),
                $programBorrowerTypeConfiguration->getProgramChoiceOption(),
                (string) (\random_int(0, 100) / 100)
            );
            $manager->persist($programBorrowerTypeAllocation);
        }
        $manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            FieldFixtures::class,
            ProgramChoiceOptionFixtures::class,
            ProgramEligibilityConfigurationFixtures::class,
        ];
    }
}
