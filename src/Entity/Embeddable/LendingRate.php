<?php

declare(strict_types=1);

namespace Unilend\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
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

    public const FLOOR_TYPE_NONE       = 'none';
    public const FLOOR_TYPE_INDEX      = 'index';
    public const FLOOR_TYPE_INDEX_RATE = 'index+rate';
    /**
     * @var string|null
     *
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
     * @var string|null
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
     * @var string|null
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
     * @var string|null
     *
     * @ORM\Column(length=20, nullable=true)
     *
     * @Assert\Choice(callback="getFloorTypes")
     *
     * @Groups({"lendingRate:read", "lendingRate:write"})
     */
    protected ?string $floorType = null;

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
        $this->floor     = $floor;
        $this->floorType = $floorType;
    }

    /**
     * @return string|null
     */
    public function getIndexType(): ?string
    {
        return $this->indexType;
    }

    /**
     * @param string|null $indexType
     *
     * @return self
     */
    public function setIndexType(?string $indexType): self
    {
        $this->indexType = $indexType;
        if (self::INDEX_FIXED === $this->indexType) {
            $this->floor     = null;
            $this->floorType = null;
        }

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
     * @param string|null $margin
     *
     * @return self
     */
    public function setMargin(?string $margin): self
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

    /**
     * @return string|null
     */
    public function getFloorType(): ?string
    {
        return $this->floorType;
    }

    /**
     * @param string|null $floorType
     */
    public function setFloorType(?string $floorType): void
    {
        $this->floorType = $floorType;
    }

    /**
     * @return array
     */
    public static function getFloorTypes(): array
    {
        return self::getConstants('FLOOR_TYPE_');
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     */
    public function validate(ExecutionContextInterface $context): void
    {
        // Transfer the "none" to null to simplify the validation conditions, because they are equivalent in this context.
        $floorType = self::FLOOR_TYPE_NONE === $this->getFloorType() ? null : $this->getFloorType();

        switch ($this->getIndexType()) {
            case null:
                if ($this->getMargin() || $this->getFloor() || $floorType) {
                    $context->buildViolation('LendingRate.indexType.empty')->atPath('indexType')->addViolation();
                }
                break;
            case self::INDEX_FIXED:
                if (null !== $this->getFloor()) {
                    $context->buildViolation('LendingRate.floor.illegal')->atPath('floor')->addViolation();
                }

                if (null !== $floorType) {
                    $context->buildViolation('LendingRate.floorType.illegal')->atPath('floorType')->addViolation();
                }
                break;
            default:
                if (null !== $floorType && null === $this->getFloor()) {
                    $context->buildViolation('LendingRate.floor.empty')->atPath('floor')->addViolation();
                }

                if (null !== $this->getFloor() && null === $floorType) {
                    $context->buildViolation('LendingRate.floorType.empty')->atPath('floorType')->addViolation();
                }
        }
    }
}
