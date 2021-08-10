<?php

declare(strict_types=1);

namespace KLS\Agency\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use KLS\Agency\Entity\Embeddable\Inequality;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

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
     * @ORM\ManyToOne(targetEntity="KLS\Agency\Entity\Covenant", inversedBy="covenantRules")
     * @ORM\JoinColumn(name="id_covenant", nullable=false, onDelete="CASCADE")
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
     * @ORM\Embedded(class="KLS\Agency\Entity\Embeddable\Inequality")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     *
     * @Groups({"agency:covenantRule:read", "agency:covenantRule:create"})
     */
    private Inequality $inequality;

    public function __construct(Covenant $covenant, int $year, Inequality $inequality)
    {
        $this->covenant   = $covenant;
        $this->year       = $year;
        $this->inequality = $inequality;
    }

    public function getCovenant(): Covenant
    {
        return $this->covenant;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getInequality(): Inequality
    {
        return $this->inequality;
    }

    public function setInequality(Inequality $inequality): CovenantRule
    {
        $this->inequality = $inequality;

        return $this;
    }
}
