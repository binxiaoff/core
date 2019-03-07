<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class LendingRate
{
    const TYPE_FIXED = 'FIXED';
    /**
     * @var string
     *
     * @ORM\Column(length=20)
     */
    private $type;

    /**
     * The margin to be added on the indexed rate.
     *
     * @var float
     *
     * @ORM\Column(type="decimal", precision=4, scale=2)
     */
    private $margin;

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return float
     */
    public function getMargin(): float
    {
        return $this->margin;
    }

    /**
     * @param float $margin
     *
     * @return self
     */
    public function setMargin(float $margin): self
    {
        $this->margin = $margin;

        return $this;
    }
}
