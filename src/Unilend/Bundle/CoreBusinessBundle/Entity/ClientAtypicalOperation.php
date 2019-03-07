<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientAtypicalOperation
 *
 * @ORM\Table(name="client_atypical_operation", indexes={@ORM\Index(name="idx_client_atypical_operation_id_client", columns={"id_client"}), @ORM\Index(name="idx_client_atypical_operation_status", columns={"detection_status"}), @ORM\Index(name="fk_vigilance_rule_client_atypical_operation_id_rule", columns={"id_rule"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ClientAtypicalOperation
{
    const STATUS_PENDING        = 0;
    const STATUS_WAITING_ACK    = 1;
    const STATUS_TREATED        = 2;

    /**
     * @var string
     *
     * @ORM\Column(name="atypical_value", type="string", length=191, nullable=true)
     */
    private $atypicalValue;

    /**
     * @var string
     *
     * @ORM\Column(name="operation_log", type="text", length=65535, nullable=true)
     */
    private $operationLog;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user", referencedColumnName="id_user", nullable=false)
     * })
     */
    private $idUser;

    /**
     * @var string
     *
     * @ORM\Column(name="user_comment", type="text", length=65535, nullable=true)
     */
    private $userComment;

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_rule", referencedColumnName="id", nullable=false)
     * })
     */
    private $rule;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $client;

    /**
     * @var int
     *
     * @ORM\Column(name="detection_status", type="smallint")
     */
    private $detectionStatus;

    /**
     * @return string
     */
    public function getAtypicalValue()
    {
        return $this->atypicalValue;
    }

    /**
     * @param string $atypicalValue
     *
     * @return ClientAtypicalOperation
     */
    public function setAtypicalValue($atypicalValue)
    {
        $this->atypicalValue = $atypicalValue;

        return $this;
    }

    /**
     * @return string
     */
    public function getOperationLog()
    {
        return $this->operationLog;
    }

    /**
     * @param string $operationLog
     *
     * @return ClientAtypicalOperation
     */
    public function setOperationLog($operationLog)
    {
        $this->operationLog = $operationLog;

        return $this;
    }

    /**
     * @return Users
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * @param Users $idUser
     *
     * @return ClientAtypicalOperation
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserComment()
    {
        return $this->userComment;
    }

    /**
     * @param string $userComment
     *
     * @return ClientAtypicalOperation
     */
    public function setUserComment($userComment)
    {
        $this->userComment = $userComment;

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
     * @return ClientAtypicalOperation
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
     * @return ClientAtypicalOperation
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return VigilanceRule
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param VigilanceRule $rule
     *
     * @return ClientAtypicalOperation
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * @return Clients
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Clients $client
     *
     * @return ClientAtypicalOperation
     */
    public function setClient($client)
    {
        $this->client = $client;

        return $this;
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

    /**
     * Set detectionStatus
     *
     * @param integer $detectionStatus
     *
     * @return ClientAtypicalOperation
     */
    public function setDetectionStatus($detectionStatus)
    {
        $this->detectionStatus = $detectionStatus;

        return $this;
    }

    /**
     * Get detectionStatus
     *
     * @return boolean
     */
    public function getDetectionStatus()
    {
        return $this->detectionStatus;
    }
}
