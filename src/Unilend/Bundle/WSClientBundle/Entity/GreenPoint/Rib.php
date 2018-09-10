<?php

namespace Unilend\Bundle\WSClientBundle\Entity\GreenPoint;

use JMS\Serializer\Annotation as JMS;
use Unilend\Bundle\WSClientBundle\Service\GreenPointManager;

class Rib
{
    const CONTROL_STATUS_LABEL = [
        GreenPointManager::NOT_VERIFIED                   => 'Non vérifié',
        GreenPointManager::OUT_OF_BOUNDS                  => 'Hors périmètre (pas un RIB)',
        GreenPointManager::FALSIFIED_OR_MINOR             => 'Falsifié',
        GreenPointManager::ILLEGIBLE                      => 'Illisible / coupé',
        GreenPointManager::VERSO_MISSING                  => 'Banque hors périmètre',
        GreenPointManager::NAME_SURNAME_INVERSION         => 'Non cohérent / données : inversion nom - prénom',
        GreenPointManager::INCOHERENT_OTHER_ERROR         => 'Non cohérent / données : autre erreur',
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
     * @JMS\SerializedName("iban")
     * @JMS\Type("string")
     */
    private $iban;

    /**
     * @JMS\SerializedName("bic")
     * @JMS\Type("string")
     */
    private $bic;


    /**
     * @JMS\SerializedName("url")
     * @JMS\Type("string")
     */
    private $url;

    /**
     * @return string|null
     */
    public function getStatusLabel(): ?string
    {
        return self::CONTROL_STATUS_LABEL[$this->status];
    }

    /**
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getIban(): ?string
    {
        return $this->iban;
    }

    /**
     * @return string|null
     */
    public function getBic(): ?string
    {
        return $this->bic;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }
}
