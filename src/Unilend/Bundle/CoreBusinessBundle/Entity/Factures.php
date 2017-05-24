<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Factures
 *
 * @ORM\Table(name="factures", indexes={@ORM\Index(name="id_company", columns={"id_company"}), @ORM\Index(name="id_project", columns={"id_project"}), @ORM\Index(name="ordre", columns={"ordre"}), @ORM\Index(name="type_commission", columns={"type_commission"})})
 * @ORM\Entity
 */
class Factures
{
    const TYPE_COMMISSION_FUNDS     = 1;
    const TYPE_COMMISSION_REPAYMENT = 2;

    /**
     * @var string
     *
     * @ORM\Column(name="num_facture", type="string", length=191, nullable=false)
     */
    private $numFacture;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=false)
     */
    private $date;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_company", type="integer", nullable=false)
     */
    private $idCompany;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project", type="integer", nullable=false)
     */
    private $idProject;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordre", type="integer", nullable=false)
     */
    private $ordre;

    /**
     * @var integer
     *
     * @ORM\Column(name="type_commission", type="integer", nullable=false)
     */
    private $typeCommission;

    /**
     * @var integer
     *
     * @ORM\Column(name="commission", type="integer", nullable=false)
     */
    private $commission;

    /**
     * @var integer
     *
     * @ORM\Column(name="montant_ht", type="integer", nullable=false)
     */
    private $montantHt;

    /**
     * @var integer
     *
     * @ORM\Column(name="tva", type="integer", nullable=false)
     */
    private $tva;

    /**
     * @var integer
     *
     * @ORM\Column(name="montant_ttc", type="integer", nullable=false)
     */
    private $montantTtc;

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
     * @ORM\Column(name="id_facture", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idFacture;



    /**
     * Set numFacture
     *
     * @param string $numFacture
     *
     * @return Factures
     */
    public function setNumFacture($numFacture)
    {
        $this->numFacture = $numFacture;

        return $this;
    }

    /**
     * Get numFacture
     *
     * @return string
     */
    public function getNumFacture()
    {
        return $this->numFacture;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Factures
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
     * Set idCompany
     *
     * @param integer $idCompany
     *
     * @return Factures
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
     * Set idProject
     *
     * @param integer $idProject
     *
     * @return Factures
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
     * Set ordre
     *
     * @param integer $ordre
     *
     * @return Factures
     */
    public function setOrdre($ordre)
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * Get ordre
     *
     * @return integer
     */
    public function getOrdre()
    {
        return $this->ordre;
    }

    /**
     * Set typeCommission
     *
     * @param integer $typeCommission
     *
     * @return Factures
     */
    public function setTypeCommission($typeCommission)
    {
        $this->typeCommission = $typeCommission;

        return $this;
    }

    /**
     * Get typeCommission
     *
     * @return integer
     */
    public function getTypeCommission()
    {
        return $this->typeCommission;
    }

    /**
     * Set commission
     *
     * @param integer $commission
     *
     * @return Factures
     */
    public function setCommission($commission)
    {
        $this->commission = $commission;

        return $this;
    }

    /**
     * Get commission
     *
     * @return integer
     */
    public function getCommission()
    {
        return $this->commission;
    }

    /**
     * Set montantHt
     *
     * @param integer $montantHt
     *
     * @return Factures
     */
    public function setMontantHt($montantHt)
    {
        $this->montantHt = $montantHt;

        return $this;
    }

    /**
     * Get montantHt
     *
     * @return integer
     */
    public function getMontantHt()
    {
        return $this->montantHt;
    }

    /**
     * Set tva
     *
     * @param integer $tva
     *
     * @return Factures
     */
    public function setTva($tva)
    {
        $this->tva = $tva;

        return $this;
    }

    /**
     * Get tva
     *
     * @return integer
     */
    public function getTva()
    {
        return $this->tva;
    }

    /**
     * Set montantTtc
     *
     * @param integer $montantTtc
     *
     * @return Factures
     */
    public function setMontantTtc($montantTtc)
    {
        $this->montantTtc = $montantTtc;

        return $this;
    }

    /**
     * Get montantTtc
     *
     * @return integer
     */
    public function getMontantTtc()
    {
        return $this->montantTtc;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Factures
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
     * @return Factures
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
     * Get idFacture
     *
     * @return integer
     */
    public function getIdFacture()
    {
        return $this->idFacture;
    }
}
