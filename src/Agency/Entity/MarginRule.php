<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Agency\Entity\Embeddable\Expression;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ORM\Table(name="agency_margin_rule")
 * @ORM\Entity
 */
class MarginRule
{
    use PublicizeIdentityTrait;

    /**
     * @var Covenant
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Covenant", inversedBy="marginRules")
     * @ORM\JoinColumn(name="id_covenant")
     *
     * @Assert\NotBlank
     */
    private Covenant $covenant;

    /**
     * @var Expression
     *
     * @ORM\Embedded(class="Unilend\Agency\Entity\Embeddable\Expression")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"marginRule:read", "marginRule:write"})
     */
    private Expression $expression;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\MarginImpact", mappedBy="marginRule")
     *
     * @Assert\Valid
     *
     * @Groups({"marginRule:read"})
     */
    private Collection $impacts;

    /**
     * @param Covenant   $covenant
     * @param Expression $expression
     */
    public function __construct(Covenant $covenant, Expression $expression)
    {
        $this->covenant   = $covenant;
        $this->expression = $expression;
    }

    /**
     * @return Covenant
     */
    public function getCovenant(): Covenant
    {
        return $this->covenant;
    }

    /**
     * @return Expression
     */
    public function getExpression(): Expression
    {
        return $this->expression;
    }

    /**
     * @param Expression $expression
     *
     * @return MarginRule
     */
    public function setExpression(Expression $expression): MarginRule
    {
        $this->expression = $expression;

        return $this;
    }
}
