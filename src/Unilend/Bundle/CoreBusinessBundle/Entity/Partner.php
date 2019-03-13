<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Partner
 *
 * @ORM\Table(name="partner")
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\PartnerRepository")
 */
class Partner
{
    const STATUS_PENDING   = 1;
    const STATUS_VALIDATED = 2;
    const STATUS_DISABLED  = 3;

    const PARTNER_CALS_ID            = 1;
    const PARTNER_CACIB_COLLPUB_ID   = 2;
    const PARTNER_CACIB_CORPORATE_ID = 3;
    const PARTNER_UNIFERGIE_ID       = 4;

    const PARTNER_CALS_LABEL            = 'cals';
    const PARTNER_CACIB_COLLPUB_LABEL   = 'cacib_collpub';
    const PARTNER_CACIB_CORPORATE_LABEL = 'cacib_corporate';
    const PARTNER_UNIFERGIE_LABEL       = 'unifergie';

    /**
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Companies")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company", referencedColumnName="id_company", nullable=false)
     * })
     */
    private $idCompany;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, unique=true)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="logo", type="string", length=191, nullable=true)
     */
    private $logo;

    /**
     * @var bool
     *
     * @ORM\Column(name="prospect", type="boolean", nullable=false, options={"default": true})
     */
    private $prospect;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var Users
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
     * @var PartnerType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\PartnerType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="type", referencedColumnName="id", nullable=false)
     * })
     */
    private $type;

    /**
     * @var PartnerProjectAttachment[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\PartnerProjectAttachment", mappedBy="idPartner")
     * @ORM\OrderBy({"rank" = "ASC"})
     */
    private $attachmentTypes;

    /**
     * @var PartnerThirdParty[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\PartnerThirdParty", mappedBy="idPartner")
     */
    private $partnerThirdParties;

    /**
     * @var PartnerProduct[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\PartnerProduct", mappedBy="idPartner")
     */
    private $productAssociations;

    /**
     * Projects constructor.
     */
    public function __construct()
    {
        $this->attachmentTypes     = new ArrayCollection();
        $this->partnerThirdParties = new ArrayCollection();
        $this->productAssociations = new ArrayCollection();
    }

    /**
     * Set idCompany
     *
     * @param Companies $idCompany
     *
     * @return Partner
     */
    public function setIdCompany(Companies $idCompany)
    {
        $this->idCompany = $idCompany;

        return $this;
    }

    /**
     * Get idUserCreation
     *
     * @return Companies
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
     * @param bool $prospect
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
     * @return bool
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
     * @param Users $idUserCreation
     *
     * @return Partner
     */
    public function setIdUserCreation(Users $idUserCreation)
    {
        $this->idUserCreation = $idUserCreation;

        return $this;
    }

    /**
     * Get idUserCreation
     *
     * @return Users
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
     * @param PartnerType $type
     *
     * @return Partner
     */
    public function setType(PartnerType $type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return PartnerType
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

    /**
     * @return ArrayCollection|PartnerThirdParty[]
     */
    public function getPartnerThirdParties()
    {
        return $this->partnerThirdParties;
    }

    /**
     * @param array|null $status
     *
     * @return PartnerProduct[]
     */
    public function getProductAssociations(array $status = null)
    {
        if (null === $status) {
            return iterator_to_array($this->productAssociations);
        }

        $productAssociations = [];

        foreach ($this->productAssociations as $association) {
            if (in_array($association->getIdProduct()->getStatus(), $status)) {
                $productAssociations[] = $association;
            }
        }

        return $productAssociations;
    }
}
