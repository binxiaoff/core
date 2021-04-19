<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\{ApiProperty, ApiResource};
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
 *          "creditGuaranty:field:read",
 *          "creditGuaranty:programEligibilityConfiguration:read",
 *          "timestampable:read"
 *      }},
 *     denormalizationContext={"groups":{"creditGuaranty:programEligibility:write"}},
 *      itemOperations={
 *          "get": {
 *              "normalization_context": {
 *                  "groups":{
 *                      "creditGuaranty:programEligibility:read",
 *                      "creditGuaranty:field:read",
 *                      "creditGuaranty:programChoiceOption:read",
 *                      "creditGuaranty:programEligibilityConfiguration:read",
 *                      "timestampable:read"
 *                  }
 *              }
 *          },
 *          "delete"
 *      },
 *      collectionOperations={
 *          "post"
 *      }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(
 *      name="credit_guaranty_program_eligibility",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"id_field", "id_program"})
 *      }
 * )
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"field", "program"})
 */
class ProgramEligibility
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program", inversedBy="programEligibilities")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:programEligibility:read", "creditGuaranty:programEligibility:write"})
     */
    private Program $program;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Field")
     * @ORM\JoinColumn(name="id_field", nullable=false)
     *
     * @Groups({"creditGuaranty:programEligibility:read", "creditGuaranty:programEligibility:write"})
     */
    private Field $field;

    /**
     * @var Collection|ProgramEligibilityConfiguration[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration", mappedBy="programEligibility", orphanRemoval=true, fetch="EXTRA_LAZY")
     *
     * @Groups({"creditGuaranty:programEligibility:read"})
     */
    private Collection $programEligibilityConfigurations;

    /**
     * @param Program $program
     * @param Field   $field
     */
    public function __construct(Program $program, Field $field)
    {
        $this->program                          = $program;
        $this->field                            = $field;
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
     * @return Field
     */
    public function getField(): Field
    {
        return $this->field;
    }

    /**
     * @return Collection|ProgramEligibilityConfiguration[]
     */
    public function getProgramEligibilityConfigurations()
    {
        return $this->programEligibilityConfigurations;
    }
}
