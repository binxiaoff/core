<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\CloneableTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\CreditGuaranty\FEI\Entity\Interfaces\DeepCloneInterface;
use KLS\CreditGuaranty\FEI\Entity\Interfaces\EquivalenceCheckerInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={"pagination_enabled": false},
 *     normalizationContext={
 *         "groups": {
 *             "creditGuaranty:programEligibility:read",
 *             "creditGuaranty:field:read",
 *             "creditGuaranty:programEligibilityConfiguration:read",
 *             "timestampable:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "creditGuaranty:programEligibility:write",
 *         },
 *         "openapi_definition_name": "write",
 *     },
 *     itemOperations={
 *         "get": {"security": "is_granted('view', object)"},
 *         "delete": {"security": "is_granted('delete', object)"},
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "creditGuaranty:programEligibility:write",
 *                     "creditGuaranty:programEligibility:create",
 *                 },
 *                 "openapi_definition_name": "collection-post-write",
 *             },
 *         },
 *     },
 * )
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="credit_guaranty_program_eligibility",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"id_field", "id_program"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"field", "program"})
 */
class ProgramEligibility implements DeepCloneInterface, EquivalenceCheckerInterface
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use CloneableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Program", inversedBy="programEligibilities")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:programEligibility:read", "creditGuaranty:programEligibility:create"})
     */
    private Program $program;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Field")
     * @ORM\JoinColumn(name="id_field", nullable=false)
     *
     * @Groups({"creditGuaranty:programEligibility:read", "creditGuaranty:programEligibility:create"})
     */
    private Field $field;

    /**
     * @var Collection|ProgramEligibilityConfiguration[]
     *
     * @ApiProperty(security="is_granted('manager', object)")
     *
     * @ORM\OneToMany(
     *     targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration",
     *     mappedBy="programEligibility", orphanRemoval=true, fetch="EXTRA_LAZY", cascade={"persist", "remove"}
     * )
     *
     * @Groups({"creditGuaranty:programEligibility:read"})
     */
    private Collection $programEligibilityConfigurations;

    public function __construct(Program $program, Field $field)
    {
        $this->program                          = $program;
        $this->field                            = $field;
        $this->programEligibilityConfigurations = new ArrayCollection();
        $this->added                            = new \DateTimeImmutable();
        $this->initialiseConfigurations();
    }

    public function getProgram(): Program
    {
        return $this->program;
    }

    public function setProgram(Program $program): ProgramEligibility
    {
        $this->program = $program;

        return $this;
    }

    public function getField(): Field
    {
        return $this->field;
    }

    /**
     * @Groups({"creditGuaranty:programEligibility:read"})
     */
    public function getFieldCategory(): string
    {
        return $this->field->getCategory();
    }

    /**
     * @return Collection|ProgramEligibilityConfiguration[]
     */
    public function getProgramEligibilityConfigurations()
    {
        return $this->programEligibilityConfigurations;
    }

    public function addProgramEligibilityConfiguration(
        ProgramEligibilityConfiguration $programEligibilityConfiguration
    ): ProgramEligibility {
        if (
            false === $this->programEligibilityConfigurations->exists(
                $programEligibilityConfiguration->getEquivalenceChecker()
            )
        ) {
            $this->programEligibilityConfigurations->add($programEligibilityConfiguration);
        }

        return $this;
    }

    public function removeProgramEligibilityConfiguration(
        ProgramEligibilityConfiguration $programEligibilityConfiguration
    ): ProgramEligibility {
        $this->programEligibilityConfigurations->removeElement($programEligibilityConfiguration);

        return $this;
    }

    public function deepClone(): ProgramEligibility
    {
        $clonedProgramEligibility               = clone $this;
        $clonedProgramEligibilityConfigurations = new ArrayCollection();
        foreach ($this->programEligibilityConfigurations as $item) {
            if (false === $item instanceof DeepCloneInterface) {
                throw new \LogicException(
                    \sprintf(
                        'Make sure that class %s implements %s',
                        \get_class($item),
                        DeepCloneInterface::class
                    )
                );
            }
            $clonedItem = $item->deepClone();
            $clonedItem->setProgramEligibility($clonedProgramEligibility);
            $clonedProgramEligibilityConfigurations->add($clonedItem);
        }

        $clonedProgramEligibility->programEligibilityConfigurations = $clonedProgramEligibilityConfigurations;

        return $clonedProgramEligibility;
    }

    public function getEquivalenceChecker(): Closure
    {
        $self = $this;

        return static function (int $key, ProgramEligibility $pe) use ($self): bool {
            return $pe->getProgram() === $self->getProgram()
                && $pe->getField()   === $self->getField();
        };
    }

    /**
     * Auto-configure the new-created eligibility.
     */
    private function initialiseConfigurations(): void
    {
        $field = $this->getField();

        switch ($field->getType()) {
            case Field::TYPE_OTHER:
                // if CASA configures an "other" type field, it means that he expects its value to be filled in
                // thus, we always create an eligible configuration to make it required
                $this->addProgramEligibilityConfiguration(new ProgramEligibilityConfiguration($this, null, null, true));

                break;

            case Field::TYPE_BOOL:
                $this->addProgramEligibilityConfiguration(
                    new ProgramEligibilityConfiguration($this, null, Field::VALUE_BOOL_YES, true)
                );
                $this->addProgramEligibilityConfiguration(
                    new ProgramEligibilityConfiguration($this, null, Field::VALUE_BOOL_NO, true)
                );

                break;

            case Field::TYPE_LIST:
                $programChoiceOptions = $this->getProgram()->getProgramChoiceOptions()->filter(
                    fn (ProgramChoiceOption $programChoiceOption) => $programChoiceOption->getField() === $field
                );
                foreach ($programChoiceOptions as $programChoiceOption) {
                    $this->addProgramEligibilityConfiguration(
                        new ProgramEligibilityConfiguration($this, $programChoiceOption, null, true)
                    );
                }

                break;

            default:
                throw new \UnexpectedValueException('The field type is not supported.');
        }
    }
}
