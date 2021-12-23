<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\CloneableTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\CreditGuaranty\FEI\DTO\ProgramEligibilityConfigurationInput;
use KLS\CreditGuaranty\FEI\Entity\Interfaces\DeepCloneInterface;
use KLS\CreditGuaranty\FEI\Entity\Interfaces\EquivalenceCheckerInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ApiResource(
 *     attributes={"pagination_enabled": false},
 *     normalizationContext={
 *         "groups": {
 *             "creditGuaranty:programEligibilityConfiguration:read",
 *             "timestampable:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "creditGuaranty:programEligibilityConfiguration:write",
 *         },
 *         "openapi_definition_name": "write",
 *     },
 *     itemOperations={
 *         "get": {
 *             "normalization_context": {
 *                 "groups": {
 *                     "creditGuaranty:programEligibilityConfiguration:read",
 *                     "creditGuaranty:programEligibilityCondition:read",
 *                     "timestampable:read",
 *                 },
 *                 "openapi_definition_name": "item-get-read",
 *             },
 *         },
 *         "patch": {"security": "is_granted('edit', object)"},
 *         "delete": {"security": "is_granted('delete', object)"},
 *     },
 *     collectionOperations={
 *         "post": {
 *             "input": ProgramEligibilityConfigurationInput::class,
 *             "security_post_denormalize": "is_granted('create', object)",
 *         },
 *     },
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"programEligibility.publicId"})
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="credit_guaranty_program_eligibility_configuration",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"id_program_eligibility", "id_program_choice_option"}),
 *         @ORM\UniqueConstraint(columns={"id_program_eligibility", "value"}),
 *     }
 * )
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"programChoiceOption", "programEligibility"})
 * @UniqueEntity({"value", "programEligibility"})
 */
