<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Core\Traits\ConstantsAwareTrait;

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

    public const FLOOR_TYPE_NONE       = 'none';
    public const FLOOR_TYPE_INDEX      = 'index';
    public const FLOOR_TYPE_INDEX_RATE = 'index_rate';

    /**
     * @ORM\Column(length=20, nullable=true)
     *
     * @Assert\Choice(callback="getIndexes")
     *
     * @Groups({"lendingRate:read", "lendingRate:write"})
     */
    protected ?string $indexType = null;

    /**
     * The margin to be added on the indexed rate.
     *
     * @ORM\Column(type="decimal", precision=4, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="0.9999")
     *
     * @Groups({"lendingRate:read", "lendingRate:write"})
     */
    protected ?string $margin = null;

    /**
     * Have floor = X. Floor the indexed rate + margin to X if it's lower than X.
     *
     * @ORM\Column(type="decimal", precision=4, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\Range(max="-0.9999", max="0.9999")
     *
     * @Groups({"lendingRate:read", "lendingRate:write"})
     */
    protected ?string $floor = null;

    /**
     * @ORM\Column(length=20, nullable=true)
     *
     * @Assert\Choice(callback="getFloorTypes")
     *
     * @Groups({"lendingRate:read", "lendingRate:write"})
     */
    protected ?string $floorType = null;

    public function __construct(?string $indexType = null, ?string $margin = null, ?string $floor = null, ?string $floorType = null)
    {
        $this->setIndexType($indexType);
        $this->setMargin($margin);
        $this->setFloor($floor);
        $this->setFloorType($floorType);
    }

    public function getIndexType(): ?string
    {
        return $this->indexType;
    }

    public function setIndexType(?string $indexType): self
    {
        $this->indexType = $indexType;
        if (self::INDEX_FIXED === $this->indexType) {
            $this->floor     = null;
            $this->floorType = null;
        }

        return $this;
    }

    public function getMargin(): ?string
    {
        return $this->margin;
    }

    public function setMargin(?string $margin): self
    {
        $this->margin = '' === $margin ? null : $margin;

        return $this;
    }

    public function getFloor(): ?string
    {
        return $this->floor;
    }

    public function setFloor(?string $floor): self
    {
        $this->floor = '' === $floor ? null : $floor;

        return $this;
    }

    public static function getIndexes(): array
    {
        return self::getConstants('INDEX_');
    }

    public function getFloorType(): ?string
    {
        return $this->floorType;
    }

    public function setFloorType(?string $floorType): void
    {
        $this->floorType = $floorType;
    }

    public static function getFloorTypes(): array
    {
        return self::getConstants('FLOOR_TYPE_');
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context): void
    {
        // Transfer the "none" to null to simplify the validation conditions, because they are equivalent in this context.
        $floorType = self::FLOOR_TYPE_NONE === $this->getFloorType() ? null : $this->getFloorType();

        switch ($this->getIndexType()) {
            case null:
                if ($floorType || $this->getMargin() || $this->getFloor()) {
                    $context->buildViolation('Core.LendingRate.indexType.empty')->atPath('indexType')->addViolation();
                }

                break;

            case self::INDEX_FIXED:
                if (null !== $this->getFloor()) {
                    $context->buildViolation('Core.LendingRate.floor.illegal')->atPath('floor')->addViolation();
                }

                if (null !== $floorType) {
                    $context->buildViolation('Core.LendingRate.floorType.illegal')->atPath('floorType')->addViolation();
                }

                break;

            default:
                if (null !== $floorType && null === $this->getFloor()) {
                    $context->buildViolation('Core.LendingRate.floor.empty')->atPath('floor')->addViolation();
                }

                if (null === $floorType && null !== $this->getFloor()) {
                    $context->buildViolation('Core.LendingRate.floorType.empty')->atPath('floorType')->addViolation();
                }
        }
    }
}
