<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TermsForCompanies
 *
 * @ORM\Table(name="terms_for_companies")
 * @ORM\Entity
 */
class TermsForCompanies
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_project", type="integer", nullable=false)
     */
    private $idProject;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_company", type="integer", nullable=false)
     */
    private $idCompany;

    /**
     * @var float
     *
     * @ORM\Column(name="fees", type="float", precision=10, scale=0, nullable=false)
     */
    private $fees;

    /**
     * @var float
     *
     * @ORM\Column(name="sum_amount", type="float", precision=10, scale=0, nullable=false)
     */
    private $sumAmount;

    /**
     * @var float
     *
     * @ORM\Column(name="sum_capital", type="float", precision=10, scale=0, nullable=false)
     */
    private $sumCapital;

    /**
     * @var float
     *
     * @ORM\Column(name="sum_interests", type="float", precision=10, scale=0, nullable=false)
     */
    private $sumInterests;

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
     * @ORM\Column(name="id_term_for_company", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idTermForCompany;



    /**
     * Set idProject
     *
     * @param integer $idProject
     *
     * @return TermsForCompanies
     */
    public function setIdProject($idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return integer
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * Set idCompany
     *
     * @param integer $idCompany
     *
     * @return TermsForCompanies
     */
    public function setIdCompany($idCompany)
    {
        $this->idCompany = $idCompany;

        return $this;
    }

    /**
     * Get idCompany
     *
     * @return integer
     */
    public function getIdCompany()
    {
        return $this->idCompany;
    }

    /**
     * Set fees
     *
     * @param float $fees
     *
     * @return TermsForCompanies
     */
    public function setFees($fees)
    {
        $this->fees = $fees;

        return $this;
    }

    /**
     * Get fees
     *
     * @return float
     */
    public function getFees()
    {
        return $this->fees;
    }

    /**
     * Set sumAmount
     *
     * @param float $sumAmount
     *
     * @return TermsForCompanies
     */
    public function setSumAmount($sumAmount)
    {
        $this->sumAmount = $sumAmount;

        return $this;
    }

    /**
     * Get sumAmount
     *
     * @return float
     */
    public function getSumAmount()
    {
        return $this->sumAmount;
    }

    /**
     * Set sumCapital
     *
     * @param float $sumCapital
     *
     * @return TermsForCompanies
     */
    public function setSumCapital($sumCapital)
    {
        $this->sumCapital = $sumCapital;

        return $this;
    }

    /**
     * Get sumCapital
     *
     * @return float
     */
    public function getSumCapital()
    {
        return $this->sumCapital;
    }

    /**
     * Set sumInterests
     *
     * @param float $sumInterests
     *
     * @return TermsForCompanies
     */
    public function setSumInterests($sumInterests)
    {
        $this->sumInterests = $sumInterests;

        return $this;
    }

    /**
     * Get sumInterests
     *
     * @return float
     */
    public function getSumInterests()
    {
        return $this->sumInterests;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return TermsForCompanies
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
     * @return TermsForCompanies
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
     * Get idTermForCompany
     *
     * @return integer
     */
    public function getIdTermForCompany()
    {
        return $this->idTermForCompany;
    }
}
