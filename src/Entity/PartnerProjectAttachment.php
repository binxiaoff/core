<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PartnerProjectAttachment
 *
 * @ORM\Table(name="partner_project_attachment", uniqueConstraints={@ORM\UniqueConstraint(name="uc_id_partner_id_attachment_type", columns={"id_partner", "id_attachment_type"})}, indexes={@ORM\Index(name="fk_partner_project_attachment_attachment_type", columns={"id_attachment_type"}), @ORM\Index(name="IDX_E130D16DEFB69766", columns={"id_partner"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class PartnerProjectAttachment
{
    /**
     * @var bool
     *
     * @ORM\Column(name="mandatory", type="boolean")
     */
    private $mandatory;

    /**
     * @var int
     *
     * @ORM\Column(name="rank", type="smallint", nullable=true)
     */
    private $rank;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var AttachmentType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\AttachmentType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_attachment_type", referencedColumnName="id", nullable=false)
     * })
     */
    private $idAttachmentType;

    /**
     * @var Partner
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Partner", inversedBy="attachmentTypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_partner", referencedColumnName="id", nullable=false)
     * })
     */
    private $idPartner;



    /**
     * Set mandatory
     *
     * @param boolean $mandatory
     *
     * @return PartnerProjectAttachment
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;

        return $this;
    }

    /**
     * Get mandatory
     *
     * @return boolean
     */
    public function getMandatory()
    {
        return $this->mandatory;
    }

    /**
     * Set rank
     *
     * @param int $rank
     *
     * @return PartnerProjectAttachment
     */
    public function setRank($rank)
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * Get rank
     *
     * @return int
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return PartnerProjectAttachment
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set idAttachmentType
     *
     * @param AttachmentType $idAttachmentType
     *
     * @return PartnerProjectAttachment
     */
    public function setAttachmentType(AttachmentType $idAttachmentType = null)
    {
        $this->idAttachmentType = $idAttachmentType;

        return $this;
    }

    /**
     * Get idAttachmentType
     *
     * @return AttachmentType
     */
    public function getAttachmentType()
    {
        return $this->idAttachmentType;
    }

    /**
     * Set idPartner
     *
     * @param Partner $idPartner
     *
     * @return PartnerProjectAttachment
     */
    public function setPartner(Partner $idPartner = null)
    {
        $this->idPartner = $idPartner;

        return $this;
    }

    /**
     * Get idPartner
     *
     * @return Partner
     */
    public function getPartner()
    {
        return $this->idPartner;
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
