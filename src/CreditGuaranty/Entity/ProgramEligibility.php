<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};
use Unilend\CreditGuaranty\Entity\ConstantList\EligibilityCondition;

class ProgramEligibility
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     */
    private Program $program;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ConstantList\EligibilityCondition")
     * @ORM\JoinColumn(name="id_eligibility_condition", nullable=false)
     */
    private EligibilityCondition $eligibilityCondition;

    /**
     * When the eligibility type is data or bool.
     *
     * @ORM\Column(length=100)
     */
    private ?string $data;

    /**
     * @param Program              $program
     * @param EligibilityCondition $eligibilityCondition
     * @param string|null          $data
     */
    public function __construct(Program $program, EligibilityCondition $eligibilityCondition, ?string $data)
    {
        $this->program              = $program;
        $this->eligibilityCondition = $eligibilityCondition;
        $this->data                 = $data;
    }

    /**
     * @return Program
     */
    public function getProgram(): Program
    {
        return $this->program;
    }

    /**
     * @return EligibilityCondition
     */
    public function getEligibilityCondition(): EligibilityCondition
    {
        return $this->eligibilityCondition;
    }

    /**
     * @return string|null
     */
    public function getData(): ?string
    {
        return $this->data;
    }

    /**
     * @param string|null $data
     *
     * @return ProgramEligibility
     */
    public function setData(?string $data): ProgramEligibility
    {
        $this->data = $data;

        return $this;
    }
}
