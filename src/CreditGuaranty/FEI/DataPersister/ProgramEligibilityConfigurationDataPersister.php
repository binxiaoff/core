<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\ProgramBorrowerTypeAllocation;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\FEI\Repository\ProgramChoiceOptionRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityConfigurationRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramRepository;

class ProgramEligibilityConfigurationDataPersister implements DataPersisterInterface
{
    private ProgramRepository $programRepository;
    private ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;

    public function __construct(
        ProgramRepository $programRepository,
        ProgramEligibilityConfigurationRepository $programEligibilityConfigurationRepository,
        ProgramChoiceOptionRepository $programChoiceOptionRepository
    ) {
        $this->programEligibilityConfigurationRepository = $programEligibilityConfigurationRepository;
        $this->programRepository                         = $programRepository;
        $this->programChoiceOptionRepository             = $programChoiceOptionRepository;
    }

    public function supports($data): bool
    {
        return $data instanceof ProgramEligibilityConfiguration && $data->getProgramChoiceOption();
    }

    /**
     * @param ProgramEligibilityConfiguration $data
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function persist($data): void
    {
        $programChoiceOption = $data->getProgramChoiceOption();
        $fieldAlias          = $programChoiceOption->getField()->getFieldAlias();

        $programChoiceOption->setArchived(null);

        if (FieldAlias::BORROWER_TYPE !== $fieldAlias) {
            $this->programEligibilityConfigurationRepository->save($data);

            return;
        }

        $programEligibility = $data->getProgramEligibility();
        $program            = $programEligibility->getProgram();

        // Add ProgramBorrowerTypeAllocation and ProgramEligibility to the Program
        // (instead of saving themselves separately),
        // so that we can check in the add() their existence and save both of them by saving the Program.
        $programEligibility->addProgramEligibilityConfiguration($data);
        $program->addProgramBorrowerTypeAllocation(
            new ProgramBorrowerTypeAllocation($program, $data->getProgramChoiceOption(), '1')
        );

        $this->programRepository->save($program);
    }

    /**
     * @param ProgramEligibilityConfiguration $data
     *
     * @throws ORMException
     */
    public function remove($data): void
    {
        $programEligibility = $data->getProgramEligibility();
        $program            = $programEligibility->getProgram();
        $fieldAlias         = $data->getProgramChoiceOption()->getField()->getFieldAlias();

        // Normally by removing ProgramChoiceOption, the related ProgramEligibilityConfiguration and
        // ProgramBorrowerTypeAllocation will also be (cascade) removed.
        // But in case of the ProgramChoiceOption archiving (instead of deleting), the deleting cascade configured in
        // the ProgramEligibilityConfiguration doesn't work,
        // we need to remove "manually" the ProgramEligibilityConfiguration.
        $this->programEligibilityConfigurationRepository->remove($data);
        $this->programChoiceOptionRepository->remove($data->getProgramChoiceOption());

        if (FieldAlias::BORROWER_TYPE !== $fieldAlias) {
            return;
        }

        // remove only ProgramBorrowerTypeAllocation and ProgramEligibilityConfiguration
        $programBorrowerTypeAllocations = $program->getProgramBorrowerTypeAllocations()->filter(
            fn (ProgramBorrowerTypeAllocation $item) => $item->getProgramChoiceOption() === $data->getProgramChoiceOption()
        );

        if (1 === $programBorrowerTypeAllocations->count()) {
            $programBorrowerTypeAllocation = $programBorrowerTypeAllocations->current();
            $program->removeProgramBorrowerTypeAllocation($programBorrowerTypeAllocation);
            $this->programRepository->save($program);
        }
    }
}
