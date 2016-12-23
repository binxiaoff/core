<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Receptions
 *
 * @ORM\Table(name="receptions", indexes={@ORM\Index(name="idx_receptions_type", columns={"type"}), @ORM\Index(name="idx_receptions_added", columns={"added"}), @ORM\Index(name="type", columns={"type"}), @ORM\Index(name="status_virement", columns={"status_virement"}), @ORM\Index(name="status_prelevement", columns={"status_prelevement"}), @ORM\Index(name="status_bo", columns={"status_bo"}), @ORM\Index(name="remb", columns={"remb"}), @ORM\Index(name="id_client", columns={"id_client"}), @ORM\Index(name="id_project", columns={"id_project"})})
 * @ORM\Entity
 */
class Receptions
{
    const STATUS_PENDING = 0;
    const STATUS_MANUALLY_ASSIGNED = 1;
    const STATUS_AUTO_ASSIGNED = 2;
    const STATUS_REJECTED = 3;

    const REPAYMENT_TYPE_NORMAL         = 0;
    const REPAYMENT_TYPE_EARLY          = 1;
    const REPAYMENT_TYPE_REGULARISATION = 2;
    const REPAYMENT_TYPE_RECOVERY       = 3;

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
     * @var boolean
     *
     * @ORM\Column(name="type", type="boolean", nullable=false)
     */
    private $type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="type_remb", type="boolean", nullable=false)
     */
    private $typeRemb;

    /**
     * @var integer
     *
     * @ORM\Column(name="status_virement", type="integer", nullable=false)
     */
    private $statusVirement;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status_prelevement", type="boolean", nullable=false)
     */
    private $statusPrelevement;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status_bo", type="boolean", nullable=false)
     */
    private $statusBo;

    /**
     * @var boolean
     *
     * @ORM\Column(name="remb", type="boolean", nullable=false)
     */
    private $remb;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_client", type="integer", nullable=false)
     */
    private $idClient;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project", type="integer", nullable=false)
     */
    private $idProject;

    /**
     * @var string
     *
     * @ORM\Column(name="ligne", type="text", length=16777215, nullable=false)
     */
    private $ligne;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     */
    private $idUser = '0';

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
     * @ORM\Column(name="updated", type="datetime", nullable=false)
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
     * @param boolean $type
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
     * @return boolean
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set typeRemb
     *
     * @param boolean $typeRemb
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
     * @return boolean
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
     * @param boolean $statusPrelevement
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
     * @return boolean
     */
    public function getStatusPrelevement()
    {
        return $this->statusPrelevement;
    }

    /**
     * Set statusBo
     *
     * @param boolean $statusBo
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
     * @return boolean
     */
    public function getStatusBo()
    {
        return $this->statusBo;
    }

    /**
     * Set remb
     *
     * @param boolean $remb
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
     * @return boolean
     */
    public function getRemb()
    {
        return $this->remb;
    }

    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return Receptions
     */
    public function setIdClient($idClient)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return integer
     */
    public function getIdClient()
    {
        return $this->idClient;
    }

    /**
     * Set idProject
     *
     * @param integer $idProject
     *
     * @return Receptions
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
     * @param integer $idUser
     *
     * @return Receptions
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return integer
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
}
