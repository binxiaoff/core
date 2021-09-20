<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Listener\Doctrine\Entity;

use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Repository\FieldRepository;

class ProgramCreatedListener
{
    private FieldRepository $fieldRepository;

    public function __construct(FieldRepository $fieldRepository)
    {
        $this->fieldRepository = $fieldRepository;
    }

    /**
     * Auto-create the ProgramChoiceOptions for all eligibility fields of pre-defined list type.
     */
    public function createPredefinedChoiceOption(Program $program): void
    {
        $listFields = $this->fieldRepository->findBy(['tag' => Field::TAG_ELIGIBILITY, 'type' => Field::TYPE_LIST]);

        foreach ($listFields as $field) {
            if (null === $field->getPredefinedItems()) {
                continue;
            }
            foreach ($field->getPredefinedItems() as $option) {
                $program->addProgramChoiceOption(new ProgramChoiceOption($program, $option, $field));
            }
        }
    }
}
