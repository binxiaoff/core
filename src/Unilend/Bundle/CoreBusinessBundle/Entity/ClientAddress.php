<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientAddress
 *
 * @ORM\Table(name="client_address", indexes={@ORM\Index(name="idx_client_address_id_client", columns={"id_client"}), @ORM\Index(name="idx_client_address_pays_v2_id_country", columns={"id_country"})})
 * @ORM\Entity
 */
class ClientAddress
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
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Attachment", inversedBy="ClientAddress")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_attachment", referencedColumnName="id")
     * })
     */
    private $idAttachment;

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
     * @var PaysV2
     *
     * @ORM\ManyToOne(targetEntity="PaysV2")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_country", referencedColumnName="id_pays")
     * })
     */
    private $idCountry;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client")
     * })
     */
    private $idClient;



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
     * @return ClientAddress
     */
    public function setAddress(string $address): ClientAddress
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
     * @return ClientAddress
     */
    public function setZip(string $zip): ClientAddress
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
     * @return ClientAddress
     */
    public function setCity(string $city): ClientAddress
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
     * @return ClientAddress
     */
    public function setIdAttachment(?Attachment $idAttachment): ClientAddress
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
     * Set datePending
     *
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
     * @return ClientAddress
     */
    public function setDateValidated(?\DateTime $dateValidated): ClientAddress
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
     * @return ClientAddress
     */
    public function setDateArchived(?\DateTime $dateArchived): ClientAddress
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
     * @return ClientAddress
     */
    public function setAdded(\DateTime $added): ClientAddress
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
     * @return ClientAddress
     */
    public function setUpdated(?\DateTime $updated): ClientAddress
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
     * Set idCountry
     *
     * @param PaysV2 $idCountry
     *
     * @return ClientAddress
     */
    public function setIdCountry(PaysV2 $idCountry): ClientAddress
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
     * Set idClient
     *
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
     * Get idClient
     *
     * @return Clients
     */
    public function getIdClient(): Clients
    {
        return $this->idClient;
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
}
