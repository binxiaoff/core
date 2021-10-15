<?php

declare(strict_types=1);

namespace KLS\Test\CreditGuaranty\FEI\Unit\Traits;

use KLS\CreditGuaranty\FEI\Entity\Borrower;
use KLS\CreditGuaranty\FEI\Entity\Field;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\Project;

trait FieldTrait
{
    /**
     * @return Field[}|array
     */
    protected function createMultipleFields(): array
    {
        $fields = [];

        $fields[] = new Field(
            'guaranty_duration',
            Field::TAG_INFO,
            'program',
            'other',
            'program',
            'guarantyDuration',
            'int',
            Program::class,
            false,
            null,
            null
        );
        $fields[] = new Field(
            'reservation_status',
            Field::TAG_INFO,
            'reservation',
            'other',
            'currentStatus',
            '',
            'int',
            '',
            false,
            null,
            null
        );
        $fields[] = new Field(
            'borrower_type',
            Field::TAG_ELIGIBILITY,
            'borrower',
            'list',
            'borrower',
            'borrowerType',
            'ProgramChoiceOption',
            Borrower::class,
            false,
            null,
            null
        );
        $fields[] = new Field(
            'project_total_amount',
            Field::TAG_ELIGIBILITY,
            'project',
            'other',
            'project',
            'fundingMoney',
            'MoneyInterface',
            Project::class,
            true,
            'money',
            null
        );
        $fields[] = new Field(
            'supporting_generations_renewal',
            Field::TAG_ELIGIBILITY,
            'loan',
            'bool',
            'financingObjects',
            'supportingGenerationsRenewal',
            'bool',
            FinancingObject::class,
            false,
            null,
            null
        );

        return $fields;
    }
}
