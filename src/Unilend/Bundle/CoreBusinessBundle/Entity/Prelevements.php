<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Prelevements
 *
 * @ORM\Table(name="prelevements", indexes={
 *     @ORM\Index(name="idx_prelevements_id_client", columns={"id_client"}),
 *     @ORM\Index(name="idx_prelevements_id_project", columns={"id_project"})
 * })
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\PrelevementsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Prelevements
{
    const STATUS_PENDING             = 0;
    const STATUS_SENT                = 1;
    const STATUS_VALID               = 2;
    const STATUS_TERMINATED          = 3;
    const STATUS_TEMPORARILY_BLOCKED = 4;

    const CLIENT_TYPE_LENDER   = 1;
    const CLIENT_TYPE_BORROWER = 2;

    const TYPE_RECURRENT = 1;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClient;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project", nullable=false)
     * })
     */
    private $idProject;

    /**
     * @var string
     *
     * @ORM\Column(name="motif", type="string", length=50)
     */
    private $motif;

    /**
     * @var int
     *
     * @ORM\Column(name="montant", type="integer")
     */
    private $montant;

    /**
     * @var string
     *
     * @ORM\Column(name="bic", type="string", length=100)
     */
    private $bic;

    /**
     * @var string
     *
     * @ORM\Column(name="iban", type="string", length=28)
     */
    private $iban;

    /**
     * @var int
     *
     * @ORM\Column(name="type_prelevement", type="smallint")
     */
    private $typePrelevement;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint")
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(name="num_prelevement", type="smallint")
     */
    private $numPrelevement;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_execution_demande_prelevement", type="date")
     */
    private $dateExecutionDemandePrelevement;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_echeance_emprunteur", type="date")
     */
    private $dateEcheanceEmprunteur;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added_xml", type="datetime", nullable=true)
     */
    private $addedXml;

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
     * @ORM\Column(name="id_prelevement", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idPrelevement;

    /**
     * Set idClient
     *
     * @param Clients $idClient
     *
     * @return Prelevements
     */
    public function setIdClient(Clients $idClient)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return Clients
     */
    public function getIdClient()
    {
        return $this->idClient;
    }

    /**
     * Set idProject
     *
     * @param Projects $idProject
     *
     * @return Prelevements
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
     * Set motif
     *
     * @param string $motif
     *
     * @return Prelevements
     */
    public function setMotif($motif)
    {
        $this->motif = $motif;

        return $this;
    }

    /**
     * Get motif
     *
     * @return string
     */
    public function getMotif()
    {
        return $this->motif;
    }

    /**
     * Set montant
     *
     * @param integer $montant
     *
     * @return Prelevements
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
     * Set bic
     *
     * @param string $bic
     *
     * @return Prelevements
     */
    public function setBic($bic)
    {
        $this->bic = $bic;

        return $this;
    }

    /**
     * Get bic
     *
     * @return string
     */
    public function getBic()
    {
        return $this->bic;
    }

    /**
     * Set iban
     *
     * @param string $iban
     *
     * @return Prelevements
     */
    public function setIban($iban)
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * Get iban
     *
     * @return string
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * Set typePrelevement
     *
     * @param integer $typePrelevement
     *
     * @return Prelevements
     */
    public function setTypePrelevement($typePrelevement)
    {
        $this->typePrelevement = $typePrelevement;

        return $this;
    }

    /**
     * Get typePrelevement
     *
     * @return integer
     */
    public function getTypePrelevement()
    {
        return $this->typePrelevement;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return Prelevements
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set numPrelevement
     *
     * @param integer $numPrelevement
     *
     * @return Prelevements
     */
    public function setNumPrelevement($numPrelevement)
    {
        $this->numPrelevement = $numPrelevement;

        return $this;
    }

    /**
     * Get numPrelevement
     *
     * @return integer
     */
    public function getNumPrelevement()
    {
        return $this->numPrelevement;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Prelevements
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
     * Set dateExecutionDemandePrelevement
     *
     * @param \DateTime $dateExecutionDemandePrelevement
     *
     * @return Prelevements
     */
    public function setDateExecutionDemandePrelevement($dateExecutionDemandePrelevement)
    {
        $this->dateExecutionDemandePrelevement = $dateExecutionDemandePrelevement;

        return $this;
    }

    /**
     * Get dateExecutionDemandePrelevement
     *
     * @return \DateTime
     */
    public function getDateExecutionDemandePrelevement()
    {
        return $this->dateExecutionDemandePrelevement;
    }

    /**
     * Set dateEcheanceEmprunteur
     *
     * @param \DateTime $dateEcheanceEmprunteur
     *
     * @return Prelevements
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
     * Set addedXml
     *
     * @param \DateTime $addedXml
     *
     * @return Prelevements
     */
    public function setAddedXml($addedXml)
    {
        $this->addedXml = $addedXml;

        return $this;
    }

    /**
     * Get addedXml
     *
     * @return \DateTime
     */
    public function getAddedXml()
    {
        return $this->addedXml;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Prelevements
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
     * @return Prelevements
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
     * Get idPrelevement
     *
     * @return integer
     */
    public function getIdPrelevement()
    {
        return $this->idPrelevement;
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
