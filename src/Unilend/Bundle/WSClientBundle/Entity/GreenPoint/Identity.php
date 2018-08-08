<?php

namespace Unilend\Bundle\WSClientBundle\Entity\GreenPoint;

use JMS\Serializer\Annotation as JMS;
use Unilend\Bundle\WSClientBundle\Service\GreenPointManager;

class Identity
{
    const CONTROL_STATUS_LABEL = [
        GreenPointManager::NOT_VERIFIED                   => 'Non vérifié',
        GreenPointManager::OUT_OF_BOUNDS                  => 'Hors périmètre (pas un document d\'identité)',
        GreenPointManager::FALSIFIED_OR_MINOR             => 'Falsifiée ou mineur',
        GreenPointManager::ILLEGIBLE                      => 'Illisible / coupée',
        GreenPointManager::VERSO_MISSING                  => 'Verso seul : recto manquant',
        GreenPointManager::NAME_SURNAME_INVERSION         => 'Non cohérent / données : inversion nom - prénom',
        GreenPointManager::INCOHERENT_OTHER_ERROR         => 'Non cohérent / données : autre erreur',
        GreenPointManager::EXPIRED                        => 'Expiré',
        GreenPointManager::CONFORM_COHERENT_NOT_QUALIFIED => 'Conforme, cohérent et valide mais non labellisable',
        GreenPointManager::CONFORM_COHERENT_QUALIFIED     => 'Conforme, cohérent et valide + label GREENPOINT IDCONTROL'
    ];

    /**
     * @JMS\SerializedName("code")
     * @JMS\Type("string")
     */
    private $code;

    /**
     * @JMS\SerializedName("statut_verification")
     * @JMS\Type("integer")
     */
    private $status;

    /**
     * @JMS\SerializedName("expirationdate")
     * @JMS\Type("DateTime<'d/m/Y'>")
     */
    private $expirationDate;

    /**
     * @JMS\SerializedName("nom")
     * @JMS\Type("string")
     */
    private $name;

    /**
     * @JMS\SerializedName("prenom")
     * @JMS\Type("string")
     */
    private $firstName;

    /**
     * @JMS\SerializedName("date_naissance")
     * @JMS\Type("DateTime<'d/m/Y'>")
     */
    private $birthday;

    /**
     * @JMS\SerializedName("lieu_naissancee")
     * @JMS\Type("string")
     */
    private $birthplace;

    /**
     * @JMS\SerializedName("nationalite")
     * @JMS\Type("string")
     */
    private $nationality;

    /**
     * @JMS\SerializedName("mrz1")
     * @JMS\Type("string")
     */
    private $mrz1;

    /**
     * @JMS\SerializedName("mrz2")
     * @JMS\Type("string")
     */
    private $mrz2;

    /**
     * @JMS\SerializedName("mrz3")
     * @JMS\Type("string")
     */
    private $mrz3;

    /**
     * @JMS\SerializedName("pays_emetteur")
     * @JMS\Type("string")
     */
    private $issuingCountry;

    /**
     * @JMS\SerializedName("autorite_emettrice")
     * @JMS\Type("string")
     */
    private $issuingAuthority;

    /**
     * @JMS\SerializedName("type_id")
     * @JMS\Type("string")
     */
    private $type;

    /**
     * @JMS\SerializedName("numero")
     * @JMS\Type("string")
     */
    private $documentNumber;

    /**
     * @JMS\SerializedName("sexe")
     * @JMS\Type("string")
     */
    private $gender;

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getStatusLabel(): string
    {
        return self::CONTROL_STATUS_LABEL[$this->status];
    }

    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return \DateTime
     */
    public function getExpirationDate(): \DateTime
    {
        return $this->expirationDate;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @return \DateTime|null
     */
    public function getBirthday(): ?\DateTime
    {
        return $this->birthday;
    }

    /**
     * @return string|null
     */
    public function getBirthplace(): ?string
    {
        return $this->birthplace;
    }

    /**
     * @return string|null
     */
    public function getNationality(): ?string
    {
        return $this->nationality;
    }

    /**
     * @return string|null
     */
    public function getMrz1(): ?string
    {
        return $this->mrz1;
    }

    /**
     * @return string|null
     */
    public function getMrz2(): ?string
    {
        return $this->mrz2;
    }

    /**
     * @return string|null
     */
    public function getMrz3(): ?string
    {
        return $this->mrz3;
    }

    /**
     * @return string|null
     */
    public function getIssuingCountry(): ?string
    {
        return $this->issuingCountry;
    }

    /**
     * @return string|null
     */
    public function getIssuingAuthority(): ?string
    {
        return $this->issuingAuthority;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getDocumentNumber(): ?string
    {
        return $this->documentNumber;
    }

    /**
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->gender;
    }
}
