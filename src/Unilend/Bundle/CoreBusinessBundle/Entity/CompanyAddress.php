<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Documents\Address;

/**
 * CompanyAddress
 *
 * @ORM\Table(name="company_address", indexes={@ORM\Index(name="idx_company_address_id_company", columns={"id_company"}), @ORM\Index(name="idx_company_address_pays_v2_id_country", columns={"id_country"})})
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity
 */
class CompanyAddress
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=191, nullable=false)
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="zip", type="string", length=191, nullable=false)
     */
    private $zip;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=191, nullable=false)
     */
    private $city;

    /**
     * @var Attachment
     *
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Attachment", inversedBy="CompanyAddress")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_attachment", referencedColumnName="id")
     * })
     */
    private $idAttachment;

    /**
     * @var string
     *
     * @ORM\Column(name="latitude", type="decimal", precision=10, scale=8, nullable=true)
     */
    private $latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="decimal", precision=11, scale=8, nullable=true)
     */
    private $longitude;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_pending", type="datetime", nullable=false)
     */
    private $datePending;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_validated", type="datetime", nullable=true)
     */
    private $dateValidated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_archived", type="datetime", nullable=true)
     */
    private $dateArchived;

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
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Companies")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company", referencedColumnName="id_company")
     * })
     */
    private $idCompany;

    /**
     * @var PaysV2
     *
     * @ORM\ManyToOne(targetEntity="PaysV2")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_country", referencedColumnName="id_pays")
     * })
     */
    private $idCountry;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\AddressType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\AddressType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type", referencedColumnName="id")
     * })
     */
    private $idType;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return CompanyAddress
     */
    public function setAddress(string $address): CompanyAddress
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Set zip
     *
     * @param string $zip
     *
     * @return CompanyAddress
     */
    public function setZip(string $zip): CompanyAddress
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get zip
     *
     * @return string
     */
    public function getZip(): string
    {
        return $this->zip;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return CompanyAddress
     */
    public function setCity(string $city): CompanyAddress
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * Set idAttachment
     *
     * @param Attachment|null $idAttachment
     *
     * @return CompanyAddress
     */
    public function setIdAttachment(?Attachment $idAttachment): CompanyAddress
    {
        $this->idAttachment = $idAttachment;

        return $this;
    }

    /**
     * Get idAttachment
     *
     * @return Attachment|null
     */
    public function getIdAttachment(): ?Attachment
    {
        return $this->idAttachment;
    }

    /**
     * Set latitude
     *
     * @param string|null $latitude
     *
     * @return CompanyAddress
     */
    public function setLatitude(?string $latitude): CompanyAddress
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return string|null
     */
    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param string|null $longitude
     *
     * @return CompanyAddress
     */
    public function setLongitude(?string $longitude): CompanyAddress
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return string|null
     */
    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    /**
     * Set datePending
     *
     * @param \DateTime $datePending
     *
     * @return CompanyAddress
     */
    public function setDatePending(\DateTime $datePending): CompanyAddress
    {
        $this->datePending = $datePending;

        return $this;
    }

    /**
     * Get datePending
     *
     * @return \DateTime
     */
    public function getDatePending(): \DateTime
    {
        return $this->datePending;
    }

    /**
     * Set dateValidated
     *
     * @param \DateTime|null $dateValidated
     *
     * @return CompanyAddress
     */
    public function setDateValidated(?\DateTime $dateValidated): CompanyAddress
    {
        $this->dateValidated = $dateValidated;

        return $this;
    }

    /**
     * Get dateValidated
     *
     * @return \DateTime|null
     */
    public function getDateValidated(): ?\DateTime
    {
        return $this->dateValidated;
    }

    /**
     * Set dateArchived
     *
     * @param \DateTime|null $dateArchived
     *
     * @return CompanyAddress
     */
    public function setDateArchived(?\DateTime $dateArchived): CompanyAddress
    {
        $this->dateArchived = $dateArchived;

        return $this;
    }

    /**
     * Get dateArchived
     *
     * @return \DateTime|null
     */
    public function getDateArchived(): ?\DateTime
    {
        return $this->dateArchived;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return CompanyAddress
     */
    public function setAdded(\DateTime $added): CompanyAddress
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime|null $updated
     *
     * @return CompanyAddress
     */
    public function setUpdated(?\DateTime $updated): CompanyAddress
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * Set idCompany
     *
     * @param Companies $idCompany
     *
     * @return CompanyAddress
     */
    public function setIdCompany(Companies $idCompany): CompanyAddress
    {
        $this->idCompany = $idCompany;

        return $this;
    }

    /**
     * Get idCompany
     *
     * @return Companies|null
     */
    public function getIdCompany(): ?Companies
    {
        return $this->idCompany;
    }

    /**
     * Set idCountry
     *
     * @param PaysV2 $idCountry
     *
     * @return CompanyAddress
     */
    public function setIdCountry(PaysV2 $idCountry): CompanyAddress
    {
        $this->idCountry = $idCountry;

        return $this;
    }

    /**
     * Get idCountry
     *
     * @return PaysV2
     */
    public function getIdCountry(): PaysV2
    {
        return $this->idCountry;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedAndPendingValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->added->getTimestamp()) {
            $this->added = new \DateTime();
        }

        if (! $this->datePending instanceof \DateTime || 1 > $this->datePending->getTimestamp()) {
            $this->datePending = new \DateTime();
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
     * @param AddressType $idType
     *
     * @return CompanyAddress
     */
    public function setIdType(AddressType $idType): CompanyAddress
    {
        $this->idType = $idType;

        return $this;
    }

    /**
     * @return AddressType
     */
    public function getIdType(): AddressType
    {
        return $this->idType;
    }
}
