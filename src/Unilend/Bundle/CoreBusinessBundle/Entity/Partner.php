<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Partner
 *
 * @ORM\Table(name="partner", uniqueConstraints={@ORM\UniqueConstraint(name="label", columns={"label"})}, indexes={@ORM\Index(name="fk_partner_partner_type_type", columns={"type"})})
 * @ORM\Entity
 */
class Partner
{
    const STATUS_PENDING   = 1;
    const STATUS_VALIDATED = 2;
    const STATUS_DISABLED  = 3;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Companies")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company", referencedColumnName="id_company")
     * })
     */
    private $idCompany;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="logo", type="string", length=191, nullable=true)
     */
    private $logo;

    /**
     * @var boolean
     *
     * @ORM\Column(name="prospect", type="boolean", nullable=false)
     */
    private $prospect;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user_creation", referencedColumnName="id_user")
     * })
     */
    private $idUserCreation;

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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\PartnerType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\PartnerType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="type", referencedColumnName="id")
     * })
     */
    private $type;

    /**
     * @var PartnerProjectAttachment[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\PartnerProjectAttachment", mappedBy="idPartner")
     */
    private $attachmentTypes;

    /**
     * Projects constructor.
     */
    public function __construct() {
        $this->attachmentTypes = new ArrayCollection();
    }

    /**
     * Set idCompany
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Companies $idCompany
     *
     * @return Partner
     */
    public function setIdCompany(\Unilend\Bundle\CoreBusinessBundle\Entity\Companies $idCompany)
    {
        $this->idCompany = $idCompany;

        return $this;
    }

    /**
     * Get idUserCreation
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Companies
     */
    public function getIdCompany()
    {
        return $this->idCompany;
    }

    /**
     * Set label
     *
     * @param string $label
     *
     * @return Partner
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set logo
     *
     * @param string $logo
     *
     * @return Partner
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set prospect
     *
     * @param boolean $prospect
     *
     * @return Partner
     */
    public function setProspect($prospect)
    {
        $this->prospect = $prospect;

        return $this;
    }

    /**
     * Get prospect
     *
     * @return boolean
     */
    public function getProspect()
    {
        return $this->prospect;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Partner
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
     * Set idUserCreation
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Users $idUserCreation
     *
     * @return Partner
     */
    public function setIdUserCreation(\Unilend\Bundle\CoreBusinessBundle\Entity\Users $idUserCreation)
    {
        $this->idUserCreation = $idUserCreation;

        return $this;
    }

    /**
     * Get idUserCreation
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Users
     */
    public function getIdUserCreation()
    {
        return $this->idUserCreation;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Partner
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
     * @return Partner
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
     * Set type
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\PartnerType $type
     *
     * @return Partner
     */
    public function setType(\Unilend\Bundle\CoreBusinessBundle\Entity\PartnerType $type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\PartnerType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get attachmentTypes
     *
     * @param bool $mandatoryOnly
     *
     * @return PartnerProjectAttachment[]
     */
    public function getAttachmentTypes($mandatoryOnly = false)
    {
        if ($mandatoryOnly) {
            $attachmentTypes = [];
            foreach ($this->attachmentTypes as $attachmentType) {
                if ($attachmentType->getMandatory()) {
                    $attachmentTypes[] = $attachmentType;
                }
            }

            return $attachmentTypes;
        }

        return $this->attachmentTypes;
    }
}
