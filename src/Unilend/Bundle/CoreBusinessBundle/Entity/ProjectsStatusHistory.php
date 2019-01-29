<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\Common\Collections\{
    ArrayCollection, Criteria
};
use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectsStatusHistory
 *
 * @ORM\Table(name="projects_status_history", indexes={
 *     @ORM\Index(name="id_project_status", columns={"id_project_status"}),
 *     @ORM\Index(name="id_user", columns={"id_user"}),
 *     @ORM\Index(name="numero_relance", columns={"numero_relance"}),
 *     @ORM\Index(name="idx_psh_idproject", columns={"id_project"})
 * })
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ProjectsStatusHistoryRepository")
 */
class ProjectsStatusHistory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_project", type="integer", nullable=false)
     */
    private $idProject;

    /**
     * @var ProjectsStatus
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project_status", referencedColumnName="id_project_status")
     * })
     */
    private $idProjectStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", length=16777215, nullable=false)
     */
    private $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="numero_relance", type="integer", nullable=false)
     */
    private $numeroRelance;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_user", type="integer", nullable=false)
     */
    private $idUser;

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
     * @ORM\Column(name="id_project_status_history", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idProjectStatusHistory;

    /**
     * @var ProjectStatusHistoryReason[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectStatusHistoryReason", mappedBy="idProjectStatusHistory")
     */
    private $abandonReasons;

    /**
     * @var ProjectStatusHistoryReason[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectStatusHistoryReason", mappedBy="idProjectStatusHistory")
     */
    private $rejectionReasons;

    public function __construct()
    {
        $this->abandonReasons   = new ArrayCollection();
        $this->rejectionReasons = new ArrayCollection();
    }

    /**
     * Set idProject
     *
     * @param integer $idProject
     *
     * @return ProjectsStatusHistory
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
     * Set idProjectStatus
     *
     * @param ProjectsStatus $idProjectStatus
     *
     * @return ProjectsStatusHistory
     */
    public function setIdProjectStatus(ProjectsStatus $idProjectStatus)
    {
        $this->idProjectStatus = $idProjectStatus;

        return $this;
    }

    /**
     * Get idProjectStatus
     *
     * @return ProjectsStatus
     */
    public function getIdProjectStatus(): ProjectsStatus
    {
        return $this->idProjectStatus;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return ProjectsStatusHistory
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set numeroRelance
     *
     * @param integer $numeroRelance
     *
     * @return ProjectsStatusHistory
     */
    public function setNumeroRelance($numeroRelance)
    {
        $this->numeroRelance = $numeroRelance;

        return $this;
    }

    /**
     * Get numeroRelance
     *
     * @return integer
     */
    public function getNumeroRelance()
    {
        return $this->numeroRelance;
    }

    /**
     * Set idUser
     *
     * @param integer $idUser
     *
     * @return ProjectsStatusHistory
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectsStatusHistory
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
     * @return ProjectsStatusHistory
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
     * Get idProjectStatusHistory
     *
     * @return integer
     */
    public function getIdProjectStatusHistory()
    {
        return $this->idProjectStatusHistory;
    }

    /**
     * @return ArrayCollection|ProjectStatusHistoryReason[]
     */
    public function getAbandonReasons(): ArrayCollection
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->neq('idAbandonReason', null));

        return $this->abandonReasons->matching($criteria);
    }

    /**
     * @return ArrayCollection|ProjectStatusHistoryReason[]
     */
    public function getRejectionReasons(): ArrayCollection
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->neq('idRejectionReason', null));

        return $this->rejectionReasons->matching($criteria);
    }
}
