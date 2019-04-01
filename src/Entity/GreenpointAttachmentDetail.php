<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GreenpointAttachmentDetail
 *
 * @ORM\Table(name="greenpoint_attachment_detail", indexes={@ORM\Index(name="id_greenpoint_attachment", columns={"id_greenpoint_attachment"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class GreenpointAttachmentDetail
{
    /**
     * @var string
     *
     * @ORM\Column(name="document_type", type="string", length=8, nullable=true)
     */
    private $documentType;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_civility", type="string", length=1, nullable=true)
     */
    private $identityCivility;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_name", type="string", length=32, nullable=true)
     */
    private $identityName;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_surname", type="string", length=32, nullable=true)
     */
    private $identitySurname;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="identity_expiration_date", type="date", nullable=true)
     */
    private $identityExpirationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="identity_birthdate", type="date", nullable=true)
     */
    private $identityBirthdate;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_mrz1", type="string", length=191, nullable=true)
     */
    private $identityMrz1;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_mrz2", type="string", length=191, nullable=true)
     */
    private $identityMrz2;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_mrz3", type="string", length=191, nullable=true)
     */
    private $identityMrz3;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_nationality", type="string", length=3, nullable=true)
     */
    private $identityNationality;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_issuing_country", type="string", length=3, nullable=true)
     */
    private $identityIssuingCountry;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_issuing_authority", type="string", length=191, nullable=true)
     */
    private $identityIssuingAuthority;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_document_number", type="string", length=64, nullable=true)
     */
    private $identityDocumentNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_document_type_id", type="string", length=3, nullable=true)
     */
    private $identityDocumentTypeId;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_details_iban", type="string", length=40, nullable=true)
     */
    private $bankDetailsIban;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_details_bic", type="string", length=32, nullable=true)
     */
    private $bankDetailsBic;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_details_url", type="string", length=191, nullable=true)
     */
    private $bankDetailsUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="address_address", type="string", length=191, nullable=true)
     */
    private $addressAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="address_postal_code", type="string", length=16, nullable=true)
     */
    private $addressPostalCode;

    /**
     * @var string
     *
     * @ORM\Column(name="address_city", type="string", length=191, nullable=true)
     */
    private $addressCity;

    /**
     * @var string
     *
     * @ORM\Column(name="address_country", type="string", length=32, nullable=true)
     */
    private $addressCountry;

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
     * @ORM\Column(name="id_greenpoint_attachment_detail", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idGreenpointAttachmentDetail;

    /**
     * @var \Unilend\Entity\GreenpointAttachment
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\GreenpointAttachment", inversedBy="greenpointAttachmentDetail")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_greenpoint_attachment", referencedColumnName="id_greenpoint_attachment", nullable=false)
     * })
     */
    private $idGreenpointAttachment;

    /**
     * @param string|null $documentType
     *
     * @return GreenpointAttachmentDetail
     */
    public function setDocumentType(?string $documentType): GreenpointAttachmentDetail
    {
        $this->documentType = $documentType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDocumentType(): ?string
    {
        return $this->documentType;
    }

    /**
     * @param string|null $identityCivility
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityCivility(?string $identityCivility): GreenpointAttachmentDetail
    {
        $this->identityCivility = $identityCivility;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIdentityCivility(): ?string
    {
        return $this->identityCivility;
    }

    /**
     * @param string|null $identityName
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityName(?string $identityName): GreenpointAttachmentDetail
    {
        $this->identityName = $identityName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIdentityName(): ?string
    {
        return $this->identityName;
    }

    /**
     * @param string|null $identitySurname
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentitySurname(?string $identitySurname): GreenpointAttachmentDetail
    {
        $this->identitySurname = $identitySurname;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIdentitySurname(): ?string
    {
        return $this->identitySurname;
    }

    /**
     * @param \DateTime|null $identityExpirationDate
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityExpirationDate(?\DateTime $identityExpirationDate): GreenpointAttachmentDetail
    {
        $this->identityExpirationDate = $identityExpirationDate;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getIdentityExpirationDate(): ?\DateTime
    {
        return $this->identityExpirationDate;
    }

    /**
     * @param \DateTime|null $identityBirthdate
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityBirthdate(?\DateTime $identityBirthdate): GreenpointAttachmentDetail
    {
        $this->identityBirthdate = $identityBirthdate;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getIdentityBirthdate(): ?\DateTime
    {
        return $this->identityBirthdate;
    }

    /**
     * @param string|null $identityMrz1
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityMrz1(?string $identityMrz1): GreenpointAttachmentDetail
    {
        $this->identityMrz1 = $identityMrz1;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdentityMrz1(): ?string
    {
        return $this->identityMrz1;
    }

    /**
     * @param string|null $identityMrz2
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityMrz2(?string $identityMrz2): GreenpointAttachmentDetail
    {
        $this->identityMrz2 = $identityMrz2;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIdentityMrz2(): ?string
    {
        return $this->identityMrz2;
    }

    /**
     * @param string|null $identityMrz3
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityMrz3(?string $identityMrz3): GreenpointAttachmentDetail
    {
        $this->identityMrz3 = $identityMrz3;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIdentityMrz3(): ?string
    {
        return $this->identityMrz3;
    }

    /**
     * @param string|null $identityNationality
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityNationality(?string $identityNationality): GreenpointAttachmentDetail
    {
        $this->identityNationality = $identityNationality;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIdentityNationality(): ?string
    {
        return $this->identityNationality;
    }

    /**
     * @param string|null $identityIssuingCountry
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityIssuingCountry(?string $identityIssuingCountry): GreenpointAttachmentDetail
    {
        $this->identityIssuingCountry = $identityIssuingCountry;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIdentityIssuingCountry(): ?string
    {
        return $this->identityIssuingCountry;
    }

    /**
     * @param string|null $identityIssuingAuthority
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityIssuingAuthority(?string $identityIssuingAuthority): GreenpointAttachmentDetail
    {
        $this->identityIssuingAuthority = $identityIssuingAuthority;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIdentityIssuingAuthority(): ?string
    {
        return $this->identityIssuingAuthority;
    }

    /**
     * @param string|null $identityDocumentNumber
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityDocumentNumber(?string $identityDocumentNumber): GreenpointAttachmentDetail
    {
        $this->identityDocumentNumber = $identityDocumentNumber;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIdentityDocumentNumber(): ?string
    {
        return $this->identityDocumentNumber;
    }

    /**
     * @param string|null $identityDocumentTypeId
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityDocumentTypeId(?string $identityDocumentTypeId): GreenpointAttachmentDetail
    {
        $this->identityDocumentTypeId = $identityDocumentTypeId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIdentityDocumentTypeId(): ?string
    {
        return $this->identityDocumentTypeId;
    }

    /**
     * @param string|null $bankDetailsIban
     *
     * @return GreenpointAttachmentDetail
     */
    public function setBankDetailsIban(?string $bankDetailsIban): GreenpointAttachmentDetail
    {
        $this->bankDetailsIban = $bankDetailsIban;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBankDetailsIban(): ?string
    {
        return $this->bankDetailsIban;
    }

    /**
     * @param string|null $bankDetailsBic
     *
     * @return GreenpointAttachmentDetail
     */
    public function setBankDetailsBic(?string $bankDetailsBic): GreenpointAttachmentDetail
    {
        $this->bankDetailsBic = $bankDetailsBic;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBankDetailsBic(): ?string
    {
        return $this->bankDetailsBic;
    }

    /**
     * @param string|null $bankDetailsUrl
     *
     * @return GreenpointAttachmentDetail
     */
    public function setBankDetailsUrl(?string $bankDetailsUrl): GreenpointAttachmentDetail
    {
        $this->bankDetailsUrl = $bankDetailsUrl;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBankDetailsUrl(): ?string
    {
        return $this->bankDetailsUrl;
    }

    /**
     * @param string|null $addressAddress
     *
     * @return GreenpointAttachmentDetail
     */
    public function setAddressAddress(?string $addressAddress): GreenpointAttachmentDetail
    {
        $this->addressAddress = $addressAddress;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddressAddress(): ?string
    {
        return $this->addressAddress;
    }

    /**
     * @param string|null $addressPostalCode
     *
     * @return GreenpointAttachmentDetail
     */
    public function setAddressPostalCode(?string $addressPostalCode): GreenpointAttachmentDetail
    {
        $this->addressPostalCode = $addressPostalCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddressPostalCode(): ?string
    {
        return $this->addressPostalCode;
    }

    /**
     * @param string|null $addressCity
     *
     * @return GreenpointAttachmentDetail
     */
    public function setAddressCity(?string $addressCity): GreenpointAttachmentDetail
    {
        $this->addressCity = $addressCity;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddressCity(): ?string
    {
        return $this->addressCity;
    }

    /**
     * @param string|null $addressCountry
     *
     * @return GreenpointAttachmentDetail
     */
    public function setAddressCountry(?string $addressCountry): GreenpointAttachmentDetail
    {
        $this->addressCountry = $addressCountry;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddressCountry(): ?string
    {
        return $this->addressCountry;
    }

    /**
     * @param \DateTime $added
     *
     * @return GreenpointAttachmentDetail
     */
    public function setAdded(\DateTime $added): GreenpointAttachmentDetail
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
     * @return GreenpointAttachmentDetail
     */
    public function setUpdated(?\DateTime $updated): GreenpointAttachmentDetail
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
     * @return int
     */
    public function getIdGreenpointAttachmentDetail(): int
    {
        return $this->idGreenpointAttachmentDetail;
    }

    /**
     * @param GreenpointAttachment $idGreenpointAttachment
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdGreenpointAttachment(GreenpointAttachment $idGreenpointAttachment): GreenpointAttachmentDetail
    {
        $this->idGreenpointAttachment = $idGreenpointAttachment;

        return $this;
    }

    /**
     * @return GreenpointAttachment
     */
    public function getIdGreenpointAttachment(): GreenpointAttachment
    {
        return $this->idGreenpointAttachment;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue(): void
    {
        $this->updated = new \DateTime();
    }
}
