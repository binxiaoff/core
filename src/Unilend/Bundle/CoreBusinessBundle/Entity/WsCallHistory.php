<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WsCallHistory
 *
 * @ORM\Table(name="ws_call_history", indexes={@ORM\Index(name="id_resource", columns={"id_resource"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\WsCallHistoryRepository")
 * @ORM\HasLifecycleCallbacks
 */
class WsCallHistory
{
    /**
     * @var string
     *
     * @ORM\Column(name="siren", type="string", length=9, nullable=true)
     */
    private $siren;

    /**
     * @var string
     *
     * @ORM\Column(name="transfer_time", type="decimal", precision=6, scale=4, nullable=true)
     */
    private $transferTime;

    /**
     * @var string
     *
     * @ORM\Column(name="call_status", type="string", length=10, nullable=true)
     */
    private $callStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_call_history", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idCallHistory;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\WsExternalResource
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\WsExternalResource")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_resource", referencedColumnName="id_resource")
     * })
     */
    private $idResource;

    /**
     * @return string
     */
    public function getSiren()
    {
        return $this->siren;
    }

    /**
     * @param string $siren
     *
     * @return WsCallHistory
     */
    public function setSiren($siren)
    {
        $this->siren = $siren;

        return $this;
    }

    /**
     * @return string
     */
    public function getTransferTime()
    {
        return $this->transferTime;
    }

    /**
     * @param string $transferTime
     *
     * @return WsCallHistory
     */
    public function setTransferTime($transferTime)
    {
        $this->transferTime = $transferTime;

        return $this;
    }

    /**
     * @return string
     */
    public function getCallStatus()
    {
        return $this->callStatus;
    }

    /**
     * @param string $callStatus
     *
     * @return WsCallHistory
     */
    public function setCallStatus($callStatus)
    {
        $this->callStatus = $callStatus;

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
     * @return WsCallHistory
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdCallHistory()
    {
        return $this->idCallHistory;
    }

    /**
     * @param int $idCallHistory
     *
     * @return WsCallHistory
     */
    public function setIdCallHistory($idCallHistory)
    {
        $this->idCallHistory = $idCallHistory;

        return $this;
    }

    /**
     * @return WsExternalResource
     */
    public function getIdResource()
    {
        return $this->idResource;
    }

    /**
     * @param WsExternalResource $idResource
     *
     * @return WsCallHistory
     */
    public function setIdResource($idResource)
    {
        $this->idResource = $idResource;

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
}
