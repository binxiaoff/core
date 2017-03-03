<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TransferAttachment
 *
 * @ORM\Table(name="transfer_attachment", indexes={@ORM\Index(name="id_transfer", columns={"id_transfer"}), @ORM\Index(name="id_attachment", columns={"id_attachment"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class TransferAttachment
{
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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Transfer
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Transfer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_transfer", referencedColumnName="id_transfer")
     * })
     */
    private $idTransfer;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Attachment
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Attachment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_attachment", referencedColumnName="id")
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
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Transfer $idTransfer
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
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Transfer
     */
    public function getTransfer()
    {
        return $this->idTransfer;
    }

    /**
     * Set idAttachment
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Attachment $idAttachment
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
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Attachment
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
