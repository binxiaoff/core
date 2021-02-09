<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ORM\Table(name="agency_covenant_rule")
 * @ORM\Entity
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
     */
    private Covenant $covenant;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank
     *
     * @Groups({"covenantRule:read"})
     */
    private string $year;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     *
     * @Groups({"covenant:read", "covenant:write"})
     */
    private string $expression;

    /**
     * @param Covenant $covenant
     * @param string   $year
     * @param string   $expression
     */
    public function __construct(Covenant $covenant, string $year, string $expression)
    {
        $this->covenant = $covenant;
        $this->year = $year;
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
     * @return string
     */
    public function getYear(): string
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
     */
    public function setExpression(string $expression): void
    {
        $this->expression = $expression;
    }
}
