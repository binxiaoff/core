<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * DebtCollectionMission
 *
 * @ORM\Table(name="debt_collection_mission", indexes={@ORM\Index(name="idx_dc_mission_id_user_creation", columns={"id_user_creation"}), @ORM\Index(name="idx_dc_mission_id_user_archiving", columns={"id_user_archiving"}), @ORM\Index(name="idx_dc_mission_id_project", columns={"id_project"}), @ORM\Index(name="idx_dc_mission_id_client", columns={"id_client_debt_collector"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class DebtCollectionMission
{
    const TYPE_AMICABLE   = 0;
    const TYPE_LITIGATION = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;

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
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $idProject;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client_debt_collector", referencedColumnName="id_client")
     * })
     */
    private $idClientDebtCollector;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user_creation", referencedColumnName="id_user")
     * })
     */
    private $idUserCreation;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user_archiving", referencedColumnName="id_user")
     * })
     */
    private $idUserArchiving;

    /**
     * @var DebtCollectionMissionPaymentSchedule[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\DebtCollectionMissionPaymentSchedule", mappedBy="idMission")
     *
     */
    private $debtCollectionMissionPaymentSchedules;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="archived", type="datetime", nullable=true)
     */
    private $archived;

    public function __construct()
    {
        $this->debtCollectionMissionPaymentSchedules = new ArrayCollection();
    }

    /**
     * Set type
     *
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
     * Get type
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set feesRate
     *
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
     * Get feesRate
     *
     * @return string
     */
    public function getFeesRate()
    {
        return $this->feesRate;
    }

    /**
     * Set attachment
     *
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
     * Get attachment
     *
     * @return string
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * Set added
     *
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
     * @return DebtCollectionMission
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set idProject
     *
     * @param Projects $idProject
     *
     * @return DebtCollectionMission
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
     * Set idClientDebtCollector
     *
     * @param Clients $idClientDebtCollector
     *
     * @return DebtCollectionMission
     */
    public function setIdClientDebtCollector(Clients $idClientDebtCollector)
    {
        $this->idClientDebtCollector = $idClientDebtCollector;

        return $this;
    }

    /**
     * Get idClientDebtCollector
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     */
    public function getIdClientDebtCollector()
    {
        return $this->idClientDebtCollector;
    }

    /**
     * Get debtCollectionMissionPaymentSchedules
     *
     * @return DebtCollectionMissionPaymentSchedule[]
     */
    public function getDebtCollectionMissionPaymentSchedules()
    {
        return $this->debtCollectionMissionPaymentSchedules;
    }

    /**
     * Set debtCollectionMissionPaymentSchedules
     *
     * @param DebtCollectionMissionPaymentSchedule[] $debtCollectionMissionPaymentSchedules
     *
     * @return DebtCollectionMission
     */
    public function setDebtCollectionMissionPaymentSchedules($debtCollectionMissionPaymentSchedules)
    {
        $this->debtCollectionMissionPaymentSchedules = $debtCollectionMissionPaymentSchedules;

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

    /**
     * Set idUserCreation
     *
     * @param Users $idUserCreation
     *
     * @return DebtCollectionMission
     */
    public function setIdUserCreation($idUserCreation)
    {
        $this->idUserCreation = $idUserCreation;

        return $this;
    }

    /**
     * Get idUserCreation
     *
     * @return Users
     */
    public function getIdUserCreation()
    {
        return $this->idUserCreation;
    }

    /**
     * Set idUserArchiving
     *
     * @param Users $idUserArchiving
     *
     * @return DebtCollectionMission
     */
    public function setIdUserArchiving($idUserArchiving)
    {
        $this->idUserArchiving = $idUserArchiving;

        return $this;
    }

    /**
     * Get idUserArchiving
     *
     * @return Users
     */
    public function getIdUserArchiving()
    {
        return $this->idUserArchiving;
    }

    /**
     * @return \DateTime
     */
    public function getArchived()
    {
        return $this->archived;
    }

    /**
     * @param \DateTime|null $archived
     *
     * @return DebtCollectionMission
     */
    public function setArchived(\DateTime $archived = null)
    {
        $this->archived = $archived;

        return $this;
    }
}
