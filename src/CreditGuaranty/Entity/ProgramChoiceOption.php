<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource};
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Constant\AbstractEnum;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};
use Unilend\CreditGuaranty\Entity\Constant\EligibilityFieldAlias;

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
 *          @ORM\UniqueConstraint(columns={"description", "field_alias", "id_program"})
 *      }
 * )
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"description", "fieldAlias", "program"})
 */
class ProgramChoiceOption
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program")
     * @ORM\JoinColumn(name="id_program", nullable=false)
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
     * @ORM\Column(length=100)
     *
     * @Assert\Choice(callback={EligibilityFieldAlias::class, "getListFields"})
     *
     * @Groups({"creditGuaranty:programChoiceOption:read"})
     */
    private string $fieldAlias;

    /**
     * @param Program $program
     * @param string  $description
     * @param string  $fieldAlias
     */
    public function __construct(Program $program, string $description, string $fieldAlias)
    {
        $this->program     = $program;
        $this->description = $description;
        $this->fieldAlias  = $fieldAlias;
        $this->added       = new \DateTimeImmutable();
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
     * @return string
     */
    public function getFieldAlias(): string
    {
        return $this->fieldAlias;
    }

    /**
     * If it's a pre-defined list, check whether the description is a pre-defined list's item.
     *
     * @return bool
     */
    public function isDescriptionValid(): bool
    {
        $preDefinedLists = EligibilityFieldAlias::getPredefinedListFields();
        if (false === array_key_exists($this->getFieldAlias(), $preDefinedLists)) {
            return true;
        }
        $constantClass = $preDefinedLists[$this->getFieldAlias()];
        if (is_subclass_of($constantClass, AbstractEnum::class)) {
            return in_array($this->description, $constantClass::getConstList(), true);
        }

        return true;
    }
}
