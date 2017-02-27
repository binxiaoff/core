<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PartnerProjectAttachment
 *
 * @ORM\Table(name="partner_project_attachment", uniqueConstraints={@ORM\UniqueConstraint(name="uc_id_partner_id_attachment_type", columns={"id_partner", "id_attachment_type"})}, indexes={@ORM\Index(name="fk_partner_project_attachment_attachment_type", columns={"id_attachment_type"}), @ORM\Index(name="IDX_E130D16DEFB69766", columns={"id_partner"})})
 * @ORM\Entity
 */
class PartnerProjectAttachment
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="mandatory", type="boolean", nullable=false)
     */
    private $mandatory;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

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
     *   @ORM\JoinColumn(name="id_attachment_type", referencedColumnName="id")
     * })
     */
    private $idAttachmentType;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Partner
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Partner")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_partner", referencedColumnName="id")
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
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType $idAttachmentType
     *
     * @return PartnerProjectAttachment
     */
    public function setIdAttachmentType(\Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType $idAttachmentType = null)
    {
        $this->idAttachmentType = $idAttachmentType;

        return $this;
    }

    /**
     * Get idAttachmentType
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType
     */
    public function getIdAttachmentType()
    {
        return $this->idAttachmentType;
    }

    /**
     * Set idPartner
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Partner $idPartner
     *
     * @return PartnerProjectAttachment
     */
    public function setIdPartner(\Unilend\Bundle\CoreBusinessBundle\Entity\Partner $idPartner = null)
    {
        $this->idPartner = $idPartner;

        return $this;
    }

    /**
     * Get idPartner
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Partner
     */
    public function getIdPartner()
    {
        return $this->idPartner;
    }
}
