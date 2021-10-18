<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Validator\Constraints;

use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Entity\ProgramStatus;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ProgramDistributedEsbValidator extends ConstraintValidator
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
     * @param ProgramStatus         $value
     * @param ProgramDistributedEsb $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (ProgramStatus::STATUS_DISTRIBUTED !== $value->getStatus() || null !== $value->getId()) {
            return;
        }

        $program = $value->getProgram();

        if (false === $program->isEsbCalculationActivated()) {
            return;
        }

        $fields = $this->fieldRepository->findBy(['fieldAlias' => FieldAlias::ESB_FIELDS]);

        foreach ($fields as $field) {
            $programEligibility = $this->programEligibilityRepository->findOneBy([
                'program' => $program,
                'field'   => $field,
            ]);

            if (false === ($programEligibility instanceof ProgramEligibility)) {
                $this->context->buildViolation('CreditGuaranty.ProgramEligibility.field.requiredForEsb')
                    ->setParameter('%fieldAlias%', $field->getFieldAlias())
                    ->atPath('programEligibilities')
                    ->addViolation()
                ;
            } elseif (0 === $programEligibility->getProgramEligibilityConfigurations()->count()) {
                $this->context->buildViolation('CreditGuaranty.ProgramEligibilityConfiguration.requiredForEsb')
                    ->setParameter('%fieldAlias%', $field->getFieldAlias())
                    ->atPath('programEligibilities')
                    ->addViolation()
                ;
            }
        }
    }
}
