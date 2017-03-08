<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Transfer
 *
 * @ORM\Table(name="transfer", indexes={@ORM\Index(name="id_client_origin", columns={"id_client_origin"}), @ORM\Index(name="id_client_receiver", columns={"id_client_receiver"}), @ORM\Index(name="fk_transfer_id_transfer_type", columns={"id_transfer_type"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\TransferRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Transfer
{
    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=110, nullable=false)
     */
    private $comment;

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
     * @ORM\Column(name="id_transfer", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idTransfer;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\TransferType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\TransferType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_transfer_type", referencedColumnName="id_type")
     * })
     */
    private $idTransferType;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client_receiver", referencedColumnName="id_client")
     * })
     */
    private $idClientReceiver;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client_origin", referencedColumnName="id_client")
     * })
     */
    private $idClientOrigin;

    /**
     * @var ProjectAttachment[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\TransferAttachment", mappedBy="idTransfer")
     */
    private $attachments;

    /**
     * Projects constructor.
     */
    public function __construct() {
        $this->attachments = new ArrayCollection();
    }

    /**
     * Set comment
     *
     * @param string $comment
     *
     * @return Transfer
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Transfer
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
     * @return Transfer
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
     * Get idTransfer
     *
     * @return integer
     */
    public function getIdTransfer()
    {
        return $this->idTransfer;
    }

    /**
     * Set idTransferType
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\TransferType $idTransferType
     *
     * @return Transfer
     */
    public function setTransferType(TransferType $idTransferType = null)
    {
        $this->idTransferType = $idTransferType;

        return $this;
    }

    /**
     * Get idTransferType
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\TransferType
     */
    public function getTransferType()
    {
        return $this->idTransferType;
    }

    /**
     * Set idClientReceiver
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Clients $idClientReceiver
     *
     * @return Transfer
     */
    public function setClientReceiver(Clients $idClientReceiver = null)
    {
        $this->idClientReceiver = $idClientReceiver;

        return $this;
    }

    /**
     * Get idClientReceiver
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     */
    public function getClientReceiver()
    {
        return $this->idClientReceiver;
    }

    /**
     * Set idClientOrigin
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Clients $idClientOrigin
     *
     * @return Transfer
     */
    public function setClientOrigin(Clients $idClientOrigin = null)
    {
        $this->idClientOrigin = $idClientOrigin;

        return $this;
    }

    /**
     * Get idClientOrigin
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     */
    public function getIdClientOrigin()
    {
        return $this->idClientOrigin;
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
     * Get project attachments
     *
     * @return ProjectAttachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }
}
