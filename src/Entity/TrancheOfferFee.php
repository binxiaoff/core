<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\Fee;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class TrancheOfferFee
{
    use TimestampableTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Fee
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Fee")
     *
     * @Assert\Valid
     */
    private $fee;

    /**
     * @var TrancheOffer
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\TrancheOffer", inversedBy="trancheOfferFees")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_tranche_offer", nullable=false)
     * })
     *
     * @Assert\Valid
     */
    private $trancheOffer;

    /**
     * Initialise some object-value.
     *
     * @param TrancheOffer $trancheOffer
     * @param Fee          $fee
     *
     * @throws \Exception
     */
    public function __construct(TrancheOffer $trancheOffer, Fee $fee)
    {
        $this->fee          = $fee;
        $this->trancheOffer = $trancheOffer;
        $this->added        = new DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return TrancheOffer
     */
    public function getTrancheOffer(): TrancheOffer
    {
        return $this->trancheOffer;
    }

    /**
     * @param TrancheOffer $trancheOffer
     *
     * @return TrancheOfferFee
     */
    public function setTrancheOffer(TrancheOffer $trancheOffer): TrancheOfferFee
    {
        $this->trancheOffer = $trancheOffer;

        return $this;
    }

    /**
     * @return Fee
     */
    public function getFee(): Fee
    {
        return $this->fee;
    }
}
