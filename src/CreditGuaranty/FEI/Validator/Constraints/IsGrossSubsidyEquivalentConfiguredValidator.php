<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Validator\Constraints;

use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;
use KLS\CreditGuaranty\FEI\Repository\ProgramEligibilityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsGrossSubsidyEquivalentConfiguredValidator extends ConstraintValidator
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
     * @param ?bool                              $value
     * @param IsGrossSubsidyEquivalentConfigured $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (Program::class !== $this->context->getClassName()) {
            return;
        }

        if ('esbCalculationActivated' !== $this->context->getPropertyName()) {
            return;
        }

        /** @var Program $program */
        $program = $this->context->getObject();

        if (true !== $program->isEsbCalculationActivated()) {
            return;
        }

        $fields = $this->fieldRepository->findBy(['fieldAlias' => FieldAlias::ESB_RELATED_FIELDS]);

        foreach ($fields as $field) {
            $programEligibility = $this->programEligibilityRepository->findOneBy([
                'program' => $program,
                'field'   => $field,
            ]);

            if (false === ($programEligibility instanceof ProgramEligibility)) {
                $this->context->buildViolation(
                    'CreditGuaranty.Program.esbCalculationActivated.missingProgramEligibilityForEsb'
                )
                    ->setParameter('%fieldAlias%', $field->getFieldAlias())
                    ->addViolation()
                ;

                return;
            }

            if (0 === $programEligibility->getProgramEligibilityConfigurations()->count()) {
                $this->context->buildViolation(
                    'CreditGuaranty.Program.esbCalculationActivated.missingProgramEligibilityConfigurationForEsb'
                )
                    ->setParameter('%fieldAlias%', $field->getFieldAlias())
                    ->addViolation()
                ;
            }
        }
    }
}
