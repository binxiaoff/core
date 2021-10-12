<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Constant\MathOperator;
use KLS\Core\Entity\Traits\CloneableTrait;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\Core\Traits\ConstantsAwareTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ApiResource(
 *     attributes={"pagination_enabled": false},
 *     normalizationContext={
 *         "groups": {
 *             "creditGuaranty:programEligibilityCondition:read",
 *             "creditGuaranty:field:read",
 *             "timestampable:read",
 *         },
 *         "openapi_definition_name": "read",
 *     },
 *     denormalizationContext={
 *         "groups": {
 *             "creditGuaranty:programEligibilityCondition:write",
 *         },
 *         "openapi_definition_name": "write",
 *     },
 *     itemOperations={
 *         "get": {
 *             "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *             "read": false,
 *             "output": false,
 *             "openapi_context": {
 *                 "x-visibility": "hide",
 *             },
 *         },
 *         "patch": {"security": "is_granted('edit', object)"},
 *         "delete": {"security": "is_granted('delete', object)"},
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)",
 *             "denormalization_context": {
 *                 "groups": {
 *                     "creditGuaranty:programEligibilityCondition:write",
 *                     "creditGuaranty:programEligibilityCondition:create",
 *                 },
 *                 "openapi_definition_name": "collection-post-write",
 *             },
 *         },
 *     },
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_program_eligibility_condition")
 * @ORM\HasLifecycleCallbacks
 */
