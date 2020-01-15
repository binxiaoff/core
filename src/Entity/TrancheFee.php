<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Entity\Embeddable\Fee;
use Unilend\Entity\Traits\TimestampableTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object.getTranche().getProject())"}
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedTrancheFee")
 */
class TrancheFee
{
    use TimestampableTrait;
    use ConstantsAwareTrait;

    private const TYPE_NON_UTILISATION = 'non_utilisation';
    private const TYPE_COMMITMENT      = 'commitment';

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
     *
     * @Assert\Valid
     *
     * @Groups({"project:view", "trancheFee:read", "trancheFee:write"})
     */
    private $fee;

    /**
     * @var Tranche
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Tranche", inversedBy="trancheFees")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_tranche", nullable=false)
     * })
     *
     * @Assert\Valid
     */
    private $tranche;

    /**
     * @param Fee $fee
     *
     * @throws Exception
     */
    public function __construct(Fee $fee)
    {
        $this->fee   = $fee;
        $this->added = new DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getId(): int
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
     * @return Fee
     */
    public function getFee(): Fee
    {
        return $this->fee;
    }

    /**
     * @return string|null
     *
     * @Assert\Choice(callback="getFeeTypes")
     */
    public function getFeeType(): string
    {
        return $this->fee->getType();
    }

    /**
     * @return array|string[]
     */
    public function getFeeTypes(): array
    {
        return static::getConstants('TYPE_');
    }
}
