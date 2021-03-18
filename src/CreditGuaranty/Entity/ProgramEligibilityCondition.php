<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};

/**
 * @ApiResource
 */
class ProgramEligibilityCondition
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    private const OPERATION_EQUAL_TO              = '=';
    private const OPERATION_GREATER_THAN          = '>';
    private const OPERATION_GREATER_OR_EQUAL_THAN = '>=';
    private const OPERATION_LESS_THAN             = '<';
    private const OPERATION_LESS_OR_EQUAL_THAN    = '<=';

    private const DATA_TYPE_RATE  = 'rate';
    private const DATA_TYPE_VALUE = 'value';

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration")
     * @ORM\JoinColumn(name="id_program_eligibility_item", nullable=false)
     */
    private ProgramEligibilityConfiguration $programEligibilityConfiguration;

    /**
     * @ORM\Column(length=200)
     */
    private string $leftOperand;

    /**
     * @ORM\Column(length=200)
     */
    private string $rightOperand;

    /**
     * @ORM\Column(length=10)
     */
    private string $operation;

    /**
     * @ORM\Column(length=20)
     */
    private string $valueType;

    /**
     * @ORM\Column(length=100)
     */
    private string $data;

    /**
     * @param ProgramEligibilityConfiguration $programEligibilityConfiguration
     * @param string                          $leftOperand
     * @param string                          $rightOperand
     * @param string                          $operation
     * @param string                          $valueType
     * @param string                          $data
     */
    public function __construct(
        ProgramEligibilityConfiguration $programEligibilityConfiguration,
        string $leftOperand,
        string $rightOperand,
        string $operation,
        string $valueType,
        string $data
    ) {
        $this->programEligibilityConfiguration = $programEligibilityConfiguration;
        $this->leftOperand                     = $leftOperand;
        $this->rightOperand                    = $rightOperand;
        $this->operation                       = $operation;
        $this->valueType                       = $valueType;
        $this->data                            = $data;
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
     * @return string
     */
    public function getLeftOperand(): string
    {
        return $this->leftOperand;
    }

    /**
     * @param string $leftOperand
     *
     * @return ProgramEligibilityCondition
     */
    public function setLeftOperand(string $leftOperand): ProgramEligibilityCondition
    {
        $this->leftOperand = $leftOperand;

        return $this;
    }

    /**
     * @return string
     */
    public function getRightOperand(): string
    {
        return $this->rightOperand;
    }

    /**
     * @param string $rightOperand
     *
     * @return ProgramEligibilityCondition
     */
    public function setRightOperand(string $rightOperand): ProgramEligibilityCondition
    {
        $this->rightOperand = $rightOperand;

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
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @param string $data
     *
     * @return ProgramEligibilityCondition
     */
    public function setData(string $data): ProgramEligibilityCondition
    {
        $this->data = $data;

        return $this;
    }
}
