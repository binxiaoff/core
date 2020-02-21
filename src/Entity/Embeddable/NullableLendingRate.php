<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The annotation "AttributeOverride" cannot be used for an embeddable class, because of the bug mentioned here:
 * https://github.com/doctrine/orm/pull/6151#issuecomment-271432401 .
 *
 * @ORM\Embeddable
 */
class NullableLendingRate extends LendingRate
{
    /**
     * @var string
     *
     * @ORM\Column(length=20, nullable=true)
     *
     * @Groups({"nullableLendingRate:read"})
     */
    protected $indexType;

    /**
     * The margin to be added on the indexed rate.
     *
     * @var string
     *
     * @ORM\Column(type="decimal", precision=4, scale=4, nullable=true)
     *
     * @Assert\Range(min="0", max="0.9999")
     *
     * @Groups({"nullableLendingRate:read"})
     */
    protected $margin;

    /**
     * @param string|null $indexType
     * @param string|null $margin
     * @param string|null $floor
     * @param string|null $floorType
     */
    public function __construct(?string $indexType = null, ?string $margin = null, ?string $floor = null, ?string $floorType = null)
    {
        $this->indexType = $indexType;
        $this->margin    = $margin;
        $this->floorType = $floorType;

        if ($indexType && $margin && $floorType) {
            parent::__construct($indexType, $margin, $floor, $floorType);
        }
    }

    /**
     * @param string|null $indexType
     *
     * @return self
     */
    public function setIndexType(?string $indexType)
    {
        $this->indexType = $indexType;

        return $this;
    }

    /**
     * @param string|null $margin
     *
     * @return self
     */
    public function setMargin(?string $margin)
    {
        $this->margin = $margin;

        return $this;
    }
}
