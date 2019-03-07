<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectStatusHistoryReason
 *
 * @ORM\Table(name="project_status_history_reason", indexes={
 *     @ORM\Index(name="idx_project_status_history_id_project_status_history", columns={"id_project_status_history"}),
 *     @ORM\Index(name="idx_project_rejection_reason_id_rejection_reason", columns={"id_rejection_reason"}),
 *     @ORM\Index(name="idx_project_abandon_reason_id_abandon_reason", columns={"id_abandon_reason"})
 * })
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ProjectStatusHistoryReasonRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ProjectStatusHistoryReason
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectAbandonReason
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectAbandonReason")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_abandon_reason", referencedColumnName="id_abandon")
     * })
     */
    private $idAbandonReason;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatusHistory
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatusHistory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project_status_history", referencedColumnName="id_project_status_history", nullable=false)
     * })
     */
    private $idProjectStatusHistory;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRejectionReason
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRejectionReason")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_rejection_reason", referencedColumnName="id_rejection")
     * })
     */
    private $idRejectionReason;

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProjectStatusHistoryReason
     */
    public function setAdded(\DateTime $added): ProjectStatusHistoryReason
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set idAbandonReason
     *
     * @param ProjectAbandonReason|null $idAbandonReason
     *
     * @return ProjectStatusHistoryReason
     */
    public function setIdAbandonReason(?ProjectAbandonReason $idAbandonReason = null): ProjectStatusHistoryReason
    {
        $this->idAbandonReason = $idAbandonReason;

        return $this;
    }

    /**
     * Get idAbandonReason
     *
     * @return ProjectAbandonReason|null
     */
    public function getIdAbandonReason(): ?ProjectAbandonReason
    {
        return $this->idAbandonReason;
    }

    /**
     * Set idProjectStatusHistory
     *
     * @param ProjectsStatusHistory|null $idProjectStatusHistory
     *
     * @return ProjectStatusHistoryReason
     */
    public function setIdProjectStatusHistory(?ProjectsStatusHistory $idProjectStatusHistory = null): ProjectStatusHistoryReason
    {
        $this->idProjectStatusHistory = $idProjectStatusHistory;

        return $this;
    }

    /**
     * Get idProjectStatusHistory
     *
     * @return ProjectsStatusHistory
     */
    public function getIdProjectStatusHistory(): ProjectsStatusHistory
    {
        return $this->idProjectStatusHistory;
    }

    /**
     * Set idRejectionReason
     *
     * @param ProjectRejectionReason|null $idRejectionReason
     *
     * @return ProjectStatusHistoryReason
     */
    public function setIdRejectionReason(?ProjectRejectionReason $idRejectionReason = null): ProjectStatusHistoryReason
    {
        $this->idRejectionReason = $idRejectionReason;

        return $this;
    }

    /**
     * Get idRejectionReason
     *
     * @return ProjectRejectionReason|null
     */
    public function getIdRejectionReason(): ?ProjectRejectionReason
    {
        return $this->idRejectionReason;
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
}
