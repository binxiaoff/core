<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EcheanciersEmprunteur
 *
 * @ORM\Table(name="echeanciers_emprunteur", indexes={@ORM\Index(name="id_project", columns={"id_project"}), @ORM\Index(name="date_echeance_emprunteur_reel", columns={"date_echeance_emprunteur_reel"}), @ORM\Index(name="ordre", columns={"ordre"}), @ORM\Index(name="status_emprunteur", columns={"status_emprunteur"})})
 * @ORM\Entity
 */
class EcheanciersEmprunteur
{
    const STATUS_PENDING = 0;
    const STATUS_PAID    = 1;

    const STATUS_NO_EARLY_REPAYMENT   = 0;
    const STATUS_EARLY_REPAYMENT_DONE = 1;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
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
     * @ORM\Column(name="commission", type="integer", nullable=false)
     */
    private $commission;

    /**
     * @var integer
     *
     * @ORM\Column(name="tva", type="integer", nullable=false)
     */
    private $tva;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_echeance_emprunteur", type="datetime", nullable=false)
     */
    private $dateEcheanceEmprunteur;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_echeance_emprunteur_reel", type="datetime", nullable=false)
     */
    private $dateEcheanceEmprunteurReel;

    /**
     * @var integer
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
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_echeancier_emprunteur", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idEcheancierEmprunteur;


    /**
     * Set idProject
     *
     * @param Projects $idProject
     *
     * @return EcheanciersEmprunteur
     */
    public function setIdProject($idProject)
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
    public function setDateEcheanceEmprunteurReel($dateEcheanceEmprunteurReel)
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
}
