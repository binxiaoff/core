<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableTrait;
use KLS\CreditGuaranty\FEI\Entity\Interfaces\ProgramAwareInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "creditGuaranty:financingObjectUnblocking:read",
 *         "money:read"
 *     }},
 *     denormalizationContext={"groups": {
 *         "creditGuaranty:financingObjectUnblocking:write",
 *         "money:write"
 *     }},
 *     itemOperations={
 *         "get": {
 *             "security": "is_granted('view', object)"
 *         },
 *         "patch": {
 *             "security": "is_granted('edit', object)"
 *         }
 *     },
 *     collectionOperations={
 *         "post": {
 *             "security_post_denormalize": "is_granted('create', object)"
 *         }
 *     }
 * )
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_financing_object_unblocking")
 * @ORM\HasLifecycleCallbacks
 */
class FinancingObjectUnblocking implements ProgramAwareInterface
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\CreditGuaranty\FEI\Entity\FinancingObject", inversedBy="financingObjectUnblockings")
     * @ORM\JoinColumn(name="id_financing_object", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:financingObjectUnblocking:read", "creditGuaranty:financingObjectUnblocking:write"})
     */
    private FinancingObject $financingObject;

    /**
     * @ORM\Column(type="date_immutable", nullable=false)
     *
     * @Groups({"creditGuaranty:financingObjectUnblocking:read", "creditGuaranty:financingObjectUnblocking:write"})
     */
    private DateTimeImmutable $unblockingDate;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\Money")
     *
     * @Assert\AtLeastOneOf({
     *     @Assert\Expression("null === this.getProgram().isLoanReleasedOnInvoice()"),
     *     @Assert\Expression("false === this.getProgram().isLoanReleasedOnInvoice()"),
     *     @Assert\Expression("true === this.getProgram().isLoanReleasedOnInvoice() && false === value.isNull()")
     * }, message="CreditGuaranty.Reservation.financingObjectUnblocking.invoiceMoney.requiredForLoanReleasedOnInvoice", includeInternalMessages=false)
     *
     * @Groups({"creditGuaranty:financingObjectUnblocking:read", "creditGuaranty:financingObjectUnblocking:write"})
     */
    private Money $invoiceMoney;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\Money")
     *
     * @Assert\AtLeastOneOf({
     *     @Assert\Expression("null === this.getProgram().isLoanReleasedOnInvoice()"),
     *     @Assert\Expression("false === this.getProgram().isLoanReleasedOnInvoice()"),
     *     @Assert\Expression("true === this.getProgram().isLoanReleasedOnInvoice() && false === value.isNull()")
     * }, message="CreditGuaranty.Reservation.financingObjectUnblocking.achievementMoney.requiredForLoanReleasedOnInvoice", includeInternalMessages=false)
     *
     * @Groups({"creditGuaranty:financingObjectUnblocking:read", "creditGuaranty:financingObjectUnblocking:write"})
     */
    private Money $achievementMoney;

    /**
     * @ORM\Embedded(class="KLS\Core\Entity\Embeddable\Money")
     *
     * @Groups({"creditGuaranty:financingObjectUnblocking:read", "creditGuaranty:financingObjectUnblocking:write"})
     */
    private Money $totalMoney;

    public function __construct(
        FinancingObject $financingObject,
        DateTimeImmutable $unblockingDate,
        Money $invoiceMoney,
        Money $achievementMoney,
        Money $totalMoney
    ) {
        $this->financingObject  = $financingObject;
        $this->unblockingDate   = $unblockingDate;
        $this->invoiceMoney     = $invoiceMoney;
        $this->achievementMoney = $achievementMoney;
        $this->totalMoney       = $totalMoney;
        $this->added            = new DateTimeImmutable();
    }

    public function getReservation(): Reservation
    {
        return $this->getFinancingObject()->getReservation();
    }

    public function getProgram(): Program
    {
        return $this->getFinancingObject()->getProgram();
    }

    public function getFinancingObject(): FinancingObject
    {
        return $this->financingObject;
    }

    public function getInvoiceMoney(): Money
    {
        return $this->invoiceMoney;
    }

    public function setInvoiceMoney(Money $invoiceMoney): FinancingObjectUnblocking
    {
        $this->invoiceMoney = $invoiceMoney;

        return $this;
    }

    public function getAchievementMoney(): Money
    {
        return $this->achievementMoney;
    }

    public function setAchievementMoney(Money $achievementMoney): FinancingObjectUnblocking
    {
        $this->achievementMoney = $achievementMoney;

        return $this;
    }

    public function getTotalMoney(): Money
    {
        return $this->totalMoney;
    }

    public function setTotalMoney(Money $totalMoney): FinancingObjectUnblocking
    {
        $this->totalMoney = $totalMoney;

        return $this;
    }
}
