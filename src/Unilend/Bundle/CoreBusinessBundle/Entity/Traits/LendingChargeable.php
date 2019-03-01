<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Bundle\CoreBusinessBundle\Entity\LendingFeeType;

trait LendingChargeable
{
    use PercentChargeable;

    /**
     * @var LendingFeeType|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\LendingFeeType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lending_fee_type", referencedColumnName="id")
     * })
     */
    protected $lendingFeeType;

    /**
     * @return LendingFeeType|null
     */
    public function getLendingFeeType(): ?LendingFeeType
    {
        return $this->lendingFeeType;
    }

    /**
     * @param LendingFeeType|null $lendingFeeType
     *
     * @return self
     */
    public function setLendingFeeType(?LendingFeeType $lendingFeeType): self
    {
        $this->lendingFeeType = $lendingFeeType;

        return $this;
    }
}
