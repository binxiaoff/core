<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Factures
 *
 * @ORM\Table(name="factures", indexes={@ORM\Index(name="id_company", columns={"id_company"}), @ORM\Index(name="id_project", columns={"id_project"}), @ORM\Index(name="ordre", columns={"ordre"}), @ORM\Index(name="type_commission", columns={"type_commission"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Factures
{
    const TYPE_COMMISSION_FUNDS     = 1;
    const TYPE_COMMISSION_REPAYMENT = 2;

    /**
     * @var string
     *
     * @ORM\Column(name="num_facture", type="string", length=191)
     */
    private $numFacture;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var int
     *
     * @deprecated use idProject
     *
     * @ORM\Column(name="id_company", type="integer")
     */
    private $idCompany;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects", inversedBy="invoices")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project", nullable=false)
     * })
     */
    private $idProject;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre", type="integer")
     */
    private $ordre;

    /**
     * @var int
     *
     * @ORM\Column(name="type_commission", type="smallint")
     */
    private $typeCommission;

    /**
     * @var float
     *
     * @ORM\Column(name="commission", type="decimal", precision=3, scale=1)
     */
    private $commission;

    /**
     * @var int
     *
     * @ORM\Column(name="montant_ht", type="integer")
     */
    private $montantHt;

    /**
     * @var int
     *
     * @ORM\Column(name="tva", type="integer")
     */
    private $tva;

    /**
     * @var int
     *
     * @ORM\Column(name="montant_ttc", type="integer")
     */
    private $montantTtc;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
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
     * @deprecated use idProject
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
     * @deprecated use idProject
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
     * @param Projects $idProject
     *
     * @return Factures
     */
    public function setIdProject(Projects $idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return Projects
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
     * @param float $commission
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
     * @return float
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
