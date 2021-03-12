<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource};
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};
use Unilend\CreditGuaranty\Entity\ConstantList\EligibilityCriteria;

/**
 * @ApiResource(
 *      attributes={"pagination_enabled": false},
 *      normalizationContext={"groups":{"creditGuaranty:programChoiceOption:read"}},
 *      itemOperations={
 *          "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *          },
 *          "patch",
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
 *     name="credit_guaranty_program_choice_option",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"description", "id_eligibility_criteria", "id_program"})
 *      }
 * )
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"description", "eligibilityCriteria", "program"})
 */
class ProgramChoiceOption
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @Groups({"creditGuaranty:programChoiceOption:read"})
     */
    private Program $program;

    /**
     * @ORM\Column(length=255)
     *
     * @Groups({"creditGuaranty:programChoiceOption:read"})
     *
     * @Assert\Expression("this.isDescriptionValid()")
     */
    private string $description;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ConstantList\EligibilityCriteria")
     * @ORM\JoinColumn(name="id_eligibility_criteria", nullable=false)
     *
     * @Groups({"creditGuaranty:programChoiceOption:read"})
     */
    private EligibilityCriteria $eligibilityCriteria;

    /**
     * @param Program             $program
     * @param string              $description
     * @param EligibilityCriteria $eligibilityCriteria
     */
    public function __construct(Program $program, string $description, EligibilityCriteria $eligibilityCriteria)
    {
        $this->program             = $program;
        $this->description         = $description;
        $this->eligibilityCriteria = $eligibilityCriteria;
        $this->added               = new \DateTimeImmutable();
    }

    /**
     * @return Program
     */
    public function getProgram(): Program
    {
        return $this->program;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return ProgramChoiceOption
     */
    public function setDescription(string $description): ProgramChoiceOption
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return EligibilityCriteria
     */
    public function getEligibilityCriteria(): EligibilityCriteria
    {
        return $this->eligibilityCriteria;
    }

    /**
     * If it's a pre-defined list, check whether the description is a pre-defined list's item.
     *
     * @return bool
     */
    public function isDescriptionValid(): bool
    {
        if (EligibilityCriteria::TYPE_LIST === $this->getEligibilityCriteria()->getType() && null === $this->getEligibilityCriteria()->getPredefinedItems()) {
            return true;
        }

        return in_array($this->description, $this->getEligibilityCriteria()->getPredefinedItems(), true);
    }
}
