<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Embeddable\Fee;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class LoanFee
{
    use TimestampableTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Fee
     *
     * @ORM\Embedded(class="Unilend\Entity\Embeddable\Fee")
     */
    private $fee;

    /**
     * @var Loans
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Loans", inversedBy="loanFees")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_loan", referencedColumnName="id_loan", nullable=false)
     * })
     */
    private $loan;

    /**
     * Initialise some object-value.
     */
    public function __construct()
    {
        $this->fee = new Fee();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Loans
     */
    public function getLoan(): Loans
    {
        return $this->loan;
    }

    /**
     * @param Loans $loan
     *
     * @return LoanFee
     */
    public function setLoan(Loans $loan): LoanFee
    {
        $this->loan = $loan;

        return $this;
    }

    /**
     * @return Fee
     */
    public function getFee(): Fee
    {
        return $this->fee;
    }

    /**
     * @param Fee $fee
     *
     * @return LoanFee
     */
    public function setFee(Fee $fee): LoanFee
    {
        $this->fee = $fee;

        return $this;
    }
}
