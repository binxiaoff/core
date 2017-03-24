<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

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
     * @ORM\Column(name="id_greenpoint_attachment_detail", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idGreenpointAttachmentDetail;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_greenpoint_attachment", referencedColumnName="id_greenpoint_attachment")
     * })
     */
    private $idGreenpointAttachment;


    /**
     * Set documentType
     *
     * @param string $documentType
     *
     * @return GreenpointAttachmentDetail
     */
    public function setDocumentType($documentType)
    {
        $this->documentType = $documentType;

        return $this;
    }

    /**
     * Get documentType
     *
     * @return string
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * Set identityCivility
     *
     * @param string $identityCivility
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityCivility($identityCivility)
    {
        $this->identityCivility = $identityCivility;

        return $this;
    }

    /**
     * Get identityCivility
     *
     * @return string
     */
    public function getIdentityCivility()
    {
        return $this->identityCivility;
    }

    /**
     * Set identityName
     *
     * @param string $identityName
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityName($identityName)
    {
        $this->identityName = $identityName;

        return $this;
    }

    /**
     * Get identityName
     *
     * @return string
     */
    public function getIdentityName()
    {
        return $this->identityName;
    }

    /**
     * Set identitySurname
     *
     * @param string $identitySurname
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentitySurname($identitySurname)
    {
        $this->identitySurname = $identitySurname;

        return $this;
    }

    /**
     * Get identitySurname
     *
     * @return string
     */
    public function getIdentitySurname()
    {
        return $this->identitySurname;
    }

    /**
     * Set identityExpirationDate
     *
     * @param \DateTime $identityExpirationDate
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityExpirationDate($identityExpirationDate)
    {
        $this->identityExpirationDate = $identityExpirationDate;

        return $this;
    }

    /**
     * Get identityExpirationDate
     *
     * @return \DateTime
     */
    public function getIdentityExpirationDate()
    {
        return $this->identityExpirationDate;
    }

    /**
     * Set identityBirthdate
     *
     * @param \DateTime $identityBirthdate
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityBirthdate($identityBirthdate)
    {
        $this->identityBirthdate = $identityBirthdate;

        return $this;
    }

    /**
     * Get identityBirthdate
     *
     * @return \DateTime
     */
    public function getIdentityBirthdate()
    {
        return $this->identityBirthdate;
    }

    /**
     * Set identityMrz1
     *
     * @param string $identityMrz1
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityMrz1($identityMrz1)
    {
        $this->identityMrz1 = $identityMrz1;

        return $this;
    }

    /**
     * Get identityMrz1
     *
     * @return string
     */
    public function getIdentityMrz1()
    {
        return $this->identityMrz1;
    }

    /**
     * Set identityMrz2
     *
     * @param string $identityMrz2
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityMrz2($identityMrz2)
    {
        $this->identityMrz2 = $identityMrz2;

        return $this;
    }

    /**
     * Get identityMrz2
     *
     * @return string
     */
    public function getIdentityMrz2()
    {
        return $this->identityMrz2;
    }

    /**
     * Set identityMrz3
     *
     * @param string $identityMrz3
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityMrz3($identityMrz3)
    {
        $this->identityMrz3 = $identityMrz3;

        return $this;
    }

    /**
     * Get identityMrz3
     *
     * @return string
     */
    public function getIdentityMrz3()
    {
        return $this->identityMrz3;
    }

    /**
     * Set identityNationality
     *
     * @param string $identityNationality
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityNationality($identityNationality)
    {
        $this->identityNationality = $identityNationality;

        return $this;
    }

    /**
     * Get identityNationality
     *
     * @return string
     */
    public function getIdentityNationality()
    {
        return $this->identityNationality;
    }

    /**
     * Set identityIssuingCountry
     *
     * @param string $identityIssuingCountry
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityIssuingCountry($identityIssuingCountry)
    {
        $this->identityIssuingCountry = $identityIssuingCountry;

        return $this;
    }

    /**
     * Get identityIssuingCountry
     *
     * @return string
     */
    public function getIdentityIssuingCountry()
    {
        return $this->identityIssuingCountry;
    }

    /**
     * Set identityIssuingAuthority
     *
     * @param string $identityIssuingAuthority
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityIssuingAuthority($identityIssuingAuthority)
    {
        $this->identityIssuingAuthority = $identityIssuingAuthority;

        return $this;
    }

    /**
     * Get identityIssuingAuthority
     *
     * @return string
     */
    public function getIdentityIssuingAuthority()
    {
        return $this->identityIssuingAuthority;
    }

    /**
     * Set identityDocumentNumber
     *
     * @param string $identityDocumentNumber
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityDocumentNumber($identityDocumentNumber)
    {
        $this->identityDocumentNumber = $identityDocumentNumber;

        return $this;
    }

    /**
     * Get identityDocumentNumber
     *
     * @return string
     */
    public function getIdentityDocumentNumber()
    {
        return $this->identityDocumentNumber;
    }

    /**
     * Set identityDocumentTypeId
     *
     * @param string $identityDocumentTypeId
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdentityDocumentTypeId($identityDocumentTypeId)
    {
        $this->identityDocumentTypeId = $identityDocumentTypeId;

        return $this;
    }

    /**
     * Get identityDocumentTypeId
     *
     * @return string
     */
    public function getIdentityDocumentTypeId()
    {
        return $this->identityDocumentTypeId;
    }

    /**
     * Set bankDetailsIban
     *
     * @param string $bankDetailsIban
     *
     * @return GreenpointAttachmentDetail
     */
    public function setBankDetailsIban($bankDetailsIban)
    {
        $this->bankDetailsIban = $bankDetailsIban;

        return $this;
    }

    /**
     * Get bankDetailsIban
     *
     * @return string
     */
    public function getBankDetailsIban()
    {
        return $this->bankDetailsIban;
    }

    /**
     * Set bankDetailsBic
     *
     * @param string $bankDetailsBic
     *
     * @return GreenpointAttachmentDetail
     */
    public function setBankDetailsBic($bankDetailsBic)
    {
        $this->bankDetailsBic = $bankDetailsBic;

        return $this;
    }

    /**
     * Get bankDetailsBic
     *
     * @return string
     */
    public function getBankDetailsBic()
    {
        return $this->bankDetailsBic;
    }

    /**
     * Set bankDetailsUrl
     *
     * @param string $bankDetailsUrl
     *
     * @return GreenpointAttachmentDetail
     */
    public function setBankDetailsUrl($bankDetailsUrl)
    {
        $this->bankDetailsUrl = $bankDetailsUrl;

        return $this;
    }

    /**
     * Get bankDetailsUrl
     *
     * @return string
     */
    public function getBankDetailsUrl()
    {
        return $this->bankDetailsUrl;
    }

    /**
     * Set addressAddress
     *
     * @param string $addressAddress
     *
     * @return GreenpointAttachmentDetail
     */
    public function setAddressAddress($addressAddress)
    {
        $this->addressAddress = $addressAddress;

        return $this;
    }

    /**
     * Get addressAddress
     *
     * @return string
     */
    public function getAddressAddress()
    {
        return $this->addressAddress;
    }

    /**
     * Set addressPostalCode
     *
     * @param string $addressPostalCode
     *
     * @return GreenpointAttachmentDetail
     */
    public function setAddressPostalCode($addressPostalCode)
    {
        $this->addressPostalCode = $addressPostalCode;

        return $this;
    }

    /**
     * Get addressPostalCode
     *
     * @return string
     */
    public function getAddressPostalCode()
    {
        return $this->addressPostalCode;
    }

    /**
     * Set addressCity
     *
     * @param string $addressCity
     *
     * @return GreenpointAttachmentDetail
     */
    public function setAddressCity($addressCity)
    {
        $this->addressCity = $addressCity;

        return $this;
    }

    /**
     * Get addressCity
     *
     * @return string
     */
    public function getAddressCity()
    {
        return $this->addressCity;
    }

    /**
     * Set addressCountry
     *
     * @param string $addressCountry
     *
     * @return GreenpointAttachmentDetail
     */
    public function setAddressCountry($addressCountry)
    {
        $this->addressCountry = $addressCountry;

        return $this;
    }

    /**
     * Get addressCountry
     *
     * @return string
     */
    public function getAddressCountry()
    {
        return $this->addressCountry;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return GreenpointAttachmentDetail
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
     * @return GreenpointAttachmentDetail
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
     * Get idGreenpointAttachmentDetail
     *
     * @return integer
     */
    public function getIdGreenpointAttachmentDetail()
    {
        return $this->idGreenpointAttachmentDetail;
    }

    /**
     * Set idGreenpointAttachment
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment $idGreenpointAttachment
     *
     * @return GreenpointAttachmentDetail
     */
    public function setIdGreenpointAttachment(\Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment $idGreenpointAttachment = null)
    {
        $this->idGreenpointAttachment = $idGreenpointAttachment;

        return $this;
    }

    /**
     * Get idGreenpointAttachment
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment
     */
    public function getIdGreenpointAttachment()
    {
        return $this->idGreenpointAttachment;
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

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
