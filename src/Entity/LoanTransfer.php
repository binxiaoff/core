<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LoanTransfer
 *
 * @ORM\Table(name="loan_transfer", indexes={@ORM\Index(name="id_transfer", columns={"id_transfer"})})
 * @ORM\Entity
 */
class LoanTransfer
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_loan", type="integer")
     */
    private $idLoan;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_loan_transfer", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLoanTransfer;

    /**
     * @var \Unilend\Entity\Transfer
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Transfer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_transfer", referencedColumnName="id_transfer", nullable=false)
     * })
     */
    private $idTransfer;



    /**
     * Set idLoan
     *
     * @param integer $idLoan
     *
     * @return LoanTransfer
     */
    public function setIdLoan($idLoan)
    {
        $this->idLoan = $idLoan;

        return $this;
    }

    /**
     * Get idLoan
     *
     * @return integer
     */
    public function getIdLoan()
    {
        return $this->idLoan;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return LoanTransfer
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
     * @return LoanTransfer
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
     * Get idLoanTransfer
     *
     * @return integer
     */
    public function getIdLoanTransfer()
    {
        return $this->idLoanTransfer;
    }

    /**
     * Set idTransfer
     *
     * @param \Unilend\Entity\Transfer $idTransfer
     *
     * @return LoanTransfer
     */
    public function setIdTransfer(\Unilend\Entity\Transfer $idTransfer = null)
    {
        $this->idTransfer = $idTransfer;

        return $this;
    }

    /**
     * Get idTransfer
     *
     * @return \Unilend\Entity\Transfer
     */
    public function getIdTransfer()
    {
        return $this->idTransfer;
    }
}
