<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Unilend\CreditGuaranty\Entity\ProgramBorrowerTypeAllocation;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use Unilend\CreditGuaranty\Repository\ProgramBorrowerTypeAllocationRepository;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;
use Unilend\CreditGuaranty\Repository\ProgramEligibilityRepository;
use Unilend\CreditGuaranty\Repository\ProgramRepository;

class ProgramBorrowerTypeDataPersister implements DataPersisterInterface
{
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private ProgramRepository $programRepository;
    private ProgramEligibilityRepository $programEligibilityRepository;
    private ProgramBorrowerTypeAllocationRepository $programBorrowerTypeAllocationRepository;

    public function __construct(
        ProgramRepository $programRepository,
        ProgramEligibilityRepository $programEligibilityRepository,
        ProgramChoiceOptionRepository $programChoiceOptionRepository,
        ProgramBorrowerTypeAllocationRepository $programBorrowerTypeAllocationRepository
    ) {
        $this->programChoiceOptionRepository           = $programChoiceOptionRepository;
        $this->programRepository                       = $programRepository;
        $this->programEligibilityRepository            = $programEligibilityRepository;
        $this->programBorrowerTypeAllocationRepository = $programBorrowerTypeAllocationRepository;
    }

    public function supports($data): bool
    {
        return $data instanceof ProgramBorrowerTypeAllocation;
    }

    /**
     * @param ProgramBorrowerTypeAllocation $data
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function persist($data): void
    {
        $program           = $data->getProgram();
        $borrowerTypeField = $data->getProgramChoiceOption()->getField();
        //Add ProgramBorrowerTypeAllocation and ProgramEligibility to the Program (instead of saving themselves separately),
        //so that we can check in the add() their existence and save both of them by saving the Program.
        $program->addProgramBorrowerTypeAllocation($data);
        $program->addProgramEligibility(new ProgramEligibility($program, $borrowerTypeField));
        $programEligibilities = $program->getProgramEligibilities()->filter(fn (ProgramEligibility $item) => $borrowerTypeField === $item->getField());

        if (1 !== $programEligibilities->count()) {
            throw new \LogicException(sprintf('1 program eligibility expected, but found %d', $programEligibilities->count()));
        }
        /** @var ProgramEligibility $programEligibility */
        $programEligibility = $programEligibilities->current();
        $programEligibility->addProgramEligibilityConfiguration(new ProgramEligibilityConfiguration($programEligibility, $data->getProgramChoiceOption(), null, true));

        $this->programRepository->save($program);
    }

    /**
     * @param ProgramBorrowerTypeAllocation $data
     *
     * @throws ORMException
     */
    public function remove($data): void
    {
        $program           = $data->getProgram();
        $borrowerTypeField = $data->getProgramChoiceOption()->getField();
        //remove all
        //todo: or if choice option is not used by any project
        if ($program->isInDraft()) {
            //By removing ProgramChoiceOption, the related ProgramEligibilityConfiguration and ProgramBorrowerTypeAllocation will also be (cascade) removed.
            $this->programChoiceOptionRepository->remove($data->getProgramChoiceOption());
        //remove only ProgramBorrowerTypeAllocation and ProgramEligibilityConfiguration
        //todo: archive ProgramChoiceOption
        } else {
            $this->programBorrowerTypeAllocationRepository->remove($data);
            $programEligibilities = $program->getProgramEligibilities()->filter(fn (ProgramEligibility $item) => $borrowerTypeField === $item->getField());
            if (1 === $programEligibilities->count()) {
                /** @var ProgramEligibility $programEligibility */
                $programEligibility               = $programEligibilities->current();
                $programEligibilityConfigurations = $programEligibility->getProgramEligibilityConfigurations()->filter(
                    fn (ProgramEligibilityConfiguration $item) => $item->getProgramChoiceOption() === $data->getProgramChoiceOption()
                );
                if (1 === $programEligibilityConfigurations->count()) {
                    $programEligibilityConfiguration = $programEligibilityConfigurations->current();
                    $programEligibility->removeProgramEligibilityConfiguration($programEligibilityConfiguration);
                    $this->programEligibilityRepository->save($programEligibility);
                }
            }
        }
    }
}
