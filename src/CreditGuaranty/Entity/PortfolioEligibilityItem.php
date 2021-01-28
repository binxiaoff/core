<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};

class PortfolioEligibilityItem
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\Column(length=100)
     */
    private string $name;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\PortfolioEligibility")
     * @ORM\JoinColumn(name="id_portfolio_eligibility", nullable=false)
     */
    private PortfolioEligibility $portfolioEligibility;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $enabled;

    /**
     * @param PortfolioEligibility $portfolioEligibility
     * @param bool                 $enabled
     */
    public function __construct(PortfolioEligibility $portfolioEligibility, bool $enabled = true)
    {
        $this->portfolioEligibility = $portfolioEligibility;
        $this->enabled = $enabled;
    }

    /**
     * @return PortfolioEligibility
     */
    public function getPortfolioEligibility(): PortfolioEligibility
    {
        return $this->portfolioEligibility;
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
     * @return PortfolioEligibilityItem
     */
    public function setName(string $name): PortfolioEligibilityItem
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param bool $enabled
     *
     * @return PortfolioEligibilityItem
     */
    public function setEnabled(bool $enabled): PortfolioEligibilityItem
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
