<?php

namespace Unilend\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Transfer
 *
 * @ORM\Table(name="transfer", indexes={@ORM\Index(name="idx_transfer_id_client_origin", columns={"id_client_origin"}), @ORM\Index(name="idx_transfer_id_client_receiver", columns={"id_client_receiver"}), @ORM\Index(name="idx_transfer_id_transfer_type", columns={"id_transfer_type"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\TransferRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Transfer
{
    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=110)
     */
    private $comment;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_transfer", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idTransfer;

    /**
     * @var \Unilend\Entity\TransferType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\TransferType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_transfer_type", referencedColumnName="id_type", nullable=false)
     * })
     */
    private $idTransferType;

    /**
     * @var \Unilend\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client_receiver", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClientReceiver;

    /**
     * @var \Unilend\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client_origin", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClientOrigin;

    /**
     * @var ProjectAttachment[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\TransferAttachment", mappedBy="idTransfer")
     */
    private $attachments;

    public function __construct()
    {
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
     * @param \Unilend\Entity\TransferType $idTransferType
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
     * @return \Unilend\Entity\TransferType
     */
    public function getTransferType()
    {
        return $this->idTransferType;
    }

    /**
     * Set idClientReceiver
     *
     * @param \Unilend\Entity\Clients $idClientReceiver
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
     * @return \Unilend\Entity\Clients
     */
    public function getClientReceiver()
    {
        return $this->idClientReceiver;
    }

    /**
     * Set idClientOrigin
     *
     * @param \Unilend\Entity\Clients $idClientOrigin
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
     * @return \Unilend\Entity\Clients
     */
    public function getClientOrigin()
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
