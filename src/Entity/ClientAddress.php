<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientAddress
 *
 * @ORM\Table(name="client_address", indexes={
 *     @ORM\Index(name="idx_client_address_id_client", columns={"id_client"}),
 *     @ORM\Index(name="idx_client_address_pays_id_country", columns={"id_country"}),
 *     @ORM\Index(name="idx_client_address_updated", columns={"updated"}),
 * })
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ClientAddressRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ClientAddress
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
     * Code officiel géographique designated by INSEE
     * @var string
     *
     * @ORM\Column(name="cog", type="string", length=5, nullable=true)
     */
    private $cog;

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
     * @var Pays
     *
     * @ORM\ManyToOne(targetEntity="Pays")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_country", referencedColumnName="id_pays", nullable=false)
     * })
     */
    private $idCountry;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClient;

    /**
     * @var \Unilend\Entity\AddressType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\AddressType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type", referencedColumnName="id", nullable=false)
     * })
     */
    private $idType;

    /**
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param string $address
     *
     * @return ClientAddress
     */
    public function setAddress(string $address): ClientAddress
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
     * @return ClientAddress
     */
    public function setZip(string $zip): ClientAddress
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
     * @return ClientAddress
     */
    public function setCity(string $city): ClientAddress
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
     * @return ClientAddress
     */
    public function setCog(?string $cog): ClientAddress
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
     * @param \DateTime $datePending
     *
     * @return ClientAddress
     */
    public function setDatePending(\DateTime $datePending): ClientAddress
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
     * @return ClientAddress
     */
    public function setDateValidated(?\DateTime $dateValidated): ClientAddress
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
     * @return ClientAddress
     */
    public function setDateArchived(?\DateTime $dateArchived): ClientAddress
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
     * @return ClientAddress
     */
    public function setAdded(\DateTime $added): ClientAddress
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
     * @return ClientAddress
     */
    public function setUpdated(?\DateTime $updated): ClientAddress
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
     * @param Pays $idCountry
     *
     * @return ClientAddress
     */
    public function setIdCountry(Pays $idCountry): ClientAddress
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
     * @param Clients $idClient
     *
     * @return ClientAddress
     */
    public function setIdClient(Clients $idClient): ClientAddress
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * @return Clients
     */
    public function getIdClient(): Clients
    {
        return $this->idClient;
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
     * @return ClientAddress
     */
    public function setIdType(AddressType $idType): ClientAddress
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
