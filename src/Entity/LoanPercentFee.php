<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class LoanPercentFee
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var PercentFee
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\PercentFee", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_percent_fee", referencedColumnName="id", nullable=false)
     * })
     */
    private $percentFee;

    /**
     * @var Loans
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Loans", inversedBy="loanPercentFees")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_loan", referencedColumnName="id_loan", nullable=false)
     * })
     */
    private $loan;

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
     * @return LoanPercentFee
     */
    public function setLoan(Loans $loan): LoanPercentFee
    {
        $this->loan = $loan;

        return $this;
    }

    /**
     * @return PercentFee
     */
    public function getPercentFee(): PercentFee
    {
        return $this->percentFee;
    }

    /**
     * @param PercentFee $percentFee
     *
     * @return LoanPercentFee
     */
    public function setPercentFee(PercentFee $percentFee): LoanPercentFee
    {
        $this->percentFee = $percentFee;

        return $this;
    }
}
