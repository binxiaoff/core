<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\CreditGuaranty\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\Entity\ProgramBorrowerTypeAllocation;
use KLS\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use KLS\CreditGuaranty\Repository\ProgramChoiceOptionRepository;
use KLS\CreditGuaranty\Repository\ProgramEligibilityConfigurationRepository;
use KLS\CreditGuaranty\Repository\ProgramRepository;

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
        return $data instanceof ProgramEligibilityConfiguration
            && $data->getProgramChoiceOption()
            && FieldAlias::BORROWER_TYPE === $data->getProgramChoiceOption()->getField()->getFieldAlias();
    }

    /**
     * @param ProgramEligibilityConfiguration $data
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function persist($data): void
    {
        $programEligibility = $data->getProgramEligibility();
        $program            = $programEligibility->getProgram();
        //Add ProgramBorrowerTypeAllocation and ProgramEligibility to the Program (instead of saving themselves separately),
        //so that we can check in the add() their existence and save both of them by saving the Program.
        $programEligibility->addProgramEligibilityConfiguration($data);
        $program->addProgramBorrowerTypeAllocation(new ProgramBorrowerTypeAllocation($program, $data->getProgramChoiceOption(), '1'));

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
        //remove all
        //todo: or if choice option is not used by any project
        if ($program->isInDraft()) {
            //By removing ProgramChoiceOption, the related ProgramEligibilityConfiguration and ProgramBorrowerTypeAllocation will also be (cascade) removed.
            $this->programChoiceOptionRepository->remove($data->getProgramChoiceOption());
        //remove only ProgramBorrowerTypeAllocation and ProgramEligibilityConfiguration
        //todo: archive ProgramChoiceOption
        } else {
            $this->programEligibilityConfigurationRepository->remove($data);
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
}
