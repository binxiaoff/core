<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TermsOfLoans
 *
 * @ORM\Table(name="terms_of_loans", indexes={@ORM\Index(name="id_term_for_company", columns={"id_term_for_company", "id_loan"})})
 * @ORM\Entity
 */
class TermsOfLoans
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_term_for_company", type="integer", nullable=false)
     */
    private $idTermForCompany;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_loan", type="integer", nullable=false)
     */
    private $idLoan;

    /**
     * @var integer
     *
     * @ORM\Column(name="num", type="integer", nullable=false)
     */
    private $num;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float", precision=10, scale=0, nullable=false)
     */
    private $amount;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=false)
     */
    private $date;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    private $status;

    /**
     * @var float
     *
     * @ORM\Column(name="capital", type="float", precision=10, scale=0, nullable=false)
     */
    private $capital;

    /**
     * @var float
     *
     * @ORM\Column(name="interests", type="float", precision=10, scale=0, nullable=false)
     */
    private $interests;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_term_of_loan", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idTermOfLoan;



    /**
     * Set idTermForCompany
     *
     * @param integer $idTermForCompany
     *
     * @return TermsOfLoans
     */
    public function setIdTermForCompany($idTermForCompany)
    {
        $this->idTermForCompany = $idTermForCompany;

        return $this;
    }

    /**
     * Get idTermForCompany
     *
     * @return integer
     */
    public function getIdTermForCompany()
    {
        return $this->idTermForCompany;
    }

    /**
     * Set idLoan
     *
     * @param integer $idLoan
     *
     * @return TermsOfLoans
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
     * Set num
     *
     * @param integer $num
     *
     * @return TermsOfLoans
     */
    public function setNum($num)
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Get num
     *
     * @return integer
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return TermsOfLoans
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return TermsOfLoans
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return TermsOfLoans
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set capital
     *
     * @param float $capital
     *
     * @return TermsOfLoans
     */
    public function setCapital($capital)
    {
        $this->capital = $capital;

        return $this;
    }

    /**
     * Get capital
     *
     * @return float
     */
    public function getCapital()
    {
        return $this->capital;
    }

    /**
     * Set interests
     *
     * @param float $interests
     *
     * @return TermsOfLoans
     */
    public function setInterests($interests)
    {
        $this->interests = $interests;

        return $this;
    }

    /**
     * Get interests
     *
     * @return float
     */
    public function getInterests()
    {
        return $this->interests;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return TermsOfLoans
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
     * @return TermsOfLoans
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
     * Get idTermOfLoan
     *
     * @return integer
     */
    public function getIdTermOfLoan()
    {
        return $this->idTermOfLoan;
    }
}
