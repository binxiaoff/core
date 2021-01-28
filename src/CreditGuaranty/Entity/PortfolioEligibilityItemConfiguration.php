<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Core\Entity\Traits\{PublicizeIdentityTrait, TimestampableTrait};

class PortfolioEligibilityItemConfiguration
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
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\PortfolioEligibilityItem")
     * @ORM\JoinColumn(name="id_portfolio_eligibility_item", nullable=false)
     */
    private PortfolioEligibilityItem $portfolioEligibilityItem;

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
     * @param PortfolioEligibilityItem $portfolioEligibilityItem
     * @param string                   $leftOperand
     * @param string                   $rightOperand
     * @param string                   $operation
     * @param string                   $valueType
     * @param string                   $data
     */
    public function __construct(PortfolioEligibilityItem $portfolioEligibilityItem, string $leftOperand, string $rightOperand, string $operation, string $valueType, string $data)
    {
        $this->portfolioEligibilityItem = $portfolioEligibilityItem;
        $this->leftOperand              = $leftOperand;
        $this->rightOperand             = $rightOperand;
        $this->operation                = $operation;
        $this->valueType                = $valueType;
        $this->data                     = $data;
    }

    /**
     * @return PortfolioEligibilityItem
     */
    public function getPortfolioEligibilityItem(): PortfolioEligibilityItem
    {
        return $this->portfolioEligibilityItem;
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
     * @return PortfolioEligibilityItemConfiguration
     */
    public function setLeftOperand(string $leftOperand): PortfolioEligibilityItemConfiguration
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
     * @return PortfolioEligibilityItemConfiguration
     */
    public function setRightOperand(string $rightOperand): PortfolioEligibilityItemConfiguration
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
     * @return PortfolioEligibilityItemConfiguration
     */
    public function setOperation(string $operation): PortfolioEligibilityItemConfiguration
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
     * @return PortfolioEligibilityItemConfiguration
     */
    public function setValueType(string $valueType): PortfolioEligibilityItemConfiguration
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
     * @return PortfolioEligibilityItemConfiguration
     */
    public function setData(string $data): PortfolioEligibilityItemConfiguration
    {
        $this->data = $data;

        return $this;
    }
}
