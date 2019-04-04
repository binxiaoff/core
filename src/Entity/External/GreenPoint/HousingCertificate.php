<?php

namespace Unilend\Entity\External\GreenPoint;

use JMS\Serializer\Annotation as JMS;
use Unilend\Service\WebServiceClient\GreenPointManager;

class HousingCertificate
{
    const CONTROL_STATUS_LABEL = [
        GreenPointManager::NOT_VERIFIED                   => 'Non vérifié',
        GreenPointManager::OUT_OF_BOUNDS                  => 'Hors périmètre (pas un justificatif de domicile)',
        GreenPointManager::FALSIFIED_OR_MINOR             => 'Falsifié',
        GreenPointManager::ILLEGIBLE                      => 'Illisible / coupé',
        GreenPointManager::VERSO_MISSING                  => 'Fournisseur hors périmètre',
        GreenPointManager::NAME_SURNAME_INVERSION         => 'Non cohérent / données : erreur sur le titulaire',
        GreenPointManager::INCOHERENT_OTHER_ERROR         => 'Non cohérent / données : erreur sur l\'adresse',
        GreenPointManager::EXPIRED                        => '-',
        GreenPointManager::CONFORM_COHERENT_NOT_QUALIFIED => 'Vérifié sauf prénom du titulaire non vérifié',
        GreenPointManager::CONFORM_COHERENT_QUALIFIED     => 'Conforme, cohérent et valide'
    ];

    /**
     * @JMS\SerializedName("statut_verification")
     * @JMS\Type("integer")
     */
    private $status;

    /**
     * @JMS\SerializedName("adresse")
     * @JMS\Type("string")
     */
    private $address;

    /**
     * @JMS\SerializedName("code_postal")
     * @JMS\Type("string")
     */
    private $zip;

    /**
     * @JMS\SerializedName("ville")
     * @JMS\Type("string")
     */
    private $city;

    /**
     * @JMS\SerializedName("pays")
     * @JMS\Type("string")
     */
    private $country;

    /**
     * @return string|null
     */
    public function getStatusLabel(): ?string
    {
        return self::CONTROL_STATUS_LABEL[$this->status] ?? null;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @return string|null
     */
    public function getZip(): ?string
    {
        return $this->zip;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }
}
