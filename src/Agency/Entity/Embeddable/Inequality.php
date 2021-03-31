<?php

declare(strict_types=1);

namespace Unilend\Agency\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Unilend\Core\Entity\Constant\MathOperator;

/**
 * @ORM\Embeddable
 */
class Inequality
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=3)
     *
     * @Assert\NotBlank
     * @Assert\Choice(callback={MathOperator::class, "getConstList"})
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
     * @Assert\AtLeastOneOf(constraints={
     *     @Assert\Expression("this.getOperator() === constant('Unilend\\Core\\Entity\\Constant\\MathOperator::BETWEEN') && null !== value"),
     *     @Assert\Expression("this.getOperator() !== constant('Unilend\\Core\\Entity\\Constant\\MathOperator::BETWEEN') && null === value")
     * }, message="Inequality.maxValue.incorrectOperator")
     *
     * @Groups({"agency:inequality:read","agency:inequality:write"})
     */
    private ?string $maxValue;

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
     * Replacement of GreaterThan(value) because we use string
     *
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     */
    public function validate(ExecutionContextInterface $context)
    {
        if ($this->maxValue && -1 !== bccomp($this->value, $this->maxValue, 4)) {
            $context->buildViolation('Inequality.maxValue.greaterThan')
                ->addViolation();
        }
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
            case MathOperator::EQUAL:
                return 0 === $comp;
            case MathOperator::INFERIOR:
                return 1 === $comp ;
            case MathOperator::INFERIOR_OR_EQUAL:
                return 0 === $comp || 1 === $comp;
            case MathOperator::SUPERIOR:
                return -1 === $comp;
            case MathOperator::SUPERIOR_OR_EQUAL:
                return -1 === $comp || 0 === $comp;
            case MathOperator::BETWEEN:
                return $this->maxValue && ((1 === $maxValueComp && -1 === $comp) || 0 === $maxValueComp || 0 === $comp);
        }

        return false;
    }
}
