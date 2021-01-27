<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

class PortfolioGradeAllocation
{
    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Portfolio")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_portfolio", nullable=false)
     * })
     */
    private Portfolio $portfolio;

    /**
     * @ORM\Column(length=10)
     */
    private string $grade;

    /**
     * @ORM\Column(type="decimal", precision=4, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="0.9999")
     */
    private string $maxAllocationRate;

    /**
     * @param Portfolio $portfolio
     * @param string    $grade
     * @param string    $maxAllocationRate
     */
    public function __construct(Portfolio $portfolio, string $grade, string $maxAllocationRate)
    {
        $this->portfolio         = $portfolio;
        $this->grade             = $grade;
        $this->maxAllocationRate = $maxAllocationRate;
    }

    /**
     * @return Portfolio
     */
    public function getPortfolio(): Portfolio
    {
        return $this->portfolio;
    }

    /**
     * @return string
     */
    public function getGrade(): string
    {
        return $this->grade;
    }

    /**
     * @param string $grade
     *
     * @return PortfolioGradeAllocation
     */
    public function setGrade(string $grade): PortfolioGradeAllocation
    {
        $this->grade = $grade;

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
     * @return PortfolioGradeAllocation
     */
    public function setMaxAllocationRate(string $maxAllocationRate): PortfolioGradeAllocation
    {
        $this->maxAllocationRate = $maxAllocationRate;

        return $this;
    }
}
