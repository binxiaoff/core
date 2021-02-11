<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Agency\Entity\Embeddable\Expression;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ORM\Table(name="agency_covenant_rule")
 * @ORM\Entity
 *
 * @UniqueEntity(fields={"year", "covenant"}, message="Agency.CovenantRule.yearUnicity")
 */
class CovenantRule
{
    use PublicizeIdentityTrait;

    /**
     * @var Covenant
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Covenant", inversedBy="covenantRules")
     * @ORM\JoinColumn(name="id_covenant")
     *
     * @Assert\NotBlank
     * @Assert\Expression("this.getCovenant().isFinancial()")
     */
    private Covenant $covenant;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     *
     * @Assert\NotBlank
     * @Assert\Positive
     * @Assert\Expression("value >= this.getCovenant().getStartYear() and value <= this.getCovenant().getEndYear()")
     *
     * @Groups({"agency:covenantRule:read"})
     */
    private int $year;

    /**
     * @var Expression
     *
     * @ORM\Embedded(class="Unilend\Agency\Entity\Embeddable\Expression")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:covenantRule:read", "agency:covenantRule:write"})
     */
    private Expression $expression;

    /**
     * @param Covenant   $covenant
     * @param int        $year
     * @param Expression $expression
     */
    public function __construct(Covenant $covenant, int $year, Expression $expression)
    {
        $this->covenant   = $covenant;
        $this->year       = $year;
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
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
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
     * @return CovenantRule
     */
    public function setExpression(Expression $expression): CovenantRule
    {
        $this->expression = $expression;

        return $this;
    }
}
