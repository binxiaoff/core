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
class Inequality
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
     *     "agency:inequality:read",
     *     "agency:inequality:write",
     * })
     */
    private string $operator;

    /**
     * @var string
     *
     * @ORM\Column(type="decimal", precision=65, scale=4)
     *
     * @Assert\Type("numeric")
     * @Assert\NotBlank()
     *
     * @Groups({
     *     "agency:inequality:read",
     *     "agency:inequality:write",
     * })
     *
     */
    private string $value;

    /**
     * Only usable with between operator
     *
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=65, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\Expression("(this.getOperator() === constant('Unilend\\Agency\\Entity\\Embeddable\\Inequality::OPERATOR_BETWEEN') && value) or !value")
     * @Assert\GreaterThan("value")
     *
     * @Groups({
     *     "agency:inequality:read",
     *     "agency:inequality:write",
     * })
     *
     */
    private ?string $maxValue = null;

    /**
     * @param string      $operator
     * @param string      $value
     * @param string|null $maxValue
     */
    public function __construct(string $operator, string $value, ?string $maxValue = null)
    {
        $this->operator = $operator;
        $this->value    = $value;
        $this->maxValue = $maxValue;
    }

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
     * @return Inequality
     */
    public function setOperator(string $operator): Inequality
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
     * @return Inequality
     */
    public function setValue(string $value): Inequality
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
     * @return Inequality
     */
    public function setMaxValue(?string $maxValue): Inequality
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

    /**
     * @param $evaluatedNumber
     *
     * @return bool
     */
    public function isConform(string $evaluatedNumber)
    {
        $comp = bccomp($this->value, $evaluatedNumber, 4);

        $maxValueComp = $this->maxValue ? bccomp($this->maxValue, $evaluatedNumber, 4) : false;

        switch ($this->operator) {
            case static::OPERATOR_EQUAL:
                return 0 === $comp;
            case static::OPERATOR_INFERIOR:
                return 1 === $comp ;
            case static::OPERATOR_INFERIOR_OR_EQUAL:
                return 0 === $comp || 1 === $comp;
            case static::OPERATOR_SUPERIOR:
                return -1 === $comp;
            case static::OPERATOR_SUPERIOR_OR_EQUAL:
                return -1 === $comp || 0 === $comp;
            case static::OPERATOR_BETWEEN:
                return $this->maxValue && ((1 === $maxValueComp && -1 === $comp) || 0 === $maxValueComp || 0 === $comp);
        }

        return false;
    }
}
