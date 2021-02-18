<?php

declare(strict_types=1);

namespace Unilend\Core\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

abstract class AbstractMoneyPreviousValueComparison extends Constraint
{
    public string $message;
    // if set, we will only check the property in that status of an entity. In this case, the entity need to implements Unilend\Core\Entity\Interfaces\TraceableStatusAwareInterface
    public ?int $monitoredStatus = null;
}
