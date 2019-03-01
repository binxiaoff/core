<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Bundle\CoreBusinessBundle\Entity\Traits\{LendingChargeable, Timestampable};

/**
 * @ORM\Entity
 */
class LoanFee
{
    use LendingChargeable;
    use Timestampable;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Loans
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Loans", inversedBy="fees")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_loan", referencedColumnName="id_loan", nullable=false)
     * })
     */
    private $loan;

    /**
     * @return int
     */
    public function getId(): int
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
}
