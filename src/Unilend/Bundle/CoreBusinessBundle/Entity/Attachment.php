<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Attachment
 *
 * @ORM\Table(name="attachment", uniqueConstraints={@ORM\UniqueConstraint(name="unique_id_owner_type_owner_id_type", columns={"id_type", "id_owner", "type_owner"})}, indexes={@ORM\Index(name="fk_attachment_id_type", columns={"id_type"}), @ORM\Index(name="idx_id_owner_type_owner", columns={"id_owner", "type_owner"})})
 * @ORM\Entity
 */
class Attachment
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_owner", type="integer", nullable=false)
     */
    private $idOwner;

    /**
     * @var string
     *
     * @ORM\Column(name="type_owner", type="string", length=45, nullable=false)
     */
    private $typeOwner;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=191, nullable=false)
     */
    private $path;

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
     * @var \DateTime
     *
     * @ORM\Column(name="archived", type="datetime", nullable=true)
     */
    private $archived;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type", referencedColumnName="id")
     * })
     */
    private $idType;



    /**
     * Set idOwner
     *
     * @param integer $idOwner
     *
     * @return Attachment
     */
    public function setIdOwner($idOwner)
    {
        $this->idOwner = $idOwner;

        return $this;
    }

    /**
     * Get idOwner
     *
     * @return integer
     */
    public function getIdOwner()
    {
        return $this->idOwner;
    }

    /**
     * Set typeOwner
     *
     * @param string $typeOwner
     *
     * @return Attachment
     */
    public function setTypeOwner($typeOwner)
    {
        $this->typeOwner = $typeOwner;

        return $this;
    }

    /**
     * Get typeOwner
     *
     * @return string
     */
    public function getTypeOwner()
    {
        return $this->typeOwner;
    }

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
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType $idType
     *
     * @return Attachment
     */
    public function setIdType(\Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType $idType = null)
    {
        $this->idType = $idType;

        return $this;
    }

    /**
     * Get idType
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType
     */
    public function getIdType()
    {
        return $this->idType;
    }
}
