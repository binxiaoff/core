<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\{Embeddable\Money, Embeddable\NullableMoney, MarketSegment, Staff, Traits\BlamableAddedTrait, Traits\PublicizeIdentityTrait, Traits\TimestampableTrait};

/**
 * @ApiResource(
 *      itemOperations={
 *          "get",
 *          "patch"
 *      },
 *      collectionOperations={
 *         "post"
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_program")
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity({"name"}, message="CreditGuaranty.Program.name.unique")
 */
class Program
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;
    use BlamableAddedTrait;

    /**
     * @ORM\Column(length=100, unique=true)
     */
    private string $name;

    /**
     * @ORM\Column(type="text", length=16777215, nullable=true)
     */
    private ?string $description;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\MarketSegment")
     * @ORM\JoinColumn(name="id_market_segment", nullable=false)
     *
     * @Assert\Expression(
     *     expression="this.getMarketSegment().getLabel() in ([MarketSegment::LABEL_CORPORATE, MarketSegment::LABEL_AGRICULTURE])",
     *     message="CreditGuaranty.Program.marketSegment.invalid"
     * )
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
     * @param Staff         $addedBy
     */
    public function __construct(string $name, MarketSegment $marketSegment, Money $funds, Staff $addedBy)
    {
        $this->name          = $name;
        $this->marketSegment = $marketSegment;
        $this->funds         = $funds;
        $this->cappedAt      = new NullableMoney();
        $this->addedBy       = $addedBy;
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
     * @param Money $funds
     *
     * @return Program
     */
    public function setFunds(Money $funds): Program
    {
        $this->funds = $funds;

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
