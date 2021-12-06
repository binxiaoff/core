<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Validator\Constraints;

use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramStatus;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ProgramDistributedValidator extends ConstraintValidator
{
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

        if ($programEligibilities->isEmpty()) {
            $this->context->buildViolation('CreditGuaranty.Program.programEligibilities.empty')
                ->atPath('programEligibilities')
                ->addViolation()
            ;

            return;
        }

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
    }
}