class ProgramEligibilityConfiguration implements DeepCloneInterface, EquivalenceCheckerInterface
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use CloneableTrait;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramEligibility",
     *     inversedBy="programEligibilityConfigurations"
     * )
     * @ORM\JoinColumn(name="id_program_eligibility", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:programEligibilityConfiguration:read"})
     */
    private ProgramEligibility $programEligibility;

    /**
     * When its value is not null, it means that we configure the eligibility
     * based on the user's choice of the target field.
     * $programChoiceOption and $value cannot be both filed at the same time.
     *
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_program_choice_option", onDelete="CASCADE")
     */
    private ?ProgramChoiceOption $programChoiceOption;

    /**
     * When its value is not null, it means that we configure the eligibility based on the value of the target field.
     * $programChoiceOption and $value cannot be both filed at the same time.
     *
     * @ORM\Column(length=100, nullable=true)
     *
     * @Groups({"creditGuaranty:programEligibilityConfiguration:read"})
     */
    private ?string $value;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({
     *     "creditGuaranty:programEligibilityConfiguration:read",
     *     "creditGuaranty:programEligibilityConfiguration:write"
     * })
     */
    private bool $eligible;

    /**
     * @var Collection|ProgramEligibilityCondition[]
     *
     * @ApiSubresource
     *
     * @ORM\OneToMany(
     *     targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityCondition",
     *     mappedBy="programEligibilityConfiguration",
     *     orphanRemoval=true, fetch="EXTRA_LAZY",
     *     cascade={"persist", "remove"}
     * )
     */
    private Collection $programEligibilityConditions;

    public function __construct(
        ProgramEligibility $programEligibility,
        ?ProgramChoiceOption $programChoiceOption,
        ?string $value,
        bool $eligible
    ) {
        $this->programEligibility           = $programEligibility;
        $this->programChoiceOption          = $programChoiceOption;
        $this->value                        = $value;
        $this->eligible                     = $eligible;
        $this->programEligibilityConditions = new ArrayCollection();
        $this->added                        = new \DateTimeImmutable();
    }

    public function getProgramEligibility(): ProgramEligibility
    {
        return $this->programEligibility;
    }

    public function setProgramEligibility(ProgramEligibility $programEligibility): ProgramEligibilityConfiguration
    {
        $this->programEligibility = $programEligibility;

        return $this;
    }

    public function isEligible(): bool
    {
        return $this->eligible;
    }

    public function setEligible(bool $eligible): ProgramEligibilityConfiguration
    {
        $this->eligible = $eligible;

        return $this;
    }

    public function getProgramChoiceOption(): ?ProgramChoiceOption
    {
        return $this->programChoiceOption;
    }

    public function setProgramChoiceOption(?ProgramChoiceOption $programChoiceOption): ProgramEligibilityConfiguration
    {
        $this->programChoiceOption = $programChoiceOption;

        return $this;
    }

    /**
     * @Groups({"creditGuaranty:programEligibilityConfiguration:read"})
     */
    public function getDescription(): ?string
    {
        return $this->getProgramChoiceOption() ? $this->getProgramChoiceOption()->getDescription() : null;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @return Collection|ProgramEligibilityCondition[}
     */
    public function getProgramEligibilityConditions(): Collection
    {
        return $this->programEligibilityConditions;
    }

    /**
     * @Groups({"creditGuaranty:programEligibilityConfiguration:read"})
     */
    public function getProgramEligibilityConditionsCount(): int
    {
        return $this->getProgramEligibilityConditions()->count();
    }

    public function deepClone(): ProgramEligibilityConfiguration
    {
        $clonedProgramEligibilityConfiguration = clone $this;
        $clonedProgramEligibilityConditions    = new ArrayCollection();
        foreach ($this->programEligibilityConditions as $item) {
            // no need to do the deep clone for ProgramEligibilityCondition
            $clonedItem = clone $item;
            $clonedItem->setProgramEligibilityConfiguration($clonedProgramEligibilityConfiguration);
            $clonedProgramEligibilityConditions->add($clonedItem);
        }

        $clonedProgramEligibilityConfiguration->programEligibilityConditions = $clonedProgramEligibilityConditions;

        return $clonedProgramEligibilityConfiguration;
    }

    public function getEquivalenceChecker(): Closure
    {
        $self = $this;

        return static function (int $key, ProgramEligibilityConfiguration $pec) use ($self): bool {
            if ($pec->getProgramEligibility() !== $self->getProgramEligibility()) {
                return false;
            }
            if ($pec->getProgramChoiceOption()) {
                return $pec->getProgramChoiceOption() === $self->getProgramChoiceOption();
            }
            if ($pec->getValue()) {
                return $pec->getValue() === $self->getValue();
            }
            // If both are null, it's a configuration without value.
            // One ProgramEligibility can only have one configuration like that, so we return true.
            return true;
        };
    }

    /**
     * @Assert\Callback
     */
    public function validateConfiguration(ExecutionContextInterface $context): void
    {
        $criteriaType   = $this->getProgramEligibility()->getField()->getType();
        $violationPaths = [];

        switch ($criteriaType) {
            case Field::TYPE_OTHER:
                if (null !== $this->value) {
                    $violationPaths[] = 'value';
                }
                if (null !== $this->programChoiceOption) {
                    $violationPaths[] = 'programChoiceOption';
                }

                break;

            case Field::TYPE_BOOL:
                if (null === $this->value) {
                    $violationPaths[] = 'value';
                }
                if (null !== $this->programChoiceOption) {
                    $violationPaths[] = 'programChoiceOption';
                }

                break;

            case Field::TYPE_LIST:
                if (null !== $this->value) {
                    $violationPaths[] = 'value';
                }
                if (null === $this->programChoiceOption) {
                    $violationPaths[] = 'programChoiceOption';
                }

                break;

            default:
                $context->buildViolation('CreditGuaranty.ProgramEligibility.field.unsupportedType')
                    ->atPath('programEligibility.criteria')
                    ->addViolation()
                ;

                return;
        }

        if ($this->getProgramChoiceOption() instanceof ProgramChoiceOption) {
            if ($this->getProgramChoiceOption()->getProgram() !== $this->getProgramEligibility()->getProgram()) {
                $violationPaths[] = 'programChoiceOption.program';
            }

            if ($this->getProgramChoiceOption()->getField() !== $this->getProgramEligibility()->getField()) {
                $violationPaths[] = 'programChoiceOption.field';
            }
        }

        if (\count($violationPaths) > 0) {
            foreach ($violationPaths as $path) {
                $context->buildViolation('CreditGuaranty.ProgramEligibilityConfiguration.' . $path . '.invalid')
                    ->atPath($path)
                    ->addViolation()
                ;
            }
        }
    }
}
