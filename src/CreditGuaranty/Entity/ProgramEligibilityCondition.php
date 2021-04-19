<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Core\Entity\Constant\MathOperator;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ApiResource(
 *      attributes={"pagination_enabled": false},
 *      normalizationContext={"groups": {"creditGuaranty:programEligibilityCondition:read", "creditGuaranty:field:read", "timestampable:read"}},
 *      denormalizationContext={"groups": {"creditGuaranty:programEligibilityCondition:write"}},
 *      itemOperations={
 *          "get": {
 *              "controller": "ApiPlatform\Core\Action\NotFoundAction",
 *              "read": false,
 *              "output": false,
 *          },
 *          "patch",
 *          "delete"
 *      },
 *      collectionOperations={
 *          "post"
 *      }
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

    public const VALUE_TYPE_RATE  = 'rate';
    public const VALUE_TYPE_VALUE = 'value';

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration", inversedBy="programEligibilityConditions")
     * @ORM\JoinColumn(name="id_program_eligibility_configuration", nullable=false)
     *
     * @Groups({"creditGuaranty:programEligibilityCondition:write"})
     */
    private ProgramEligibilityConfiguration $programEligibilityConfiguration;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Field")
     * @ORM\JoinColumn(name="id_left_operand_field", nullable=false)
     *
     * @Groups({"creditGuaranty:programEligibilityCondition:read", "creditGuaranty:programEligibilityCondition:write"})
     */
    private Field $leftOperandField;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Field")
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
     * It stocks the value to compare if the type is "value", otherwise it stocks the rate.
     *
     * @ORM\Column(type="decimal", precision=15, scale=2)
     *
     * @Assert\Expression(
     *     "(value <= 1 && value >= 0) || constant('Unilend\\CreditGuaranty\\Entity\\ProgramEligibilityCondition::VALUE_TYPE_VALUE') === this.getValueType()",
     *     message="CreditGuaranty.ProgramEligibilityCondition.value.outOfRange"
     * )
     *
     * @Groups({"creditGuaranty:programEligibilityCondition:read", "creditGuaranty:programEligibilityCondition:write"})
     */
    private string $value;

    /**
     * @param ProgramEligibilityConfiguration $programEligibilityConfiguration
     * @param Field                           $leftOperandField
     * @param ?Field                          $rightOperandField
     * @param string                          $operation
     * @param string                          $valueType
     * @param string                          $value
     */
    public function __construct(
        ProgramEligibilityConfiguration $programEligibilityConfiguration,
        Field $leftOperandField,
        ?Field $rightOperandField,
        string $operation,
        string $valueType,
        string $value
    ) {
        $this->programEligibilityConfiguration = $programEligibilityConfiguration;
        $this->leftOperandField                = $leftOperandField;
        $this->rightOperandField               = $rightOperandField;
        $this->operation                       = $operation;
        $this->valueType                       = $valueType;
        $this->value                           = $value;
        $this->added                           = new \DateTimeImmutable();
    }

    /**
     * @return ProgramEligibilityConfiguration
     */
    public function getProgramEligibilityConfiguration(): ProgramEligibilityConfiguration
    {
        return $this->programEligibilityConfiguration;
    }

    /**
     * @return Field
     */
    public function getLeftOperandField(): Field
    {
        return $this->leftOperandField;
    }

    /**
     * @param Field $leftOperandField
     *
     * @return ProgramEligibilityCondition
     */
    public function setLeftOperandField(Field $leftOperandField): ProgramEligibilityCondition
    {
        $this->leftOperandField = $leftOperandField;

        return $this;
    }

    /**
     * @return Field|null
     */
    public function getRightOperandField(): ?Field
    {
        return $this->rightOperandField;
    }

    /**
     * @param Field|null $rightOperandField
     *
     * @return ProgramEligibilityCondition
     */
    public function setRightOperandField(?Field $rightOperandField): ProgramEligibilityCondition
    {
        $this->rightOperandField = $rightOperandField;

        return $this;
    }

    /**
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * @param string $operation
     *
     * @return ProgramEligibilityCondition
     */
    public function setOperation(string $operation): ProgramEligibilityCondition
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * @return string
     */
    public function getValueType(): string
    {
        return $this->valueType;
    }

    /**
     * @param string $valueType
     *
     * @return ProgramEligibilityCondition
     */
    public function setValueType(string $valueType): ProgramEligibilityCondition
    {
        $this->valueType = $valueType;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return ProgramEligibilityCondition
     */
    public function setValue(string $value): ProgramEligibilityCondition
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return array
     */
    public static function getAvailableOperations(): array
    {
        $operations = MathOperator::getConstList();
        if (isset($operations['BETWEEN'])) {
            unset($operations['BETWEEN']);
        }

        return $operations;
    }

    /**
     * @return array
     */
    public static function getAvailableValueType(): array
    {
        return static::getConstants('VALUE_TYPE_');
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     */
    public function validateTargetEntity(ExecutionContextInterface $context): void
    {
        if (false === $this->getLeftOperandField()->isComparable()) {
            $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.operandField.nonComparable')
                ->atPath('leftOperandField')
                ->addViolation()
            ;
        }

        if ($this->getLeftOperandField() === $this->getRightOperandField()) {
            $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.rightOperandField.selfComparaison')
                ->atPath('rightOperandField')
                ->addViolation()
            ;
        }

        if (null === $this->getRightOperandField() && self::VALUE_TYPE_RATE === $this->getValueType()) {
            $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.rightOperandField.empty')
                ->atPath('rightOperandField')
                ->addViolation()
            ;
        }

        if ($this->getRightOperandField()) {
            if (self::VALUE_TYPE_VALUE === $this->getValueType()) {
                $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.rightOperandField.notEmpty')
                    ->atPath('rightOperandField')
                    ->addViolation()
                ;
            }

            if (false === $this->getRightOperandField()->isComparable()) {
                $context->buildViolation('CreditGuaranty.ProgramEligibilityCondition.operandField.nonComparable')
                    ->atPath('rightOperandField')
                    ->addViolation()
                ;
            }
        }
    }
}
