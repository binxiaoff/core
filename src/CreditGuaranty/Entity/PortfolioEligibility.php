<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};
use Unilend\CreditGuaranty\Entity\ConstantList\EligibilityCondition;

class PortfolioEligibility
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Portfolio")
     * @ORM\JoinColumn(name="id_portfolio", nullable=false)
     */
    private Portfolio $portfolio;

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
     * @param Portfolio            $portfolio
     * @param EligibilityCondition $eligibilityCondition
     * @param string|null          $data
     */
    public function __construct(Portfolio $portfolio, EligibilityCondition $eligibilityCondition, ?string $data)
    {
        $this->portfolio            = $portfolio;
        $this->eligibilityCondition = $eligibilityCondition;
        $this->data                 = $data;
    }

    /**
     * @return Portfolio
     */
    public function getPortfolio(): Portfolio
    {
        return $this->portfolio;
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
     * @return PortfolioEligibility
     */
    public function setData(?string $data): PortfolioEligibility
    {
        $this->data = $data;

        return $this;
    }
}
