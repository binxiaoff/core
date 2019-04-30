<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class TranchePercentFee
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var PercentFee
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\PercentFee", cascade={"persist", "remove"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_percent_fee", nullable=false)
     * })
     */
    private $percentFee;

    /**
     * @var Tranche
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Tranche", inversedBy="tranchePercentFees")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_tranche", nullable=false)
     * })
     */
    private $tranche;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Tranche
     */
    public function getTranche(): Tranche
    {
        return $this->tranche;
    }

    /**
     * @param Tranche $tranche
     *
     * @return TranchePercentFee
     */
    public function setTranche(Tranche $tranche): TranchePercentFee
    {
        $this->tranche = $tranche;

        return $this;
    }

    /**
     * @return PercentFee|null
     */
    public function getPercentFee(): ?PercentFee
    {
        return $this->percentFee;
    }

    /**
     * @param PercentFee $percentFee
     *
     * @return TranchePercentFee
     */
    public function setPercentFee(PercentFee $percentFee): TranchePercentFee
    {
        $this->percentFee = $percentFee;

        return $this;
    }
}
