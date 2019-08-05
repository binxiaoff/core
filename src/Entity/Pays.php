<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pays.
 *
 * @ORM\Table(name="pays")
 * @ORM\Entity
 */
class Pays
{
    public const VIGILANCE_STATUS_LOW_RISK    = 0;
    public const VIGILANCE_STATUS_MEDIUM_RISK = 1;
    public const VIGILANCE_STATUS_HIGH_RISK   = 2;

    public const COUNTRY_FRANCE  = 1;
    public const COUNTRY_USA     = 62;
    public const COUNTRY_ERITREA = 59;

    public const FRANCE_DOM_TOM = [155, 195, 196, 197, 198];

    /** Name is misleading, it is a list fo EU countries, excluding France, to be confirmed https://unilend.atlassian.net/browse/TSK-147 */
    public const EUROPEAN_ECONOMIC_AREA = [6, 14, 21, 31, 41, 50, 52, 60, 61, 65, 70, 79, 84, 87, 98, 103, 104, 111, 139, 142, 143, 148, 150, 151, 165, 166, 171];

    /** Countries for which we allow lender IBANs */
    public const EEA_COUNTRIES_ISO = ['FR', 'DE', 'AT', 'BE', 'BG', 'CY', 'HR', 'DK', 'ES', 'EE', 'FI', 'GR', 'HU', 'IE', 'IS', 'IT', 'LV', 'LI', 'LT', 'LU', 'MT', 'NO', 'NL', 'PL', 'PT', 'CZ', 'RO', 'GB', 'SK', 'SI', 'SE'];

    /**
     * @var string
     *
     * @ORM\Column(name="fr", type="string", length=191, nullable=true)
     */
    private $fr;

    /**
     * @var string
     *
     * @ORM\Column(name="iso", type="string", length=2)
     */
    private $iso;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre", type="integer")
     */
    private $ordre;

    /**
     * @var int
     *
     * @ORM\Column(name="vigilance_status", type="smallint")
     */
    private $vigilanceStatus;

    /**
     * @var int
     *
     * @ORM\Column(name="id_pays", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idPays;

    /**
     * Set fr.
     *
     * @param string|null $fr
     *
     * @return Pays
     */
    public function setFr(?string $fr): Pays
    {
        $this->fr = $fr;

        return $this;
    }

    /**
     * Get fr.
     *
     * @return string|null
     */
    public function getFr(): ?string
    {
        return $this->fr;
    }

    /**
     * Set iso.
     *
     * @param string $iso
     *
     * @return Pays
     */
    public function setIso(string $iso): Pays
    {
        $this->iso = $iso;

        return $this;
    }

    /**
     * Get iso.
     *
     * @return string
     */
    public function getIso(): string
    {
        return $this->iso;
    }

    /**
     * Set ordre.
     *
     * @param int $ordre
     *
     * @return Pays
     */
    public function setOrdre(int $ordre): Pays
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * Get ordre.
     *
     * @return int
     */
    public function getOrdre(): int
    {
        return $this->ordre;
    }

    /**
     * @param int $vigilanceStatus
     *
     * @return Pays
     */
    public function setVigilanceStatus(int $vigilanceStatus): Pays
    {
        $this->vigilanceStatus = $vigilanceStatus;

        return $this;
    }

    /**
     * @return int
     */
    public function getVigilanceStatus(): int
    {
        return $this->vigilanceStatus;
    }

    /**
     * Get idPays.
     *
     * @return int
     */
    public function getIdPays(): int
    {
        return $this->idPays;
    }
}
