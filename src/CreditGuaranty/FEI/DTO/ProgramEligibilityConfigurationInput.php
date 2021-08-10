<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DTO;

use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class ProgramEligibilityConfigurationInput
{
    /**
     * @Assert\NotBlank
     * @Assert\Expression(
     *     "constant('KLS\\CreditGuaranty\\FEI\\Entity\\Field::TYPE_LIST') === value.getField().getType()",
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
    public string $description;
}
