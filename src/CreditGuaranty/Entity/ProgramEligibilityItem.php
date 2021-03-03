<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};

class ProgramEligibilityItem
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\Column(length=100)
     */
    private string $name;

    /**
     * @ORM\ManyToOne(targetEntity="ProgramEligibility")
     * @ORM\JoinColumn(name="id_program_eligibility", nullable=false)
     */
    private ProgramEligibility $programEligibility;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $enabled;

    /**
     * @param ProgramEligibility $programEligibility
     * @param bool               $enabled
     */
    public function __construct(ProgramEligibility $programEligibility, bool $enabled = true)
    {
        $this->programEligibility = $programEligibility;
        $this->enabled = $enabled;
    }

    /**
     * @return ProgramEligibility
     */
    public function getProgramEligibility(): ProgramEligibility
    {
        return $this->programEligibility;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return ProgramEligibilityItem
     */
    public function setName(string $name): ProgramEligibilityItem
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param bool $enabled
     *
     * @return ProgramEligibilityItem
     */
    public function setEnabled(bool $enabled): ProgramEligibilityItem
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
