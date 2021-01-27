<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

class PortfolioBorrowerTypeAllocation
{
    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Portfolio")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_portfolio", nullable=false)
     * })
     */
    private Portfolio $portfolio;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramBorrowerType")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_program_borrower_type", nullable=false)
     * })
     */
    private ProgramBorrowerType $programBorrowerType;

    /**
     * @ORM\Column(type="decimal", precision=4, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="0.9999")
     */
    private string $maxAllocationRate;

    /**
     * @param Portfolio           $portfolio
     * @param ProgramBorrowerType $programBorrowerType
     * @param string              $maxAllocationRate
     */
    public function __construct(Portfolio $portfolio, ProgramBorrowerType $programBorrowerType, string $maxAllocationRate)
    {
        $this->portfolio           = $portfolio;
        $this->programBorrowerType = $programBorrowerType;
        $this->maxAllocationRate   = $maxAllocationRate;
    }

    /**
     * @return Portfolio
     */
    public function getPortfolio(): Portfolio
    {
        return $this->portfolio;
    }

    /**
     * @return ProgramBorrowerType
     */
    public function getProgramBorrowerType(): ProgramBorrowerType
    {
        return $this->programBorrowerType;
    }

    /**
     * @param ProgramBorrowerType $programBorrowerType
     *
     * @return PortfolioBorrowerTypeAllocation
     */
    public function setProgramBorrowerType(ProgramBorrowerType $programBorrowerType): PortfolioBorrowerTypeAllocation
    {
        $this->programBorrowerType = $programBorrowerType;

        return $this;
    }

    /**
     * @return string
     */
    public function getMaxAllocationRate(): string
    {
        return $this->maxAllocationRate;
    }

    /**
     * @param string $maxAllocationRate
     *
     * @return PortfolioBorrowerTypeAllocation
     */
    public function setMaxAllocationRate(string $maxAllocationRate): PortfolioBorrowerTypeAllocation
    {
        $this->maxAllocationRate = $maxAllocationRate;

        return $this;
    }
}
