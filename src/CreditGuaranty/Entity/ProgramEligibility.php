<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiProperty, ApiResource};
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};
use Unilend\CreditGuaranty\Entity\ConstantList\EligibilityCriteria;

/**
 * @ApiResource(
 *      attributes={"pagination_enabled": false},
 *      normalizationContext={"groups":{
 *          "creditGuaranty:programEligibility:read",
 *          "creditGuaranty:eligibilityCriteria:read",
 *          "creditGuaranty:programChoiceOption:read",
 *          "creditGuaranty:programEligibilityConfiguration:read",
 *          "timestampable:read"
 *      }},
 *      itemOperations={
 *          "get"
 *      },
 *      collectionOperations={
 *          "get",
 *          "post"
 *      }
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"program.publicId"})
 *
 * @ORM\Entity
 * @ORM\Table(
 *      name="credit_guaranty_program_eligibility",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"id_eligibility_criteria", "id_program"})
 *      }
 * )
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"eligibilityCriteria", "program"})
 */
class ProgramEligibility
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:programEligibility:read"})
     */
    private Program $program;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ConstantList\EligibilityCriteria")
     * @ORM\JoinColumn(name="id_eligibility_criteria", nullable=false)
     *
     * @Groups({"creditGuaranty:programEligibility:read"})
     */
    private EligibilityCriteria $eligibilityCriteria;

    /**
     * @var Collection|ProgramEligibilityConfiguration[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration", mappedBy="programEligibility", orphanRemoval=true)
     *
     * @Groups({"creditGuaranty:programEligibility:read"})
     */
    private Collection $programEligibilityConfigurations;

    /**
     * @param Program             $program
     * @param EligibilityCriteria $eligibilityCriteria
     */
    public function __construct(Program $program, EligibilityCriteria $eligibilityCriteria)
    {
        $this->program                          = $program;
        $this->eligibilityCriteria              = $eligibilityCriteria;
        $this->programEligibilityConfigurations = new ArrayCollection();
        $this->added                            = new \DateTimeImmutable();
    }

    /**
     * @return Program
     */
    public function getProgram(): Program
    {
        return $this->program;
    }

    /**
     * @return EligibilityCriteria
     */
    public function getEligibilityCriteria(): EligibilityCriteria
    {
        return $this->eligibilityCriteria;
    }

    /**
     * @return Collection|ProgramEligibilityConfiguration[]
     */
    public function getProgramEligibilityConfigurations()
    {
        return $this->programEligibilityConfigurations;
    }
}
