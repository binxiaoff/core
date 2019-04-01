<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Attachment
 *
 * @ORM\Table(name="attachment", indexes={@ORM\Index(name="fk_attachment_id_type", columns={"id_type"}), @ORM\Index(name="id_client", columns={"id_client"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\AttachmentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Attachment
{
    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=191)
     */
    private $path;

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
     * @var \DateTime
     *
     * @ORM\Column(name="archived", type="datetime", nullable=true)
     */
    private $archived;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Entity\AttachmentType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\AttachmentType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type", referencedColumnName="id", nullable=false)
     * })
     */
    private $idType;

    /**
     * @var \Unilend\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients", inversedBy="attachments")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClient;

    /**
     * @var GreenpointAttachment
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\GreenpointAttachment", mappedBy="idAttachment")
     */
    private $greenpointAttachment;

    /**
     * @var BankAccount
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\BankAccount", mappedBy="idAttachment")
     */
    private $bankAccount;

    /**
     * @var string
     *
     * @ORM\Column(name="original_name", type="string", length=191, nullable=true)
     */
    private $originalName;


    /**
     * Set path
     *
     * @param string $path
     *
     * @return Attachment
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Attachment
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
     * @return Attachment
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
     * Set archived
     *
     * @param \DateTime $archived
     *
     * @return Attachment
     */
    public function setArchived($archived)
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Get archived
     *
     * @return \DateTime
     */
    public function getArchived()
    {
        return $this->archived;
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
     * Set idType
     *
     * @param \Unilend\Entity\AttachmentType $idType
     *
     * @return Attachment
     */
    public function setType(AttachmentType $idType = null)
    {
        $this->idType = $idType;

        return $this;
    }

    /**
     * Get idType
     *
     * @return \Unilend\Entity\AttachmentType
     */
    public function getType()
    {
        return $this->idType;
    }

    /**
     * Set idClient
     *
     * @param \Unilend\Entity\Clients $idClient
     *
     * @return Attachment
     */
    public function setClient(Clients $idClient = null)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return \Unilend\Entity\Clients
     */
    public function getClient()
    {
        return $this->idClient;
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
     * Get greenpointAttachment
     *
     * @return GreenpointAttachment
     */
    public function getGreenpointAttachment()
    {
        return $this->greenpointAttachment;
    }

    /**
     * Get bankAccount
     *
     * @return BankAccount
     */
    public function getBankAccount()
    {
        return $this->bankAccount;
    }

    /**
     * Set originalName
     *
     * @param string $originalName
     *
     * @return Attachment
     */
    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;

        return $this;
    }

    /**
     * Get originalName
     *
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }
}
