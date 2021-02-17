<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class MoneyGreaterThanOrEqual extends Constraint
{
    public string $message = 'The money amount is not greater than the previous one.';
    public string $field = '';

    /**
     * @inheritDoc
     */
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredOptions(): array
    {
        return ['field'];
    }

    /**
     * @inheritDoc
     */
    public function getDefaultOption(): string
    {
        return 'field';
    }
}
