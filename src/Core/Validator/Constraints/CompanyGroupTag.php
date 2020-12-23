<?php

namespace Unilend\Core\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class CompanyGroupTag extends Constraint
{
    /**
     * @var string|null
     */
    public ?string $companyPropertyPath = null;

    /**
     * @var string|null
     */
    public ?string $teamPropertyPath = null;

    /**
     * @var string
     */
    public string $message = 'The company group tag is not compatible with this object';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
