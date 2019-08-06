<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="nationalites_v2")
 * @ORM\Entity
 */
class NationalitesV2
{
    public const NATIONALITY_FRENCH = 1;
    public const NATIONALITY_OTHER  = 35;

    /**
     * @var string
     *
     * @ORM\Column(name="fr_f", type="string", length=191, nullable=true)
     */
    private $frF;

    /**
     * @var int
     *
     * @ORM\Column(name="ordre", type="integer")
     */
    private $ordre;

    /**
     * @var int
     *
     * @ORM\Column(name="id_nationalite", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idNationalite;

    /**
     * @param string $frF
     *
     * @return NationalitesV2
     */
    public function setFrF($frF)
    {
        $this->frF = $frF;

        return $this;
    }

    /**
     * @return string
     */
    public function getFrF()
    {
        return $this->frF;
    }

    /**
     * @param int $ordre
     *
     * @return NationalitesV2
     */
    public function setOrdre($ordre)
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrdre()
    {
        return $this->ordre;
    }

    /**
     * @return int
     */
    public function getIdNationalite()
    {
        return $this->idNationalite;
    }
}
