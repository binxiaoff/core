<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait PercentChargeable
{
    /**
     * @var float
     *
     * @ORM\Column(type="decimal", precision=4, scale=2)
     */
    protected $rate;

    /**
     * @var string|null
     *
     * @ORM\Column(length=60, nullable=true)
     */
    protected $customisedName;

    /**
     * @return float
     */
    public function getRate(): float
    {
        return $this->rate;
    }

    /**
     * @param float $rate
     *
     * @return self
     */
    public function setRate(float $rate): self
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCustomisedName(): ?string
    {
        return $this->customisedName;
    }

    /**
     * @param string|null $customisedName
     *
     * @return self
     */
    public function setCustomisedName(?string $customisedName): self
    {
        $this->customisedName = $customisedName;

        return $this;
    }
}
