<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class PercentFee
{
    use TimestampableTrait;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var FeeType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\FeeType")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_type", referencedColumnName="id", nullable=false)
     * })
     */
    private $type;

    /**
     * @var string|null
     *
     * @ORM\Column(length=60, nullable=true)
     */
    private $customisedName;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=4, scale=2)
     */
    private $rate;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $isRecurring;

    /**
     * @return string|null
     */
    public function getRate(): ?string
    {
        return $this->rate;
    }

    /**
     * @param string $rate
     *
     * @return PercentFee
     */
    public function setRate(string $rate): PercentFee
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
     * @return PercentFee
     */
    public function setCustomisedName(?string $customisedName): PercentFee
    {
        $this->customisedName = $customisedName;

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return FeeType|null
     */
    public function getType(): ?FeeType
    {
        return $this->type;
    }

    /**
     * @param FeeType $type
     *
     * @return PercentFee
     */
    public function setType(FeeType $type): PercentFee
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isRecurring(): ?bool
    {
        return $this->isRecurring;
    }

    /**
     * @param bool $isRecurring
     *
     * @return PercentFee
     */
    public function setIsRecurring(bool $isRecurring): PercentFee
    {
        $this->isRecurring = $isRecurring;

        return $this;
    }
}
