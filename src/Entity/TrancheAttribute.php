<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Embeddable\Attribute;
use Unilend\Entity\Traits\TimestampableTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Table(indexes={@ORM\Index(columns={"attribute_name"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\TrancheAttributeRepository")
 * @ORM\HasLifecycleCallbacks
 */
class TrancheAttribute
{
    use TimestampableTrait;
    use ConstantsAwareTrait;

    public const ATTRIBUTE_CREDIT_AGRICOLE_GREEN_ID  = 'credit_agricole_green_id';
    public const ATTRIBUTE_FONCARIS_FUNDING_TYPE     = 'foncaris_funding_type';
    public const ATTRIBUTE_FONCARIS_FUNDING_SECURITY = 'foncaris_funding_security';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Tranche
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Tranche", inversedBy="trancheAttributes")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_tranche", nullable=false)
     * })
     */
    private $tranche;

    /**
     * @var Attribute
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Attribute")
     */
    private $attribute;

    /**
     * @param string|null $name
     * @param string|null $value
     *
     * @throws \Exception
     */
    public function __construct(?string $name = null, ?string $value = null)
    {
        $this->attribute = new Attribute($name, $value);
        $this->added     = new DateTimeImmutable();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Tranche|null
     */
    public function getTranche(): ?Tranche
    {
        return $this->tranche;
    }

    /**
     * @param Tranche $tranche
     *
     * @return TrancheAttribute
     */
    public function setTranche(Tranche $tranche): TrancheAttribute
    {
        $this->tranche = $tranche;

        return $this;
    }

    /**
     * @return Attribute|null
     */
    public function getAttribute(): ?Attribute
    {
        return $this->attribute;
    }

    /**
     * @param Attribute $attribute
     *
     * @return TrancheAttribute
     */
    public function setAttribute(Attribute $attribute): TrancheAttribute
    {
        $this->attribute = $attribute;

        return $this;
    }
}
