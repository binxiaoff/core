<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CloseOutNettingRepayment
 *
 * @ORM\Table(name="close_out_netting_repayment", indexes={@ORM\Index(name="idx_close_out_netting_repayment_id_loan", columns={"id_loan"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class CloseOutNettingRepayment
{
    /**
     * @var string
     *
     * @ORM\Column(name="capital", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $capital;

    /**
     * @var string
     *
     * @ORM\Column(name="repaid_capital", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $repaidCapital;

    /**
     * @var string
     *
     * @ORM\Column(name="interest", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $interest;

    /**
     * @var string
     *
     * @ORM\Column(name="repaid_interest", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $repaidInterest;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Loans
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Loans")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_loan", referencedColumnName="id_loan")
     * })
     */
    private $idLoan;

    /**
     * Set capital
     *
     * @param string $capital
     *
     * @return CloseOutNettingRepayment
     */
    public function setCapital($capital)
    {
        $this->capital = $capital;

        return $this;
    }

    /**
     * Get capital
     *
     * @return string
     */
    public function getCapital()
    {
        return $this->capital;
    }

    /**
     * Set repaidCapital
     *
     * @param string $repaidCapital
     *
     * @return CloseOutNettingRepayment
     */
    public function setRepaidCapital($repaidCapital)
    {
        $this->repaidCapital = $repaidCapital;

        return $this;
    }

    /**
     * Get repaidCapital
     *
     * @return string
     */
    public function getRepaidCapital()
    {
        return $this->repaidCapital;
    }

    /**
     * Set interest
     *
     * @param string $interest
     *
     * @return CloseOutNettingRepayment
     */
    public function setInterest($interest)
    {
        $this->interest = $interest;

        return $this;
    }

    /**
     * Get interest
     *
     * @return string
     */
    public function getInterest()
    {
        return $this->interest;
    }

    /**
     * Set repaidInterest
     *
     * @param string $repaidInterest
     *
     * @return CloseOutNettingRepayment
     */
    public function setRepaidInterest($repaidInterest)
    {
        $this->repaidInterest = $repaidInterest;

        return $this;
    }

    /**
     * Get repaidInterest
     *
     * @return string
     */
    public function getRepaidInterest()
    {
        return $this->repaidInterest;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return CloseOutNettingRepayment
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return CloseOutNettingRepayment
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set idLoan
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Loans $idLoan
     *
     * @return CloseOutNettingRepayment
     */
    public function setIdLoan(\Unilend\Bundle\CoreBusinessBundle\Entity\Loans $idLoan = null)
    {
        $this->idLoan = $idLoan;

        return $this;
    }

    /**
     * Get idLoan
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Loans
     */
    public function getIdLoan()
    {
        return $this->idLoan;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
