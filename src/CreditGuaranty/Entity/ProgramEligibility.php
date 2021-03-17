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

/**
 * @ApiResource(
 *      attributes={"pagination_enabled": false},
 *      normalizationContext={"groups":{
 *          "creditGuaranty:programEligibility:read",
 *          "creditGuaranty:fieldConfiguration:read",
 *          "creditGuaranty:programChoiceOption:read",
 *          "creditGuaranty:programEligibilityConfiguration:read",
 *          "timestampable:read"
 *      }},
 *      itemOperations={
 *          "get",
 *          "delete"
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
 *          @ORM\UniqueConstraint(columns={"id_field_configuration", "id_program"})
 *      }
 * )
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"fieldConfiguration", "program"})
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
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\FieldConfiguration")
     * @ORM\JoinColumn(name="id_field_configuration", nullable=false)
     *
     * @Groups({"creditGuaranty:programEligibility:read"})
     */
    private FieldConfiguration $fieldConfiguration;

    /**
     * @var Collection|ProgramEligibilityConfiguration[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration", mappedBy="programEligibility", orphanRemoval=true)
     *
     * @Groups({"creditGuaranty:programEligibility:read"})
     */
    private Collection $programEligibilityConfigurations;

    /**
     * @param Program            $program
     * @param FieldConfiguration $fieldConfiguration
     */
    public function __construct(Program $program, FieldConfiguration $fieldConfiguration)
    {
        $this->program                          = $program;
        $this->fieldConfiguration               = $fieldConfiguration;
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
     * @return FieldConfiguration
     */
    public function getfieldConfiguration(): FieldConfiguration
    {
        return $this->fieldConfiguration;
    }

    /**
     * @return Collection|ProgramEligibilityConfiguration[]
     */
    public function getProgramEligibilityConfigurations()
    {
        return $this->programEligibilityConfigurations;
    }
}
