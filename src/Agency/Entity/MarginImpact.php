<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

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
     * @var MarginRule
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\MarginRule", inversedBy="impacts")
     * @ORM\JoinColumn(name="id_margin_rule")
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
     * @var Tranche
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Tranche")
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
     * @var string
     *
     * @ORM\Column(type="decimal", precision=5, scale=4)
     *
     * @Assert\Type("numeric")
     * @Assert\NotBlank
     *
     * @Groups({"agency:marginImpact:read", "agency:marginImpact:create"})
     */
    private string $margin;

    /**
     * @param MarginRule $rule
     * @param Tranche    $tranche
     * @param string     $margin
     */
    public function __construct(MarginRule $rule, Tranche $tranche, string $margin)
    {
        $this->rule    = $rule;
        $this->tranche = $tranche;
        $this->margin  = $margin;
    }

    /**
     * @return MarginRule
     */
    public function getRule(): MarginRule
    {
        return $this->rule;
    }

    /**
     * @param MarginRule $rule
     *
     * @return MarginImpact
     */
    public function setRule(MarginRule $rule): MarginImpact
    {
        $this->rule = $rule;

        return $this;
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
     * @return MarginImpact
     */
    public function setTranche(Tranche $tranche): MarginImpact
    {
        $this->tranche = $tranche;

        return $this;
    }

    /**
     * @Groups({"agency:marginImpact:read"})
     *
     * @return string
     */
    public function getTrancheName(): string
    {
        return $this->getTranche()->getName();
    }

    /**
     * @return string
     */
    public function getMargin(): string
    {
        return $this->margin;
    }

    /**
     * @param string $margin
     *
     * @return MarginImpact
     */
    public function setMargin(string $margin): MarginImpact
    {
        $this->margin = $margin;

        return $this;
    }
}
