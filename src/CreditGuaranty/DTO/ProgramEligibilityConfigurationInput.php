<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;

class ProgramEligibilityConfigurationInput
{
    /**
     * @Assert\NotBlank
     * @Assert\Expression(
     *     "constant('Unilend\\CreditGuaranty\\Entity\\FieldConfiguration::TYPE_LIST') === value.getFieldConfiguration().getType()",
     *     message="CreditGuaranty.ProgramEligibilityConfigurationInput.programEligibility.type.onlyList"
     * )
     */
    public ProgramEligibility $programEligibility;

    /**
     * @Assert\NotBlank
     */
    public string $value;
}
