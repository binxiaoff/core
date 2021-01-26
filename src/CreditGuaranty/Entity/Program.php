<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\{Embeddable\Money, Embeddable\NullableMoney, MarketSegment, Traits\PublicizeIdentityTrait, Traits\TimestampableTrait};

class Program
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\Column(length=100)
     *
     * @Groups({"creditGuaranty:program:write", "creditGuaranty:program:read"})
     */
    private string $name;

    /**
     * @ORM\Column(type="text", length=16777215, nullable=true)
     *
     * @Groups({"creditGuaranty:program:write", "creditGuaranty:program:read"})
     */
    private ?string $description;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\MarketSegment")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_market_segment", nullable=false)
     * })
     *
     * @Groups({"creditGuaranty:program:write", "creditGuaranty:program:read"})
     */
    private MarketSegment $marketSegment;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\NullableMoney")
     *
     * @Assert\Valid
     */
    private NullableMoney $cappedAt;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Assert\Valid
     */
    private Money $funds;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $distributionStartAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?DateTimeImmutable $distributionDeadline;

    /**
     * @ORM\Column(type="json", nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     */
    private ?array $distributionProcess;

    /**
     * Duration in month
     *
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Assert\NotBlank(allowNull=true)
     * @Assert\GreaterThanOrEqual(1)
     */
    private ?int $guarantyDuration;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=4, scale=4, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     * @Assert\Range(min="0", max="0.9999")
     */
    private ?string $guarantyCoverage;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2, nullable=true)
     *
     * @Assert\Type("numeric")
     * @Assert\PositiveOrZero
     */
    private ?string $guarantyCost;

    /**
     * @param string        $name
     * @param MarketSegment $marketSegment
     * @param Money         $funds
     */
    public function __construct(string $name, MarketSegment $marketSegment, Money $funds)
    {
        $this->name          = $name;
        $this->marketSegment = $marketSegment;
        $this->funds         = $funds;
        $this->cappedAt      = new NullableMoney();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return Program
     */
    public function setDescription(?string $description): Program
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return MarketSegment
     */
    public function getMarketSegment(): MarketSegment
    {
        return $this->marketSegment;
    }

    /**
     * @return NullableMoney
     */
    public function getCappedAt(): NullableMoney
    {
        return $this->cappedAt;
    }

    /**
     * @param NullableMoney $cappedAt
     *
     * @return Program
     */
    public function setCappedAt(NullableMoney $cappedAt): Program
    {
        $this->cappedAt = $cappedAt;

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
     * @return DateTimeImmutable|null
     */
    public function getDistributionStartAt(): ?DateTimeImmutable
    {
        return $this->distributionStartAt;
    }

    /**
     * @param DateTimeImmutable|null $distributionStartAt
     *
     * @return Program
     */
    public function setDistributionStartAt(?DateTimeImmutable $distributionStartAt): Program
    {
        $this->distributionStartAt = $distributionStartAt;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getDistributionDeadline(): ?DateTimeImmutable
    {
        return $this->distributionDeadline;
    }

    /**
     * @param DateTimeImmutable|null $distributionDeadline
     *
     * @return Program
     */
    public function setDistributionDeadline(?DateTimeImmutable $distributionDeadline): Program
    {
        $this->distributionDeadline = $distributionDeadline;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getDistributionProcess(): ?array
    {
        return $this->distributionProcess;
    }

    /**
     * @param array|null $distributionProcess
     *
     * @return Program
     */
    public function setDistributionProcess(?array $distributionProcess): Program
    {
        $this->distributionProcess = $distributionProcess;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getGuarantyDuration(): ?int
    {
        return $this->guarantyDuration;
    }

    /**
     * @param int|null $guarantyDuration
     *
     * @return Program
     */
    public function setGuarantyDuration(?int $guarantyDuration): Program
    {
        $this->guarantyDuration = $guarantyDuration;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getGuarantyCoverage(): ?string
    {
        return $this->guarantyCoverage;
    }

    /**
     * @param string|null $guarantyCoverage
     *
     * @return Program
     */
    public function setGuarantyCoverage(?string $guarantyCoverage): Program
    {
        $this->guarantyCoverage = $guarantyCoverage;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getGuarantyCost(): ?string
    {
        return $this->guarantyCost;
    }

    /**
     * @param string|null $guarantyCost
     *
     * @return Program
     */
    public function setGuarantyCost(?string $guarantyCost): Program
    {
        $this->guarantyCost = $guarantyCost;

        return $this;
    }
}
