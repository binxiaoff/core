<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Syndication\Agency\Entity\Embeddable\Inequality;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="agency_margin_rule")
 * @ORM\Entity
 *
 * @ApiResource(
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *             "openapi_context": {
 *                 "x-visibility": "hide",
 *             },
 *         }
 *     },
 *     collectionOperations={}
 * )
 */
class MarginRule
{
    use PublicizeIdentityTrait;

    /**
     * @ORM\ManyToOne(targetEntity=Covenant::class, inversedBy="marginRules")
     * @ORM\JoinColumn(name="id_covenant", nullable=false, onDelete="CASCADE")
     *
     * @Assert\NotBlank
     * @Assert\Expression(expression="this.getCovenant().isFinancial()", message="Agency.MarginRule.inconsistentCovenant")
     *
     * @Groups({"agency:marginRule:read", "agency:marginRule:create"})
     *
     * @ApiProperty(writableLink=false, readableLink=false)
     */
    private Covenant $covenant;

    /**
     * @ORM\Embedded(class=Inequality::class)
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:marginRule:read", "agency:marginRule:create"})
     */
    private Inequality $inequality;

    /**
     * @var MarginImpact[]|Collection
     *
     * @ORM\OneToMany(targetEntity=MarginImpact::class, mappedBy="rule", cascade={"persist"})
     *
     * @Assert\Valid
     * @Assert\Count(min="1")
     * @Assert\All({
     *     @Assert\Expression("value.getRule() === this")
     * })
     *
     * @Groups({"agency:marginRule:read", "agency:marginRule:create"})
     */
    private Collection $impacts;

    public function __construct(Covenant $covenant, Inequality $inequality)
    {
        $this->covenant   = $covenant;
        $this->inequality = $inequality;
        $this->impacts    = new ArrayCollection();
    }

    public function getCovenant(): Covenant
    {
        return $this->covenant;
    }

    public function getInequality(): Inequality
    {
        return $this->inequality;
    }

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

    public function addImpact(MarginImpact $impact): MarginRule
    {
        $this->impacts->add($impact);

        return $this;
    }

    public function removeImpact(MarginImpact $impact): MarginRule
    {
        $this->impacts->removeElement($impact);

        return $this;
    }
}
