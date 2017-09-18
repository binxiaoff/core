<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DebtCollectionMission
 *
 * @ORM\Table(name="debt_collection_mission", indexes={@ORM\Index(name="idx_dc_mission_id_project", columns={"id_project"}), @ORM\Index(name="idx_dc_mission_id_client", columns={"id_client_debt_collector"}), @ORM\Index(name="idx_dc_mission_id_status", columns={"status"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class DebtCollectionMission
{
    const STATUS_ONGOING   = 0;
    const STATUS_ARCHIVED  = 1;

    const TYPE_AMICABLE   = 0;
    const TYPE_LITIGATION = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="fees_rate", type="decimal", precision=4, scale=2, nullable=true)
     */
    private $feesRate;

    /**
     * @var string
     *
     * @ORM\Column(name="attachment", type="string", length=191, nullable=true)
     */
    private $attachment;

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
     * @var \Projects
     *
     * @ORM\ManyToOne(targetEntity="Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $idProject;

    /**
     * @var \Clients
     *
     * @ORM\ManyToOne(targetEntity="Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client_debt_collector", referencedColumnName="id_client")
     * })
     */
    private $idClientDebtCollector;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     *
     * @return DebtCollectionMission
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return DebtCollectionMission
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getFeesRate()
    {
        return $this->feesRate;
    }

    /**
     * @param string $feesRate
     *
     * @return DebtCollectionMission
     */
    public function setFeesRate($feesRate)
    {
        $this->feesRate = $feesRate;

        return $this;
    }

    /**
     * @return string
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @param string $attachment
     *
     * @return DebtCollectionMission
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @param \DateTime $added
     *
     * @return DebtCollectionMission
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     *
     * @return DebtCollectionMission
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \Projects
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * @param \Projects $idProject
     *
     * @return DebtCollectionMission
     */
    public function setIdProject($idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * @return \Clients
     */
    public function getIdClientDebtCollector()
    {
        return $this->idClientDebtCollector;
    }

    /**
     * @param \Clients $idClientDebtCollector
     *
     * @return DebtCollectionMission
     */
    public function setIdClientDebtCollector($idClientDebtCollector)
    {
        $this->idClientDebtCollector = $idClientDebtCollector;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->added->getTimestamp()) {
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
