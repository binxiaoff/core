<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use KLS\Core\DataFixtures\AbstractFixtures;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\ProgramBorrowerTypeAllocation;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramChoiceOptionRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConfigurationRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProgramBorrowerTypeAllocationFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private FieldRepository                           $fieldRepository;
    private ProgramChoiceOptionRepository             $programChoiceOptionRepository;
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        FieldRepository $fieldRepository,
        ProgramChoiceOptionRepository $programChoiceOptionRepository,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository
    ) {
        parent::__construct($tokenStorage);
        $this->fieldRepository                           = $fieldRepository;
        $this->programChoiceOptionRepository             = $programChoiceOptionRepository;
        $this->programEligibilityConfigurationRepository = $programEligibilityConfigurationRepository;
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return [
            ProgramEligibilityConfigurationFixtures::class,
        ];
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $borrowerTypeField = $this->fieldRepository->findOneBy([
            'fieldAlias' => FieldAlias::BORROWER_TYPE,
        ]);
        $borrowerTypeProgramChoiceOptions = $this->programChoiceOptionRepository->findBy([
            'field' => $borrowerTypeField,
        ]);
        $borrowerTypeProgramEligibilityConfigurations = $this->programEligibilityConfigurationRepository->findBy([
            'programChoiceOption' => $borrowerTypeProgramChoiceOptions,
        ]);

        foreach ($borrowerTypeProgramEligibilityConfigurations as $programEligibilityConfiguration) {
            $programBorrowerTypeAllocation = new ProgramBorrowerTypeAllocation(
                $programEligibilityConfiguration->getProgramEligibility()->getProgram(),
                $programEligibilityConfiguration->getProgramChoiceOption(),
                (string) (\random_int(0, 100) / 100)
            );
            $manager->persist($programBorrowerTypeAllocation);
        }

        $manager->flush();
    }
}
