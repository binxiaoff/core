<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};

class ProgramEligibilityItemConfiguration
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    private const OPERATION_EQUAL_TO = '=';
    private const OPERATION_GREATER_THAN = '>';
    private const OPERATION_GREATER_OR_EQUAL_THAN = '>=';
    private const OPERATION_LESS_THAN = '<';
    private const OPERATION_LESS_OR_EQUAL_THAN = '<=';

    private const DATA_TYPE_RATE = 'rate';
    private const DATA_TYPE_VALUE = 'value';

    /**
     * @ORM\ManyToOne(targetEntity="ProgramEligibilityItem")
     * @ORM\JoinColumn(name="id_program_eligibility_item", nullable=false)
     */
    private ProgramEligibilityItem $programEligibilityItem;

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
     * @param ProgramEligibilityItem $programEligibilityItem
     * @param string                 $leftOperand
     * @param string                 $rightOperand
     * @param string                 $operation
     * @param string                 $valueType
     * @param string                 $data
     */
    public function __construct(ProgramEligibilityItem $programEligibilityItem, string $leftOperand, string $rightOperand, string $operation, string $valueType, string $data)
    {
        $this->programEligibilityItem = $programEligibilityItem;
        $this->leftOperand              = $leftOperand;
        $this->rightOperand             = $rightOperand;
        $this->operation                = $operation;
        $this->valueType                = $valueType;
        $this->data                     = $data;
    }

    /**
     * @return ProgramEligibilityItem
     */
    public function getProgramEligibilityItem(): ProgramEligibilityItem
    {
        return $this->programEligibilityItem;
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
     * @return ProgramEligibilityItemConfiguration
     */
    public function setLeftOperand(string $leftOperand): ProgramEligibilityItemConfiguration
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
     * @return ProgramEligibilityItemConfiguration
     */
    public function setRightOperand(string $rightOperand): ProgramEligibilityItemConfiguration
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
     * @return ProgramEligibilityItemConfiguration
     */
    public function setOperation(string $operation): ProgramEligibilityItemConfiguration
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
     * @return ProgramEligibilityItemConfiguration
     */
    public function setValueType(string $valueType): ProgramEligibilityItemConfiguration
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
     * @return ProgramEligibilityItemConfiguration
     */
    public function setData(string $data): ProgramEligibilityItemConfiguration
    {
        $this->data = $data;

        return $this;
    }
}
