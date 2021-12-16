<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Validator\Constraints;

use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramStatus;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ProgramDistributedValidator extends ConstraintValidator
{
    private FieldRepository $fieldRepository;
    private ProgramEligibilityRepository $programEligibilityRepository;

    public function __construct(
        FieldRepository $fieldRepository,
        ProgramEligibilityRepository $programEligibilityRepository
    ) {
        $this->fieldRepository              = $fieldRepository;
        $this->programEligibilityRepository = $programEligibilityRepository;
    }

    /**
     * @param ProgramStatus      $value
     * @param ProgramDistributed $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (ProgramStatus::STATUS_DISTRIBUTED !== $value->getStatus() || null !== $value->getId()) {
            return;
        }

        $this->checkEligibility($value->getProgram());
    }

    private function checkEligibility(Program $program): void
    {
        $programEligibilities = $program->getProgramEligibilities();

        // check programEligibilities empty
        if ($programEligibilities->isEmpty()) {
            $this->context->buildViolation('CreditGuaranty.Program.programEligibilities.empty')
                ->atPath('programEligibilities')
                ->addViolation()
            ;

            return;
        }

        // check programEligibilities empty by category
        $countFieldsByCategory = [
            'profile' => 0,
            'project' => 0,
            'loan'    => 0,
        ];
        $emptyListTypeConfigurations = 0;

        foreach ($programEligibilities as $programEligibility) {
            $field = $programEligibility->getField();

            ++$countFieldsByCategory[$field->getCategory()];

            if (Field::TYPE_LIST !== $field->getType()) {
                continue;
            }

            if (0 === $programEligibility->getProgramEligibilityConfigurations()->count()) {
                ++$emptyListTypeConfigurations;
            }
        }

        $emptyConfiguredFieldCategories = \array_filter($countFieldsByCategory, static fn ($count) => 0 === $count);

        if (false === empty($emptyConfiguredFieldCategories)) {
            $this->context->buildViolation('CreditGuaranty.Program.programEligibilities.missingEligibilityForCategory')
                ->atPath('programEligibilities')
                ->addViolation()
            ;
        }

        if ($emptyListTypeConfigurations > 0) {
            $this->context->buildViolation('CreditGuaranty.Program.programEligibilities.missingOptionForListTypeField')
                ->atPath('programEligibilities')
                ->addViolation()
            ;
        }

        // check programEligibilities related to ESB
        if ($program->isEsbCalculationActivated()) {
            $programEligibilities = $this->programEligibilityRepository->findBy([
                'program' => $program,
                'field'   => $this->fieldRepository->findBy(['fieldAlias' => FieldAlias::ESB_RELATED_FIELDS]),
            ]);

            if (\count($programEligibilities) !== \count(FieldAlias::ESB_RELATED_FIELDS)) {
                $this->context->buildViolation('CreditGuaranty.Program.programEligibilities.missingEligibilityForEsb')
                    ->atPath('programEligibilities')
                    ->addViolation()
                ;
            }
        }
    }
}
