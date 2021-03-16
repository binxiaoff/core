<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Agency\Entity\Embeddable\Inequality;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;

/**
 * @ORM\Table(name="agency_covenant_rule")
 * @ORM\Entity
 *
 * @UniqueEntity(fields={"year", "covenant"}, message="Agency.CovenantRule.yearUnicity")
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
     *
     * @Groups({"agency:covenantRule:create"})
     *
     * @ApiProperty(writableLink=false, readableLink=false)
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
     * @Groups({"agency:covenantRule:read", "agency:covenantRule:create"})
     */
    private int $year;

    /**
     * @var Inequality
     *
     * @ORM\Embedded(class="Unilend\Agency\Entity\Embeddable\Inequality")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:covenantRule:read", "agency:covenantRule:create"})
     */
    private Inequality $inequality;

    /**
     * @param Covenant   $covenant
     * @param int        $year
     * @param Inequality $inequality
     */
    public function __construct(Covenant $covenant, int $year, Inequality $inequality)
    {
        $this->covenant   = $covenant;
        $this->year       = $year;
        $this->inequality = $inequality;
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
     * @return Inequality
     */
    public function getInequality(): Inequality
    {
        return $this->inequality;
    }

    /**
     * @param Inequality $inequality
     *
     * @return CovenantRule
     */
    public function setInequality(Inequality $inequality): CovenantRule
    {
        $this->inequality = $inequality;

        return $this;
    }
}