class ProgramEligibilityCondition
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use ConstantsAwareTrait;
    use CloneableTrait;

    public const VALUE_TYPE_RATE  = 'rate';
    public const VALUE_TYPE_VALUE = 'value';
    public const VALUE_TYPE_BOOL  = 'bool';
    public const VALUE_TYPE_LIST  = 'list';

    public const ALLOWED_VALUE_TYPES_FOR_VALUE = [
        self::VALUE_TYPE_RATE,
        self::VALUE_TYPE_VALUE,
        self::VALUE_TYPE_BOOL,
    ];

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration", inversedBy="programEligibilityConditions")
     * @ORM\JoinColumn(name="id_program_eligibility_configuration", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:programEligibilityCondition:read", "creditGuaranty:programEligibilityCondition:create"})
     */
    private ProgramEligibilityConfiguration $programEligibilityConfiguration;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Field")
     * @ORM\JoinColumn(name="id_left_operand_field", nullable=false)
     *
     * @Groups({"creditGuaranty:programEligibilityCondition:read", "creditGuaranty:programEligibilityCondition:write"})
     */
    private Field $leftOperandField;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\Field")
     * @ORM\JoinColumn(name="id_right_operand_field")
     *
     * @Groups({"creditGuaranty:programEligibilityCondition:read", "creditGuaranty:programEligibilityCondition:write"})
     */
    private ?Field $rightOperandField;

    /**
     * @ORM\Column(length=10)
     *
     * @Assert\Choice(callback="getAvailableOperations")
     *
     * @Groups({"creditGuaranty:programEligibilityCondition:read", "creditGuaranty:programEligibilityCondition:write"})
     */
    private string $operation;

    /**
     * @ORM\Column(length=20)
     *
     * @Assert\Choice(callback="getAvailableValueType")
     *
     * @Groups({"creditGuaranty:programEligibilityCondition:read", "creditGuaranty:programEligibilityCondition:write"})
     */
    private string $valueType;

    /**
     * The value to compare in case of type "value" or "bool",
     * or the value to calculate for comparison in case of type "rate".
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("numeric")
     *
     * @Groups({"creditGuaranty:programEligibilityCondition:read", "creditGuaranty:programEligibilityCondition:write"})
     */
    private ?string $value = null;

    /**
     * The option to compare in case of type "list".
     *
     * @ORM\ManyToOne(targetEntity=ProgramChoiceOption::class)
     * @ORM\JoinColumn(name="id_program_choice_option")
     *
     * @Groups({"creditGuaranty:programEligibilityCondition:read", "creditGuaranty:programEligibilityCondition:write"})
     */
    private ?ProgramChoiceOption $programChoiceOption;

    public function __construct(
        ProgramEligibilityConfiguration $programEligibilityConfiguration,
        Field $leftOperandField,
        ?Field $rightOperandField,
        string $operation,
        string $valueType
    ) {
        $this->programEligibilityConfiguration = $programEligibilityConfiguration;
        $this->leftOperandField                = $leftOperandField;
        $this->rightOperandField               = $rightOperandField;
        $this->operation                       = $operation;
        $this->valueType                       = $valueType;
        $this->added                           = new DateTimeImmutable();
    }

    public function getProgramEligibilityConfiguration(): ProgramEligibilityConfiguration
    {
        return $this->programEligibilityConfiguration;
    }

    public function setProgramEligibilityConfiguration(
        ProgramEligibilityConfiguration $programEligibilityConfiguration
    ): ProgramEligibilityCondition {
        $this->programEligibilityConfiguration = $programEligibilityConfiguration;

        return $this;
    }

    public function getLeftOperandField(): Field
    {
        return $this->leftOperandField;
    }

    public function setLeftOperandField(Field $leftOperandField): ProgramEligibilityCondition
    {
        $this->leftOperandField = $leftOperandField;

        return $this;
    }

    public function getRightOperandField(): ?Field
    {
        return $this->rightOperandField;
    }

    public function setRightOperandField(?Field $rightOperandField): ProgramEligibilityCondition
    {
        $this->rightOperandField = $rightOperandField;

        return $this;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function setOperation(string $operation): ProgramEligibilityCondition
    {
        $this->operation = $operation;

        return $this;
    }

    public function getValueType(): string
    {
        return $this->valueType;
    }

    public function setValueType(string $valueType): ProgramEligibilityCondition
    {
        $this->valueType = $valueType;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): ProgramEligibilityCondition
    {
        $this->value = $value;

        return $this;
    }

    public function getProgramChoiceOption(): ?ProgramChoiceOption
    {
        return $this->programChoiceOption;
    }

    public function setProgramChoiceOption(?ProgramChoiceOption $programChoiceOption): ProgramEligibilityCondition
    {
        $this->programChoiceOption = $programChoiceOption;

        return $this;
    }

    public static function getAvailableOperations(): array
    {
        $operations = MathOperator::getConstList();
        if (isset($operations['BETWEEN'])) {
            unset($operations['BETWEEN']);
        }

        return $operations;
    }

    public static function getAvailableValueType(): array
    {
        return static::getConstants('VALUE_TYPE_');
    }

    /**
     * @Assert\Callback
     */
    public function validateOperandFields(ExecutionContextInterface $context): void
    {
        $leftOperandField = $this->getLeftOperandField();

        if (false === $leftOperandField->isComparable()) {
            $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.operandField.notComparable')
                ->atPath('leftOperandField')
                ->addViolation()
            ;
        }

        $rightOperandField = $this->getRightOperandField();

        if ($rightOperandField instanceof Field) {
            if (self::VALUE_TYPE_RATE !== $this->getValueType()) {
                $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.rightOperandField.notEmpty')
                    ->atPath('rightOperandField')
                    ->addViolation()
                ;
            }

            if (false === $rightOperandField->isComparable()) {
                $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.operandField.notComparable')
                    ->atPath('rightOperandField')
                    ->addViolation()
                ;
            }

            if ($rightOperandField === $leftOperandField) {
                $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.rightOperandField.selfComparaison')
                    ->atPath('rightOperandField')
                    ->addViolation()
                ;
            }

            if ($rightOperandField->getUnit() !== $leftOperandField->getUnit()) {
                $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.operandField.notComparableUnit')
                    ->atPath('rightOperandField')
                    ->addViolation()
                ;
            }
        } elseif (self::VALUE_TYPE_RATE === $this->getValueType()) {
            $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.rightOperandField.required')
                ->atPath('rightOperandField')
                ->addViolation()
            ;
        }

        if (self::VALUE_TYPE_BOOL === $this->getValueType() && Field::TYPE_BOOL !== $leftOperandField->getType()) {
            $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.leftOperandField.notBool')
                ->atPath('leftOperandField')
                ->addViolation()
            ;
        }

        if (self::VALUE_TYPE_BOOL !== $this->getValueType() && Field::TYPE_BOOL === $leftOperandField->getType()) {
            $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.valueType.notBool')
                ->atPath('valueType')
                ->addViolation()
            ;
        }

        if (self::VALUE_TYPE_LIST === $this->getValueType() && Field::TYPE_LIST !== $leftOperandField->getType()) {
            $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.leftOperandField.notList')
                ->atPath('leftOperandField')
                ->addViolation()
            ;
        }

        if (self::VALUE_TYPE_LIST !== $this->getValueType() && Field::TYPE_LIST === $leftOperandField->getType()) {
            $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.valueType.notList')
                ->atPath('valueType')
                ->addViolation()
            ;
        }
    }

    /**
     * @Assert\Callback
     */
    public function validateValue(ExecutionContextInterface $context): void
    {
        $valueType = $this->getValueType();
        $value     = $this->getValue();

        if (false === \in_array($valueType, self::ALLOWED_VALUE_TYPES_FOR_VALUE)) {
            if (null !== $value) {
                $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.value.notEmpty')
                    ->atPath('value')
                    ->addViolation()
                ;
            }

            return;
        }

        if (null === $value) {
            $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.value.required')
                ->atPath('value')
                ->addViolation()
            ;

            return;
        }

        if (self::VALUE_TYPE_RATE === $valueType) {
            $value = (float) $value;

            if ($value > 1 || $value < 0) {
                $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.value.outOfRange')
                    ->atPath('value')
                    ->addViolation()
                ;
            }
        }

        if (self::VALUE_TYPE_BOOL === $valueType) {
            if (MathOperator::EQUAL !== $this->getOperation()) {
                $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.operation.notEqual')
                    ->atPath('operation')
                    ->addViolation()
                ;
            }
            if ('1' !== $value && '0' !== $value) {
                $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.value.boolean')
                    ->atPath('value')
                    ->addViolation()
                ;
            }
        }
    }

    /**
     * @Assert\Callback
     */
    public function validateProgramChoiceOption(ExecutionContextInterface $context): void
    {
        $valueType           = $this->getValueType();
        $programChoiceOption = $this->getProgramChoiceOption();

        if (\in_array($valueType, self::ALLOWED_VALUE_TYPES_FOR_VALUE)) {
            if (null !== $programChoiceOption) {
                $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.programChoiceOption.notEmpty')
                    ->atPath('programChoiceOption')
                    ->addViolation()
                ;
            }

            return;
        }

        if (false === ($programChoiceOption instanceof ProgramChoiceOption)) {
            $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.programChoiceOption.required')
                ->atPath('programChoiceOption')
                ->addViolation()
            ;
        }

        if (MathOperator::EQUAL !== $this->getOperation()) {
            $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.operation.notEqual')
                ->atPath('operation')
                ->addViolation()
            ;
        }
    }
}
