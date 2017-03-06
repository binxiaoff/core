<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Transfer
 *
 * @ORM\Table(name="transfer", indexes={@ORM\Index(name="id_transfer_type", columns={"id_transfer_type"})})
 * @ORM\Entity
 */
class Transfer
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_client_origin", type="integer", nullable=false)
     */
    private $idClientOrigin;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_client_receiver", type="integer", nullable=false)
     */
    private $idClientReceiver;

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
     * Set idClientOrigin
     *
     * @param integer $idClientOrigin
     *
     * @return Transfer
     */
    public function setIdClientOrigin($idClientOrigin)
    {
        $this->idClientOrigin = $idClientOrigin;

        return $this;
    }

    /**
     * Get idClientOrigin
     *
     * @return integer
     */
    public function getIdClientOrigin()
    {
        return $this->idClientOrigin;
    }

    /**
     * Set idClientReceiver
     *
     * @param integer $idClientReceiver
     *
     * @return Transfer
     */
    public function setIdClientReceiver($idClientReceiver)
    {
        $this->idClientReceiver = $idClientReceiver;

        return $this;
    }

    /**
     * Get idClientReceiver
     *
     * @return integer
     */
    public function getIdClientReceiver()
    {
        return $this->idClientReceiver;
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
    public function setIdTransferType(\Unilend\Bundle\CoreBusinessBundle\Entity\TransferType $idTransferType = null)
    {
        $this->idTransferType = $idTransferType;

        return $this;
    }

    /**
     * Get idTransferType
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\TransferType
     */
    public function getIdTransferType()
    {
        return $this->idTransferType;
    }
}
