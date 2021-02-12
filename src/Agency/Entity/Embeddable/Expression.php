<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ORM\Embeddable
 */
class Expression
{
    use ConstantsAwareTrait;

    public const OPERATOR_INFERIOR          = '<';
    public const OPERATOR_INFERIOR_OR_EQUAL = '<=';
    public const OPERATOR_EQUAL             = '=';
    public const OPERATOR_SUPERIOR          = '>';
    public const OPERATOR_SUPERIOR_OR_EQUAL = '>=';
    public const OPERATOR_BETWEEN           = '<>';

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=2)
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback="getOperators")
     *
     * @Groups({
     *     "agency:expression:read",
     *     "agency:expression:write",
     * })
     */
    private string $operator;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=5, scale=4)
     *
     * @Assert\Type("numeric")
     * @Assert\NotBlank(allowNull=true)
     *
     * @Groups({
     *     "agency:expression:read",
     *     "agency:expression:write",
     * })
     *
     */
    private string $value;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=5, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\Expression("(this.getOperator() === self::OPERATOR_BETWEEN && value) or !value")
     *
     * @Groups({
     *     "agency:expression:read",
     *     "agency:expression:write",
     * })
     *
     */
    private ?string $maxValue = null;

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @param string $operator
     *
     * @return Expression
     */
    public function setOperator(string $operator): Expression
    {
        $this->operator = $operator;

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
     * @return Expression
     */
    public function setValue(string $value): Expression
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMaxValue(): ?string
    {
        return $this->maxValue;
    }

    /**
     * @param string|null $maxValue
     *
     * @return Expression
     */
    public function setMaxValue(?string $maxValue): Expression
    {
        $this->maxValue = $maxValue;

        return $this;
    }

    /**
     * @return iterable
     */
    public function getOperators(): iterable
    {
        return self::getConstants('OPERATOR_');
    }
}
