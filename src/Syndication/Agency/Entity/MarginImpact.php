<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="agency_margin_impact")
 * @ORM\Entity
 *
 * @UniqueEntity(fields={"rule", "tranche"}, message="Agency.MarginImpact.unicity")
 *
 * @ApiResource(
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *         }
 *     },
 *     collectionOperations={}
 * )
 */
class MarginImpact
{
    use PublicizeIdentityTrait;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Agency\Entity\MarginRule", inversedBy="impacts")
     * @ORM\JoinColumn(name="id_margin_rule", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:marginImpact:read", "agency:marginImpact:create"})
     *
     * @ApiProperty(writableLink=false, readableLink=false)
     */
    private MarginRule $rule;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Syndication\Agency\Entity\Tranche")
     * @ORM\JoinColumn(name="id_tranche", onDelete="CASCADE")
     *
     * @Assert\NotBlank
     * @Assert\Expression("value in this.getRule().getCovenant().getProject().getTranches().toArray()")
     *
     * @Groups({"agency:marginImpact:read", "agency:marginImpact:create"})
     *
     * @ApiProperty(writableLink=false, readableLink=false)
     */
    private Tranche $tranche;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=4)
     *
     * @Assert\Type("numeric")
     * @Assert\NotBlank
     *
     * @Groups({"agency:marginImpact:read", "agency:marginImpact:create"})
     */
    private string $margin;

    public function __construct(MarginRule $rule, Tranche $tranche, string $margin)
    {
        $this->rule    = $rule;
        $this->tranche = $tranche;
        $this->margin  = $margin;
    }

    public function getRule(): MarginRule
    {
        return $this->rule;
    }

    public function setRule(MarginRule $rule): MarginImpact
    {
        $this->rule = $rule;

        return $this;
    }

    public function getTranche(): Tranche
    {
        return $this->tranche;
    }

    public function setTranche(Tranche $tranche): MarginImpact
    {
        $this->tranche = $tranche;

        return $this;
    }

    /**
     * @Groups({"agency:marginImpact:read"})
     */
    public function getTrancheName(): string
    {
        return $this->getTranche()->getName();
    }

    public function getMargin(): string
    {
        return $this->margin;
    }

    public function setMargin(string $margin): MarginImpact
    {
        $this->margin = $margin;

        return $this;
    }
}
