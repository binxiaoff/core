<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Agency\Entity\Embeddable\Inequality;
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
     * @Assert\Expression(expression="this.getCovenant().isFinancial()", message="Agency.MarginRule.inconsistentCovenant")
     */
    private Covenant $covenant;

    /**
     * @var Inequality
     *
     * @ORM\Embedded(class="Unilend\Agency\Entity\Embeddable\Inequality")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:marginRule:read", "agency:marginRule:write"})
     */
    private Inequality $inequality;

    /**
     * @var MarginImpact[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Agency\Entity\MarginImpact", mappedBy="marginRule", cascade={"persist"})
     *
     * @Assert\Valid
     * @Assert\Count(min="1")
     *
     * @Groups({"agency:marginRule:read"})
     */
    private Collection $impacts;

    /**
     * @param Covenant   $covenant
     * @param Inequality $inequality
     */
    public function __construct(Covenant $covenant, Inequality $inequality)
    {
        $this->covenant   = $covenant;
        $this->inequality = $inequality;
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
     * @return Inequality
     */
    public function getInequality(): Inequality
    {
        return $this->inequality;
    }

    /**
     * @param Inequality $inequality
     *
     * @return MarginRule
     */
    public function setInequality(Inequality $inequality): MarginRule
    {
        $this->inequality = $inequality;

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
}
