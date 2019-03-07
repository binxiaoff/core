<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyAddress
 *
 * @ORM\Table(name="company_address", indexes={@ORM\Index(name="idx_company_address_updated", columns={"updated"})})
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\CompanyAddressRepository")
 */
class CompanyAddress
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=191)
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="zip", type="string", length=191)
     */
    private $zip;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=191)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="cog", type="string", length=5, nullable=true)
     */
    private $cog;

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
     * @ORM\Column(name="date_pending", type="datetime")
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
     * @var Companies
     *
     * @ORM\ManyToOne(targetEntity="Companies")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company", referencedColumnName="id_company", nullable=false)
     * })
     */
    private $idCompany;

    /**
     * @var Pays
     *
     * @ORM\ManyToOne(targetEntity="Pays")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_country", referencedColumnName="id_pays", nullable=false)
     * })
     */
    private $idCountry;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\AddressType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\AddressType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type", referencedColumnName="id", nullable=false)
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
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
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
     * @return string
     */
    public function getZip(): string
    {
        return $this->zip;
    }

    /**
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
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @param string|null $cog
     *
     * @return CompanyAddress
     */
    public function setCog(?string $cog): CompanyAddress
    {
        $this->cog = $cog;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCog(): ?string
    {
        return $this->cog;
    }

    /**
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
     * @return string|null
     */
    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    /**
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
     * @return string|null
     */
    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    /**
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
     * @return \DateTime
     */
    public function getDatePending(): \DateTime
    {
        return $this->datePending;
    }

    /**
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
     * @return \DateTime|null
     */
    public function getDateValidated(): ?\DateTime
    {
        return $this->dateValidated;
    }

    /**
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
     * @return \DateTime|null
     */
    public function getDateArchived(): ?\DateTime
    {
        return $this->dateArchived;
    }

    /**
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
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
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
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
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
     * @return Companies|null
     */
    public function getIdCompany(): ?Companies
    {
        return $this->idCompany;
    }

    /**
     * @param Pays $idCountry
     *
     * @return CompanyAddress
     */
    public function setIdCountry(Pays $idCountry): CompanyAddress
    {
        $this->idCountry = $idCountry;

        return $this;
    }

    /**
     * @return Pays
     */
    public function getIdCountry(): Pays
    {
        return $this->idCountry;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedAndPendingValue(): void
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
    public function setUpdatedValue(): void
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
