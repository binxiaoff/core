<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Listener\Doctrine\Entity;

use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Repository\FieldRepository;

class ProgramCreatedListener
{
    private FieldRepository $fieldRepository;

    public function __construct(FieldRepository $fieldRepository)
    {
        $this->fieldRepository = $fieldRepository;
    }

    /**
     * Auto-create the ProgramChoiceOptions with pre-defined list
     * Get all "list" type fields, because we create only the choice options for the field defined in this list.
     */
    public function createPredefinedChoiceOption(Program $program): void
    {
        $listField = $this->fieldRepository->findBy(['type' => Field::TYPE_LIST]);
        foreach ($listField as $field) {
            if (null === $field->getPredefinedItems()) {
                continue;
            }
            foreach ($field->getPredefinedItems() as $option) {
                $program->addProgramChoiceOption(new ProgramChoiceOption($program, $option, $field));
            }
        }
    }
}
