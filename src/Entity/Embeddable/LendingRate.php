<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Embeddable
 */
class LendingRate
{
    use ConstantsAwareTrait;

    public const INDEX_FIXED             = 'FIXED';
    public const INDEX_EURIBOR_1_MONTH   = 'EURIBOR_1_MONTH';
    public const INDEX_EURIBOR_3_MONTHS  = 'EURIBOR_3_MONTHS';
    public const INDEX_EURIBOR_6_MONTHS  = 'EURIBOR_6_MONTHS';
    public const INDEX_EURIBOR_12_MONTHS = 'EURIBOR_12_MONTHS';
    public const INDEX_EONIA             = 'EONIA';
    public const INDEX_SONIA             = 'SONIA';
    public const INDEX_LIBOR             = 'LIBOR';
    public const INDEX_CHFTOIS           = 'CHFTOIS';
    public const INDEX_FFER              = 'FFER';
    public const INDEX_ESTER             = '€STR';

    public const MARGIN_SCALE = 2;

    /**
     * @var string
     *
     * @ORM\Column(length=20)
     *
     * @Assert\NotBlank(groups={"non-nullable"})
     */
    protected $indexType;

    /**
     * The margin to be added on the indexed rate.
     *
     * @var string
     *
     * @ORM\Column(type="decimal", precision=4, scale=4)
     *
     * @Assert\NotBlank(groups={"non-nullable"})
     * @Assert\Range(min="0", max="0.9999")
     */
    protected $margin;

    /**
     * Have floor = X. Floor the indexed rate + margin to X if it's lower than X.
     *
     * @var string
     *
     * @ORM\Column(type="decimal", precision=4, scale=4, nullable=true)
     *
     * @Assert\Range(max="-0.9999", max="0.9999")
     */
    protected $floor;

    /**
     * @return string|null
     */
    public function getIndexType(): ?string
    {
        return $this->indexType;
    }

    /**
     * @param string $indexType
     *
     * @return self
     */
    public function setIndexType(string $indexType)
    {
        $this->indexType = $indexType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMargin(): ?string
    {
        return $this->margin;
    }

    /**
     * @param string $margin
     *
     * @return self
     */
    public function setMargin(string $margin)
    {
        $this->margin = $margin;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFloor(): ?string
    {
        return $this->floor;
    }

    /**
     * @param string|null $floor
     *
     * @return self
     */
    public function setFloor(?string $floor): self
    {
        $this->floor = $floor;

        return $this;
    }

    /**
     * @return array
     */
    public static function getIndexes(): array
    {
        return self::getConstants('INDEX_');
    }
}
