<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TransferAttachment
 *
 * @ORM\Table(name="transfer_attachment", indexes={@ORM\Index(name="id_transfer", columns={"id_transfer"}), @ORM\Index(name="id_attachment", columns={"id_attachment"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\TransferAttachmentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class TransferAttachment
{
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
     * @var \Unilend\Entity\Transfer
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Transfer", inversedBy="attachments")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_transfer", referencedColumnName="id_transfer", nullable=false)
     * })
     */
    private $idTransfer;

    /**
     * @var \Unilend\Entity\Attachment
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Attachment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_attachment", referencedColumnName="id", nullable=false)
     * })
     */
    private $idAttachment;



    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return TransferAttachment
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
     * @return TransferAttachment
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
     * Set idTransfer
     *
     * @param \Unilend\Entity\Transfer $idTransfer
     *
     * @return TransferAttachment
     */
    public function setTransfer(Transfer $idTransfer = null)
    {
        $this->idTransfer = $idTransfer;

        return $this;
    }

    /**
     * Get idTransfer
     *
     * @return \Unilend\Entity\Transfer
     */
    public function getTransfer()
    {
        return $this->idTransfer;
    }

    /**
     * Set idAttachment
     *
     * @param \Unilend\Entity\Attachment $idAttachment
     *
     * @return TransferAttachment
     */
    public function setAttachment(Attachment $idAttachment = null)
    {
        $this->idAttachment = $idAttachment;

        return $this;
    }

    /**
     * Get idAttachment
     *
     * @return \Unilend\Entity\Attachment
     */
    public function getAttachment()
    {
        return $this->idAttachment;
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
