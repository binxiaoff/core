<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
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
     * @var MarginImpact[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\MarginImpact", mappedBy="marginRule", cascade={"persist"})
     *
     * @Assert\Valid
     * @Assert\Count(min="1")
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
        $this->impacts    = new ArrayCollection();
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

    /**
     * @return Collection|MarginImpact[]
     */
    public function getImpacts()
    {
        return $this->impacts;
    }

    /**
     * @param MarginImpact $impact
     *
     * @return MarginRule
     */
    public function addImpact(MarginImpact $impact): MarginRule
    {
        if (false === $this->impacts->contains($impact)) {
            $this->impacts->add($impact);
        }

        return $this;
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     */
    private function validateCovenant(ExecutionContextInterface $context)
    {
        // non financial covenant must not have margin rules
        if (false === $this->covenant->isFinancial()) {
            $context->buildViolation('Agency.MarginRule.inconsistentCovenant')
                ->atPath('covenant')
                ->addViolation();
        }
    }
}
