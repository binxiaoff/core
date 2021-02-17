<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
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
     * @ORM\ManyToOne(targetEntity="Unilend\Agency\Entity\Covenant", inversedBy="financialRules")
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
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\ExpressionLanguageSyntax
     * @Assert\NotBlank
     *
     * @Groups({"agency:covenantRule:read", "agency:covenantRule:write"})
     */
    private string $expression;

    /**
     * @param Covenant $covenant
     * @param int      $year
     * @param string   $expression
     */
    public function __construct(Covenant $covenant, int $year, string $expression)
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
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * @param string $expression
     *
     * @return CovenantRule
     */
    public function setExpression(string $expression): CovenantRule
    {
        $this->expression = $expression;

        return $this;
    }
}
