<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WireTransferOutUniversign
 *
 * @ORM\Table(name="wire_transfer_out_universign", indexes={@ORM\Index(name="idx_id_wire_transfer_out", columns={"id_wire_transfer_out"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class WireTransferOutUniversign implements UniversignEntityInterface
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="url_universign", type="string", length=191, nullable=true)
     */
    private $urlUniversign;

    /**
     * @var string
     *
     * @ORM\Column(name="id_universign", type="string", length=191, nullable=true)
     */
    private $idUniversign;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Virements
     *
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Virements", inversedBy="universign")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wire_transfer_out", referencedColumnName="id_virement")
     * })
     */
    private $idWireTransferOut;


    /**
     * Set name
     *
     * @param string $name
     *
     * @return WireTransferOutUniversign
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set urlUniversign
     *
     * @param string $urlUniversign
     *
     * @return WireTransferOutUniversign
     */
    public function setUrlUniversign($urlUniversign)
    {
        $this->urlUniversign = $urlUniversign;

        return $this;
    }

    /**
     * Get urlUniversign
     *
     * @return string
     */
    public function getUrlUniversign()
    {
        return $this->urlUniversign;
    }

    /**
     * Set idUniversign
     *
     * @param string $idUniversign
     *
     * @return WireTransferOutUniversign
     */
    public function setIdUniversign($idUniversign)
    {
        $this->idUniversign = $idUniversign;

        return $this;
    }

    /**
     * Get idUniversign
     *
     * @return string
     */
    public function getIdUniversign()
    {
        return $this->idUniversign;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return WireTransferOutUniversign
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return WireTransferOutUniversign
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
     * @return WireTransferOutUniversign
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
     * Set idWireTransferOut
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Virements $idWireTransferOut
     *
     * @return WireTransferOutUniversign
     */
    public function setIdWireTransferOut(\Unilend\Bundle\CoreBusinessBundle\Entity\Virements $idWireTransferOut = null)
    {
        $this->idWireTransferOut = $idWireTransferOut;

        return $this;
    }

    /**
     * Get idWireTransferOut
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Virements
     */
    public function getIdWireTransferOut()
    {
        return $this->idWireTransferOut;
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
