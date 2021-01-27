<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\{Embeddable\Money, Traits\BlamableAddedTrait, Traits\PublicizeIdentityTrait, Traits\TimestampableTrait};
use Unilend\Core\Traits\ConstantsAwareTrait;

class Portfolio
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use BlamableAddedTrait;
    use ConstantsAwareTrait;

    /**
     * @ORM\Column(length=100)
     */
    private string $name;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Assert\Valid
     */
    private Money $funds;

    /**
     * @ORM\Column(length=60, nullable=true)
     *
     * @Assert\Choice(callback="getGradeTypes")
     */
    private ?string $gradeType;

    /**
     * @param string $name
     * @param Money  $funds
     */
    public function __construct(string $name, Money $funds)
    {
        $this->name  = $name;
        $this->funds = $funds;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Portfolio
     */
    public function setName(string $name): Portfolio
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Money
     */
    public function getFunds(): Money
    {
        return $this->funds;
    }

    /**
     * @param Money $funds
     *
     * @return Portfolio
     */
    public function setFunds(Money $funds): Portfolio
    {
        $this->funds = $funds;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getGradeType(): ?string
    {
        return $this->gradeType;
    }

    /**
     * @param string|null $gradeType
     *
     * @return Portfolio
     */
    public function setGradeType(?string $gradeType): Portfolio
    {
        $this->gradeType = $gradeType;

        return $this;
    }

    /**
     * @return array
     */
    public function getGradeTypes(): array
    {
        return self::getConstants('GRADE_TYPE_');
    }
}
