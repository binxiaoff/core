<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Unilend\Entity\Embeddable\Fee;
use Unilend\Entity\Traits\TimestampableTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedTrancheFee")
 */
class TrancheFee
{
    use TimestampableTrait;
    use ConstantsAwareTrait;

    public const TYPE_NON_UTILISATION = 'non_utilisation';
    public const TYPE_COMMITMENT      = 'commitment';
    public const TYPE_UTILISATION     = 'utilisation';
    public const TYPE_FIRST_DRAWDOWN  = 'first_drawdown';

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
     * @Gedmo\Versioned
     */
    private $fee;

    /**
     * @var Tranche
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Tranche", inversedBy="trancheFees")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_tranche", nullable=false)
     * })
     */
    private $tranche;

    /**
     * Initialise some object-value.
     */
    public function __construct()
    {
        $this->fee = new Fee();
    }

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
     * @return TrancheFee
     */
    public function setTranche(Tranche $tranche): TrancheFee
    {
        $this->tranche = $tranche;

        return $this;
    }

    /**
     * @return Fee|null
     */
    public function getFee(): Fee
    {
        return $this->fee;
    }

    /**
     * @param Fee $fee
     *
     * @return TrancheFee
     */
    public function setFee(Fee $fee): TrancheFee
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * @return array
     */
    public static function getFeeTypes(): array
    {
        return self::getConstants('TYPE_');
    }

    /**
     * @param int $value
     *
     * @return false|string
     */
    public static function getFeeTypeConstantKey(int $value)
    {
        return self::getConstantKey($value, 'TYPE_');
    }
}
