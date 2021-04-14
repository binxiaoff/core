<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DTO;

use Symfony\Component\Serializer\Annotation\Groups;
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
     *
     * @Groups({"creditGuaranty:programEligibilityConfiguration:write"})
     */
    public ProgramEligibility $programEligibility;

    /**
     * @Assert\NotBlank
     *
     * @Groups({"creditGuaranty:programEligibilityConfiguration:write"})
     */
    public string $value;
}
