<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EcheanciersEmprunteur
 *
 * @ORM\Table(name="echeanciers_emprunteur", indexes={@ORM\Index(name="id_project", columns={"id_project"}), @ORM\Index(name="date_echeance_emprunteur_reel", columns={"date_echeance_emprunteur_reel"}), @ORM\Index(name="ordre", columns={"ordre"}), @ORM\Index(name="status_emprunteur", columns={"status_emprunteur"}), @ORM\Index(name="project_status_emprunteur", columns={"id_project", "status_emprunteur"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\EcheanciersEmprunteurRepository")
 */
class EcheanciersEmprunteur
{
    const STATUS_PENDING        = 0;
    const STATUS_PAID           = 1;
    const STATUS_PARTIALLY_PAID = 2;

    const STATUS_NO_EARLY_REPAYMENT   = 0;
    const STATUS_EARLY_REPAYMENT_DONE = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre", type="integer")
     */
    private $ordre;

    /**
     * @var int
     *
     * @deprecated This column will be deleted. Use the summary of EcheanciersEmprunteur::$capital and EcheanciersEmprunteur::$interets instead
     *
     * @ORM\Column(name="montant", type="integer")
     */
    private $montant;

    /**
     * @var int
     *
     * @ORM\Column(name="capital", type="integer")
     */
    private $capital;

    /**
     * @var int
     *
     * @ORM\Column(name="paid_capital", type="integer")
     */
    private $paidCapital;

    /**
     * @var int
     *
     * @ORM\Column(name="interets", type="integer")
     */
    private $interets;

    /**
     * @var int
     *
     * @ORM\Column(name="paid_interest", type="integer")
     */
    private $paidInterest;

    /**
     * @var int
     *
     * @ORM\Column(name="commission", type="integer")
     */
    private $commission;

    /**
     * @var int
     *
     * @ORM\Column(name="tva", type="integer")
     */
    private $tva;

    /**
     * @var int
     *
     * @ORM\Column(name="paid_commission_vat_incl", type="integer")
     */
    private $paidCommissionVatIncl;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_echeance_emprunteur", type="datetime")
     */
    private $dateEcheanceEmprunteur;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_echeance_emprunteur_reel", type="datetime", nullable=true)
     */
    private $dateEcheanceEmprunteurReel;

    /**
     * @var int
     *
     * @ORM\Column(name="status_emprunteur", type="smallint")
     */
    private $statusEmprunteur;

    /**
     * @var int
     *
     * @ORM\Column(name="status_ra", type="smallint")
     */
    private $statusRa;

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
     * @ORM\Column(name="id_echeancier_emprunteur", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idEcheancierEmprunteur;

    /**
     * @var \Unilend\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project", nullable=false)
     * })
     */
    private $idProject;

    /**
     * Set ordre
     *
     * @param integer $ordre
     *
     * @return EcheanciersEmprunteur
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
     * Set montant
     *
     * @deprecated This column will be deleted. Use the summary of EcheanciersEmprunteur::$capital and EcheanciersEmprunteur::$interets instead
     *
     * @param integer $montant
     *
     * @return EcheanciersEmprunteur
     */
    public function setMontant($montant)
    {
        $this->montant = $montant;

        return $this;
    }

    /**
     * Get montant
     *
     * @deprecated This column will be deleted. Use the summary of EcheanciersEmprunteur::$capital and EcheanciersEmprunteur::$interets instead
     *
     * @return integer
     */
    public function getMontant()
    {
        return $this->montant;
    }

    /**
     * Set capital
     *
     * @param integer $capital
     *
     * @return EcheanciersEmprunteur
     */
    public function setCapital($capital)
    {
        $this->capital = $capital;

        return $this;
    }

    /**
     * Get capital
     *
     * @return integer
     */
    public function getCapital()
    {
        return $this->capital;
    }

    /**
     * Set paidCapital
     *
     * @param integer $paidCapital
     *
     * @return EcheanciersEmprunteur
     */
    public function setPaidCapital($paidCapital)
    {
        $this->paidCapital = $paidCapital;

        return $this;
    }

    /**
     * Get paidCapital
     *
     * @return integer
     */
    public function getPaidCapital()
    {
        return $this->paidCapital;
    }

    /**
     * Set interets
     *
     * @param integer $interets
     *
     * @return EcheanciersEmprunteur
     */
    public function setInterets($interets)
    {
        $this->interets = $interets;

        return $this;
    }

    /**
     * Get interets
     *
     * @return integer
     */
    public function getInterets()
    {
        return $this->interets;
    }

    /**
     * Set paidInterest
     *
     * @param integer $paidInterest
     *
     * @return EcheanciersEmprunteur
     */
    public function setPaidInterest($paidInterest)
    {
        $this->paidInterest = $paidInterest;

        return $this;
    }

    /**
     * Get paidInterest
     *
     * @return integer
     */
    public function getPaidInterest()
    {
        return $this->paidInterest;
    }

    /**
     * Set commission
     *
     * @param integer $commission
     *
     * @return EcheanciersEmprunteur
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
     * Set tva
     *
     * @param integer $tva
     *
     * @return EcheanciersEmprunteur
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
     * Set paidCommissionVatIncl
     *
     * @param integer $paidCommissionVatIncl
     *
     * @return EcheanciersEmprunteur
     */
    public function setPaidCommissionVatIncl($paidCommissionVatIncl)
    {
        $this->paidCommissionVatIncl = $paidCommissionVatIncl;

        return $this;
    }

    /**
     * Get paidCommissionVatIncl
     *
     * @return integer
     */
    public function getPaidCommissionVatIncl()
    {
        return $this->paidCommissionVatIncl;
    }

    /**
     * Set dateEcheanceEmprunteur
     *
     * @param \DateTime $dateEcheanceEmprunteur
     *
     * @return EcheanciersEmprunteur
     */
    public function setDateEcheanceEmprunteur($dateEcheanceEmprunteur)
    {
        $this->dateEcheanceEmprunteur = $dateEcheanceEmprunteur;

        return $this;
    }

    /**
     * Get dateEcheanceEmprunteur
     *
     * @return \DateTime
     */
    public function getDateEcheanceEmprunteur()
    {
        return $this->dateEcheanceEmprunteur;
    }

    /**
     * Set dateEcheanceEmprunteurReel
     *
     * @param \DateTime $dateEcheanceEmprunteurReel
     *
     * @return EcheanciersEmprunteur
     */
    public function setDateEcheanceEmprunteurReel(\DateTime $dateEcheanceEmprunteurReel = null)
    {
        $this->dateEcheanceEmprunteurReel = $dateEcheanceEmprunteurReel;

        return $this;
    }

    /**
     * Get dateEcheanceEmprunteurReel
     *
     * @return \DateTime
     */
    public function getDateEcheanceEmprunteurReel()
    {
        return $this->dateEcheanceEmprunteurReel;
    }

    /**
     * Set statusEmprunteur
     *
     * @param integer $statusEmprunteur
     *
     * @return EcheanciersEmprunteur
     */
    public function setStatusEmprunteur($statusEmprunteur)
    {
        $this->statusEmprunteur = $statusEmprunteur;

        return $this;
    }

    /**
     * Get statusEmprunteur
     *
     * @return integer
     */
    public function getStatusEmprunteur()
    {
        return $this->statusEmprunteur;
    }

    /**
     * Set statusRa
     *
     * @param integer $statusRa
     *
     * @return EcheanciersEmprunteur
     */
    public function setStatusRa($statusRa)
    {
        $this->statusRa = $statusRa;

        return $this;
    }

    /**
     * Get statusRa
     *
     * @return integer
     */
    public function getStatusRa()
    {
        return $this->statusRa;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return EcheanciersEmprunteur
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
     * @return EcheanciersEmprunteur
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
     * Get idEcheancierEmprunteur
     *
     * @return integer
     */
    public function getIdEcheancierEmprunteur()
    {
        return $this->idEcheancierEmprunteur;
    }

    /**
     * Set idProject
     *
     * @param \Unilend\Entity\Projects $idProject
     *
     * @return EcheanciersEmprunteur
     */
    public function setIdProject(\Unilend\Entity\Projects $idProject = null)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return \Unilend\Entity\Projects
     */
    public function getIdProject()
    {
        return $this->idProject;
    }
}
