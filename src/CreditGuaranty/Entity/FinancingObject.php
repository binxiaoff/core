<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableTrait;

/**
 * @ApiResource
 *
 * @ORM\Entity
 * @ORM\Table(name="credit_guaranty_financing_object")
 * @ORM\HasLifecycleCallbacks
 */
class FinancingObject
{
    use PublicizeIdentityTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\Reservation", inversedBy="financingObjects")
     * @ORM\JoinColumn(name="id_reservation", nullable=false)
     *
     * @ApiProperty(readableLink=false, writableLink=false)
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private Reservation $reservation;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_financing_object", nullable=false)
     */
    private ProgramChoiceOption $financingObject;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\CreditGuaranty\Entity\ProgramChoiceOption")
     * @ORM\JoinColumn(name="id_loan_type", nullable=false)
     */
    private ProgramChoiceOption $loanType;

    /**
     * Duration in month.
     *
     * @ORM\Column(type="smallint")
     *
     * @Assert\GreaterThanOrEqual(1)
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private int $loanDuration;

    /**
     * @ORM\Embedded(class="Unilend\Core\Entity\Embeddable\Money")
     *
     * @Assert\Valid
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private Money $loanMoney;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Groups({"creditGuaranty:financingObject:read", "creditGuaranty:financingObject:write"})
     */
    private bool $releasedOnInvoice;

    public function __construct(
        Reservation $reservation,
        ProgramChoiceOption $financingObject,
        ProgramChoiceOption $loanType,
        int $loanDuration,
        Money $loanMoney,
        bool $releasedOnInvoice
    ) {
        $this->reservation       = $reservation;
        $this->financingObject   = $financingObject;
        $this->loanType          = $loanType;
        $this->loanDuration      = $loanDuration;
        $this->loanMoney         = $loanMoney;
        $this->releasedOnInvoice = $releasedOnInvoice;
        $this->added             = new DateTimeImmutable();
    }

    public function getReservation(): Reservation
    {
        return $this->reservation;
    }

    public function getFinancingObject(): ProgramChoiceOption
    {
        return $this->financingObject;
    }

    public function setFinancingObject(ProgramChoiceOption $financingObject): FinancingObject
    {
        $this->financingObject = $financingObject;

        return $this;
    }

    public function getLoanType(): ProgramChoiceOption
    {
        return $this->loanType;
    }

    public function setLoanType(ProgramChoiceOption $loanType): FinancingObject
    {
        $this->loanType = $loanType;

        return $this;
    }

    public function getLoanDuration(): int
    {
        return $this->loanDuration;
    }

    public function setLoanDuration(int $loanDuration): FinancingObject
    {
        $this->loanDuration = $loanDuration;

        return $this;
    }

    public function getLoanMoney(): Money
    {
        return $this->loanMoney;
    }

    public function setLoanMoney(Money $loanMoney): FinancingObject
    {
        $this->loanMoney = $loanMoney;

        return $this;
    }

    public function isReleasedOnInvoice(): bool
    {
        return $this->releasedOnInvoice;
    }

    public function setReleasedOnInvoice(bool $releasedOnInvoice): FinancingObject
    {
        $this->releasedOnInvoice = $releasedOnInvoice;

        return $this;
    }
}
