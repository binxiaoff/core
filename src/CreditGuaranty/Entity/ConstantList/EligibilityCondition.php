<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity\ConstantList;

class EligibilityCondition
{
    private const TYPE_LIST = 'list';
    private const TYPE_BOOL = 'bool';
    private const TYPE_DATA = 'data';

    private string $name;

    private string $category;

    private string $type;
}
