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
     *     "constant('Unilend\\CreditGuaranty\\Entity\\Field::TYPE_LIST') === value.getField().getType()",
     *     message="CreditGuaranty.ProgramEligibilityConfigurationInput.programEligibility.type.onlyList"
     * )
     */
    public ProgramEligibility $programEligibility;

    /**
     * @Assert\NotBlank
     */
    public string $value;
}
