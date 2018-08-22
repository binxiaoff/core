<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Echeanciers
 *
 * @ORM\Table(name="echeanciers", indexes={@ORM\Index(name="id_lender", columns={"id_lender"}), @ORM\Index(name="id_project", columns={"id_project"}), @ORM\Index(name="id_loan", columns={"id_loan"}), @ORM\Index(name="date_echeance_reel", columns={"date_echeance_reel"}), @ORM\Index(name="date_echeance_emprunteur_reel", columns={"date_echeance_emprunteur_reel"}), @ORM\Index(name="ordre", columns={"ordre"}), @ORM\Index(name="status", columns={"status"}), @ORM\Index(name="status_emprunteur", columns={"status_emprunteur"}), @ORM\Index(name="date_echeance_emprunteur", columns={"date_echeance_emprunteur"}), @ORM\Index(name="status_ra", columns={"status_ra"}), @ORM\Index(name="added", columns={"added"}), @ORM\Index(name="date_echeance", columns={"date_echeance"}), @ORM\Index(name="id_project_ordre", columns={"id_project", "ordre"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\EcheanciersRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Echeanciers
{
    const STATUS_PENDING          = 0;
    const STATUS_REPAID           = 1;
    const STATUS_PARTIALLY_REPAID = 2;

    const IS_NOT_EARLY_REPAID = 0;
    const IS_EARLY_REPAID     = 1;

    const STATUS_REPAYMENT_EMAIL_NOT_SENT = 0;
    const STATUS_REPAYMENT_EMAIL_SENT     = 1;

    /**
     * @var integer
     *
     * @deprecated This column will be deleted. Use $idLoan instead
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
     * @deprecated This column will be deleted. Use the summary of Echeanciers::$capital and Echeanciers::$interets instead
     *
     * @ORM\Column(name="montant", type="integer", nullable=false)
     */
    private $montant;

    /**
     * @var integer
     *
     * @ORM\Column(name="capital", type="integer", nullable=false)
     */
    private $capital;

    /**
     * @var integer
     *
     * @ORM\Column(name="interets", type="integer", nullable=false)
     */
    private $interets;

    /**
     * @var integer
     *
     * @ORM\Column(name="capital_rembourse", type="integer", nullable=false)
     */
    private $capitalRembourse;

    /**
     * @var integer
     *
     * @ORM\Column(name="interets_rembourses", type="integer", nullable=false)
     */
    private $interetsRembourses;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_echeance", type="datetime", nullable=false)
     */
    private $dateEcheance;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_echeance_reel", type="datetime", nullable=false)
     */
    private $dateEcheanceReel;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @deprecated This column will be deleted. Use EcheanciersEmprunteur::$dateEcheanceEmprunteur instead
     *
     * @ORM\Column(name="date_echeance_emprunteur", type="datetime", nullable=false)
     */
    private $dateEcheanceEmprunteur;

    /**
     * @var \DateTime
     *
     * @deprecated This column will be deleted. Use EcheanciersEmprunteur::$dateEcheanceEmprunteurReel instead
     *
     * @ORM\Column(name="date_echeance_emprunteur_reel", type="datetime", nullable=false)
     */
    private $dateEcheanceEmprunteurReel;

    /**
     * @var integer
     * @deprecated This column will be deleted. Use EcheanciersEmprunteur::$status instead
     *
     * @ORM\Column(name="status_emprunteur", type="integer", nullable=false)
     */
    private $statusEmprunteur;

    /**
     * @var integer
     *
     * @ORM\Column(name="status_ra", type="integer", nullable=false)
     */
    private $statusRa;

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
     * @ORM\Column(name="id_echeancier", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idEcheancier;

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
     * @deprecated This column will be deleted. Use $idLoan instead
     *
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender", referencedColumnName="id")
     * })
     */
    private $idLender;

    /**
     * Set idProject
     *
     * @deprecated idProject will be deleted. Use idLoan instead
     *
     * @param integer $idProject
     *
     * @return Echeanciers
     */
    public function setIdProject($idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @deprecated idProject will be deleted. Use idLoan instead
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
     * @return Echeanciers
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
     * @deprecated This column will be deleted. Use the summary of Echeanciers::$capital and Echeanciers::$interets instead
     *
     * @param integer $montant
     *
     * @return Echeanciers
     */
    public function setMontant($montant)
    {
        $this->montant = $montant;

        return $this;
    }

    /**
     * Get montant
     *
     * @deprecated This column will be deleted. Use the sum of Echeanciers::$capital and Echeanciers::$interets instead
     *
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
     * @return Echeanciers
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
     * Set interets
     *
     * @param integer $interets
     *
     * @return Echeanciers
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
     * Set capitalRembourse
     *
     * @param integer $capitalRembourse
     *
     * @return Echeanciers
     */
    public function setCapitalRembourse($capitalRembourse)
    {
        $this->capitalRembourse = $capitalRembourse;

        return $this;
    }

    /**
     * Get capitalRembourse
     *
     * @return integer
     */
    public function getCapitalRembourse()
    {
        return $this->capitalRembourse;
    }

    /**
     * Set interetsRembourses
     *
     * @param integer $interetsRembourses
     *
     * @return Echeanciers
     */
    public function setInteretsRembourses($interetsRembourses)
    {
        $this->interetsRembourses = $interetsRembourses;

        return $this;
    }

    /**
     * Get interetsRembourses
     *
     * @return integer
     */
    public function getInteretsRembourses()
    {
        return $this->interetsRembourses;
    }

    /**
     * Set dateEcheance
     *
     * @param \DateTime $dateEcheance
     *
     * @return Echeanciers
     */
    public function setDateEcheance($dateEcheance)
    {
        $this->dateEcheance = $dateEcheance;

        return $this;
    }

    /**
     * Get dateEcheance
     *
     * @return \DateTime
     */
    public function getDateEcheance()
    {
        return $this->dateEcheance;
    }

    /**
     * Set dateEcheanceReel
     *
     * @param \DateTime $dateEcheanceReel
     *
     * @return Echeanciers
     */
    public function setDateEcheanceReel($dateEcheanceReel)
    {
        $this->dateEcheanceReel = $dateEcheanceReel;

        return $this;
    }

    /**
     * Get dateEcheanceReel
     *
     * @return \DateTime
     */
    public function getDateEcheanceReel()
    {
        return $this->dateEcheanceReel;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Echeanciers
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set dateEcheanceEmprunteur
     *
     * @deprecated This column will be deleted. Use EcheanciersEmprunteur::$dateEcheanceEmprunteur instead
     *
     * @param \DateTime $dateEcheanceEmprunteur
     *
     * @return Echeanciers
     */
    public function setDateEcheanceEmprunteur($dateEcheanceEmprunteur)
    {
        $this->dateEcheanceEmprunteur = $dateEcheanceEmprunteur;

        return $this;
    }

    /**
     * Get dateEcheanceEmprunteur
     *
     * @deprecated This column will be deleted. Use EcheanciersEmprunteur::$dateEcheanceEmprunteur instead
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
     * @deprecated This column will be deleted. Use EcheanciersEmprunteur::$dateEcheanceEmprunteurReel instead
     *
     * @param \DateTime $dateEcheanceEmprunteurReel
     *
     * @return Echeanciers
     */
    public function setDateEcheanceEmprunteurReel($dateEcheanceEmprunteurReel)
    {
        $this->dateEcheanceEmprunteurReel = $dateEcheanceEmprunteurReel;

        return $this;
    }

    /**
     * Get dateEcheanceEmprunteurReel
     *
     * @deprecated This column will be deleted. Use EcheanciersEmprunteur::$dateEcheanceEmprunteurReel instead
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
     * @deprecated This column will be deleted. Use EcheanciersEmprunteur::$status instead
     *
     * @param integer $statusEmprunteur
     *
     * @return Echeanciers
     */
    public function setStatusEmprunteur($statusEmprunteur)
    {
        $this->statusEmprunteur = $statusEmprunteur;

        return $this;
    }

    /**
     * Get statusEmprunteur
     *
     * @deprecated This column will be deleted. Use EcheanciersEmprunteur::$status instead
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
     * @return Echeanciers
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
     * @return Echeanciers
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
     * @return Echeanciers
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
     * Get idEcheancier
     *
     * @return integer
     */
    public function getIdEcheancier()
    {
        return $this->idEcheancier;
    }

    /**
     * Set idLoan
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Loans $idLoan
     *
     * @return Echeanciers
     */
    public function setIdLoan(Loans $idLoan)
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
     * Set idLender
     * @deprecated idLender will be deleted. Use idLoan instead
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idLender
     *
     * @return Echeanciers
     */
    public function setIdLender(Wallet $idLender)
    {
        $this->idLender = $idLender;

        return $this;
    }

    /**
     * Get idLender
     * @deprecated idLender will be deleted. Use idLoan instead
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     */
    public function getIdLender()
    {
        return $this->idLender;
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
