<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Traits\ConstantsAwareTrait;

/**
 * @ORM\Embeddable
 */
class LendingRate
{
    use ConstantsAwareTrait;

    public const INDEX_FIXED   = 'FIXED';
    public const INDEX_EURIBOR = 'EURIBOR';
    public const INDEX_EONIA   = 'EONIA';
    public const INDEX_SONIA   = 'SONIA';
    public const INDEX_LIBOR   = 'LIBOR';
    public const INDEX_CHFTOIS = 'CHFTOIS';
    public const INDEX_FFER    = 'FFER';

    public const MARGIN_SCALE = 2;

    /**
     * @var string
     *
     * @ORM\Column(length=20)
     *
     * @Assert\NotBlank
     */
    private $indexType;

    /**
     * The margin to be added on the indexed rate.
     *
     * @var string
     *
     * @ORM\Column(type="decimal", precision=4, scale=2)
     *
     * @Assert\NotBlank
     * @Assert\Range(min="0.01", max="99.99")
     */
    private $margin;

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
    public function setIndexType(string $indexType): self
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
    public function setMargin(string $margin): self
    {
        $this->margin = $margin;

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
