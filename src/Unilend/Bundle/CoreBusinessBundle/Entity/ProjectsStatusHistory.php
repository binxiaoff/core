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
 * @ORM\HasLifecycleCallbacks
 */
class ProjectsStatusHistory
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_project", type="integer")
     */
    private $idProject;

    /**
     * @var ProjectsStatus
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project_status", referencedColumnName="id_project_status", nullable=false)
     * })
     */
    private $idProjectStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", length=16777215)
     */
    private $content;

    /**
     * @var int
     *
     * @ORM\Column(name="numero_relance", type="integer")
     */
    private $numeroRelance;

    /**
     * @var int
     *
     * @ORM\Column(name="id_user", type="integer")
     */
    private $idUser;

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
     * @param int $idProject
     *
     * @return ProjectsStatusHistory
     */
    public function setIdProject(int $idProject): ProjectsStatusHistory
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return int
     */
    public function getIdProject(): int
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
    public function setIdProjectStatus(ProjectsStatus $idProjectStatus): ProjectsStatusHistory
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
     * @param string|null $content
     *
     * @return ProjectsStatusHistory
     */
    public function setContent(?string $content): ProjectsStatusHistory
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Set numeroRelance
     *
     * @param int|null $numeroRelance
     *
     * @return ProjectsStatusHistory
     */
    public function setNumeroRelance(?int $numeroRelance): ProjectsStatusHistory
    {
        $this->numeroRelance = $numeroRelance;

        return $this;
    }

    /**
     * Get numeroRelance
     *
     * @return int|null
     */
    public function getNumeroRelance(): ?int
    {
        return $this->numeroRelance;
    }

    /**
     * Set idUser
     *
     * @param int $idUser
     *
     * @return ProjectsStatusHistory
     */
    public function setIdUser(int $idUser): ProjectsStatusHistory
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return int
     */
    public function getIdUser(): int
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
    public function setAdded(\DateTime $added): ProjectsStatusHistory
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime|null $updated
     *
     * @return ProjectsStatusHistory
     */
    public function setUpdated(?\DateTime $updated): ProjectsStatusHistory
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * Get idProjectStatusHistory
     *
     * @return int
     */
    public function getIdProjectStatusHistory(): int
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

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue(): void
    {
        $this->updated = new \DateTime();
    }
}
