<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Traits\CloneableTrait;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;

/**
 * @ApiResource(
 *     attributes={"pagination_enabled": false},
 *     normalizationContext={"groups": {
 *         "creditGuaranty:programEligibility:read",
 *         "creditGuaranty:field:read",
 *         "creditGuaranty:programEligibilityConfiguration:read",
 *         "timestampable:read"
 *     }},
 *     denormalizationContext={"groups": {"creditGuaranty:programEligibility:write"}},
 *     itemOperations={
 *         "get": {
 *             "normalization_context": {
 *                 "groups": {
 *                     "creditGuaranty:programEligibility:read",
 *                     "creditGuaranty:field:read",
 *                     "creditGuaranty:programChoiceOption:read",
 *                     "creditGuaranty:programEligibilityConfiguration:read",
 *                     "timestampable:read"
 *                 }
 *             }
 *         },
 *         "delete"
 *     },
 *     collectionOperations={
 *         "post": {
 *             "denormalization_context": {"groups": {"creditGuaranty:programEligibility:write", "creditGuaranty:programEligibility:create"}}
 *         }
 *     }
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
class ProgramEligibility
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use CloneableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Program", inversedBy="programEligibilities")
     * @ORM\JoinColumn(name="id_program", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:programEligibility:read", "creditGuaranty:programEligibility:create"})
     */
    private Program $program;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Field")
     * @ORM\JoinColumn(name="id_field", nullable=false)
     *
     * @Groups({"creditGuaranty:programEligibility:read", "creditGuaranty:programEligibility:create"})
     */
    private Field $field;

    /**
     * @var Collection|ProgramEligibilityConfiguration[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration",
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
     * @return Collection|ProgramEligibilityConfiguration[]
     */
    public function getProgramEligibilityConfigurations()
    {
        return $this->programEligibilityConfigurations;
    }

    public function removeProgramEligibilityConfiguration(ProgramEligibilityConfiguration $programEligibilityConfiguration): ProgramEligibility
    {
        $this->programEligibilityConfigurations->removeElement($programEligibilityConfiguration);

        return $this;
    }

    public function addProgramEligibilityConfiguration(ProgramEligibilityConfiguration $programEligibilityConfiguration): ProgramEligibility
    {
        $callback = static function (int $key, ProgramEligibilityConfiguration $existingProgramEligibilityConfiguration) use ($programEligibilityConfiguration): bool {
            if ($existingProgramEligibilityConfiguration->getProgramChoiceOption()) {
                return $existingProgramEligibilityConfiguration->getProgramChoiceOption() === $programEligibilityConfiguration->getProgramChoiceOption();
            }
            if ($existingProgramEligibilityConfiguration->getValue()) {
                return $existingProgramEligibilityConfiguration->getValue() === $programEligibilityConfiguration->getValue();
            }
            // If both are null, it's a configuration without value. One ProgramEligibility can only have one configuration like that, so we return true.
            return true;
        };

        if (
            $programEligibilityConfiguration->getProgramEligibility() === $this
            && false === $this->programEligibilityConfigurations->exists($callback)
        ) {
            $this->programEligibilityConfigurations->add($programEligibilityConfiguration);
        }

        return $this;
    }

    protected function onClone(): void
    {
        $clonedProgramEligibilityConfigurations = new ArrayCollection();
        foreach ($this->programEligibilityConfigurations as $item) {
            $clonedItem = clone $item;
            $clonedItem->setProgramEligibility($this);
            $clonedProgramEligibilityConfigurations->add($clonedItem);
        }

        $this->programEligibilityConfigurations = $clonedProgramEligibilityConfigurations;
    }

    private function initialiseConfigurations(): void
    {
        $field = $this->getField();
        // auto-configure the new-created eligibility
        switch ($field->getType()) {
            // For the "other", the only reason that it's added to the program is to let the target field be required, thus we set always its eligible to true.
            case Field::TYPE_OTHER:
                $this->addProgramEligibilityConfiguration(new ProgramEligibilityConfiguration($this, null, null, true));

                break;

            case Field::TYPE_BOOL:
                $this->addProgramEligibilityConfiguration(new ProgramEligibilityConfiguration($this, null, Field::VALUE_BOOL_YES, true));
                $this->addProgramEligibilityConfiguration(new ProgramEligibilityConfiguration($this, null, Field::VALUE_BOOL_NO, true));

                break;

            case Field::TYPE_LIST:
                $programChoiceOptions = $this->getProgram()->getProgramChoiceOptions()->filter(
                    fn (ProgramChoiceOption $programChoiceOption) => $programChoiceOption->getField() === $field
                );
                foreach ($programChoiceOptions as $programChoiceOption) {
                    $this->addProgramEligibilityConfiguration(new ProgramEligibilityConfiguration($this, $programChoiceOption, null, true));
                }

                break;

            default:
                throw new \UnexpectedValueException('The field type is not supported.');
        }
    }
}
