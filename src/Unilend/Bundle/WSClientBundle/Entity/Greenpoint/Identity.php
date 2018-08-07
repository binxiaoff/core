<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Greenpoint;

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
    private $birthdate;

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
     * @return mixed
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @return mixed
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * @return mixed
     */
    public function getBirthplace()
    {
        return $this->birthplace;
    }

    /**
     * @return mixed
     */
    public function getNationality()
    {
        return $this->nationality;
    }

    /**
     * @return mixed
     */
    public function getMrz1()
    {
        return $this->mrz1;
    }

    /**
     * @return mixed
     */
    public function getMrz2()
    {
        return $this->mrz2;
    }

    /**
     * @return mixed
     */
    public function getMrz3()
    {
        return $this->mrz3;
    }

    /**
     * @return mixed
     */
    public function getIssuingCountry()
    {
        return $this->issuingCountry;
    }

    /**
     * @return mixed
     */
    public function getIssuingAuthority()
    {
        return $this->issuingAuthority;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getDocumentNumber()
    {
        return $this->documentNumber;
    }

    /**
     * @return mixed
     */
    public function getGender()
    {
        return $this->gender;
    }
}
