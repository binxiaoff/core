<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Receptions
 *
 * @ORM\Table(name="receptions", indexes={@ORM\Index(name="idx_receptions_type", columns={"type"}), @ORM\Index(name="idx_receptions_added", columns={"added"}), @ORM\Index(name="type", columns={"type"}), @ORM\Index(name="status_virement", columns={"status_virement"}), @ORM\Index(name="status_prelevement", columns={"status_prelevement"}), @ORM\Index(name="status_bo", columns={"status_bo"}), @ORM\Index(name="remb", columns={"remb"}), @ORM\Index(name="id_client", columns={"id_client"}), @ORM\Index(name="id_project", columns={"id_project"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ReceptionsRepository")
 */
class Receptions
{
    const STATUS_PENDING         = 0;
    const STATUS_ASSIGNED_MANUAL = 1;
    const STATUS_ASSIGNED_AUTO   = 2;
    const STATUS_IGNORED_MANUAL  = 3;
    const STATUS_IGNORED_AUTO    = 4;

    const TYPE_DIRECT_DEBIT  = 1;
    const TYPE_WIRE_TRANSFER = 2;
    const TYPE_CHEQUE        = 3;
    const TYPE_UNKNOWN       = 4;

    const REPAYMENT_TYPE_NORMAL         = 0;
    const REPAYMENT_TYPE_EARLY          = 1;
    const REPAYMENT_TYPE_REGULARISATION = 2;
    const REPAYMENT_TYPE_RECOVERY       = 3;

    const WIRE_TRANSFER_STATUS_RECEIVED = 1;
    const WIRE_TRANSFER_STATUS_SENT     = 2;
    const WIRE_TRANSFER_STATUS_REJECTED = 3;

    const DIRECT_DEBIT_STATUS_SENT     = 2;
    const DIRECT_DEBIT_STATUS_REJECTED = 3;

    const CHEQUE_STATUS_RECEIVED = 1; //uses the same field as wire_transfer_status

    /**
     * @var string
     *
     * @ORM\Column(name="motif", type="string", length=191, nullable=false)
     */
    private $motif;

    /**
     * @var integer
     *
     * @ORM\Column(name="montant", type="integer", nullable=false)
     */
    private $montant;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="type_remb", type="integer", nullable=false)
     */
    private $typeRemb;

    /**
     * @var integer
     *
     * @ORM\Column(name="status_virement", type="integer", nullable=false)
     */
    private $statusVirement;

    /**
     * @var integer
     *
     * @ORM\Column(name="status_prelevement", type="integer", nullable=false)
     */
    private $statusPrelevement;

    /**
     * @var integer
     *
     * @ORM\Column(name="status_bo", type="integer", nullable=false)
     */
    private $statusBo;

    /**
     * @var integer
     *
     * @ORM\Column(name="remb", type="integer", nullable=false)
     */
    private $remb;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client")
     * })
     */
    private $idClient;

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
     * @var string
     *
     * @ORM\Column(name="ligne", type="text", length=16777215, nullable=false)
     */
    private $ligne;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user", referencedColumnName="id_user")
     * })
     */
    private $idUser;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="assignment_date", type="datetime", nullable=false)
     */
    private $assignmentDate;

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
     * @ORM\Column(name="id_reception", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idReception;

    /**
     * Set motif
     *
     * @param string $motif
     *
     * @return Receptions
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
     * @return Receptions
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
     * Set type
     *
     * @param integer $type
     *
     * @return Receptions
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
     * Set typeRemb
     *
     * @param integer $typeRemb
     *
     * @return Receptions
     */
    public function setTypeRemb($typeRemb)
    {
        $this->typeRemb = $typeRemb;

        return $this;
    }

    /**
     * Get typeRemb
     *
     * @return integer
     */
    public function getTypeRemb()
    {
        return $this->typeRemb;
    }

    /**
     * Set statusVirement
     *
     * @param integer $statusVirement
     *
     * @return Receptions
     */
    public function setStatusVirement($statusVirement)
    {
        $this->statusVirement = $statusVirement;

        return $this;
    }

    /**
     * Get statusVirement
     *
     * @return integer
     */
    public function getStatusVirement()
    {
        return $this->statusVirement;
    }

    /**
     * Set statusPrelevement
     *
     * @param integer $statusPrelevement
     *
     * @return Receptions
     */
    public function setStatusPrelevement($statusPrelevement)
    {
        $this->statusPrelevement = $statusPrelevement;

        return $this;
    }

    /**
     * Get statusPrelevement
     *
     * @return integer
     */
    public function getStatusPrelevement()
    {
        return $this->statusPrelevement;
    }

    /**
     * Set statusBo
     *
     * @param integer $statusBo
     *
     * @return Receptions
     */
    public function setStatusBo($statusBo)
    {
        $this->statusBo = $statusBo;

        return $this;
    }

    /**
     * Get statusBo
     *
     * @return integer
     */
    public function getStatusBo()
    {
        return $this->statusBo;
    }

    /**
     * Set remb
     *
     * @param integer $remb
     *
     * @return Receptions
     */
    public function setRemb($remb)
    {
        $this->remb = $remb;

        return $this;
    }

    /**
     * Get remb
     *
     * @return integer
     */
    public function getRemb()
    {
        return $this->remb;
    }

    /**
     * Set idClient
     *
     * @param Clients $idClient
     *
     * @return Receptions
     */
    public function setIdClient(Clients $idClient = null)
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
     * @return Receptions
     */
    public function setIdProject(Projects $idProject = null)
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
     * Set ligne
     *
     * @param string $ligne
     *
     * @return Receptions
     */
    public function setLigne($ligne)
    {
        $this->ligne = $ligne;

        return $this;
    }

    /**
     * Get ligne
     *
     * @return string
     */
    public function getLigne()
    {
        return $this->ligne;
    }

    /**
     * Set idUser
     *
     * @param Users $idUser
     *
     * @return Receptions
     */
    public function setIdUser(Users $idUser = null)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return Users
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * Set assignmentDate
     *
     * @param \DateTime $assignmentDate
     *
     * @return Receptions
     */
    public function setAssignmentDate($assignmentDate)
    {
        $this->assignmentDate = $assignmentDate;

        return $this;
    }

    /**
     * Get assignmentDate
     *
     * @return \DateTime
     */
    public function getAssignmentDate()
    {
        return $this->assignmentDate;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Receptions
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
     * @return Receptions
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
     * Get idReception
     *
     * @return integer
     */
    public function getIdReception()
    {
        return $this->idReception;
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
