<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class LoanPercentFee
{
    /**
     * @var PercentFee
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\PercentFee", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_percent_fee", referencedColumnName="id", nullable=false)
     * })
     */
    private $percentFee;

    /**
     * @var Loans
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Loans", inversedBy="loanPercentFees")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_loan", referencedColumnName="id_loan", nullable=false)
     * })
     */
    private $loan;

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
