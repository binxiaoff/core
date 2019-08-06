<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="nationalites")
 * @ORM\Entity
 */
class Nationalites
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_pays", type="string", length=9, unique=true)
     */
    private $codePays = '';

    /**
     * @var string
     *
     * @ORM\Column(name="etat", type="string", length=52, nullable=true)
     */
    private $etat;

    /**
     * @var string
     *
     * @ORM\Column(name="fr_m", type="string", length=50, nullable=true)
     */
    private $frM;

    /**
     * @var string
     *
     * @ORM\Column(name="fr_f", type="string", length=50, nullable=true)
     */
    private $frF;

    /**
     * @var int
     *
     * @ORM\Column(name="id_nationalite", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idNationalite;

    /**
     * @param string $codePays
     *
     * @return Nationalites
     */
    public function setCodePays($codePays)
    {
        $this->codePays = $codePays;

        return $this;
    }

    /**
     * @return string
     */
    public function getCodePays()
    {
        return $this->codePays;
    }

    /**
     * @param string $etat
     *
     * @return Nationalites
     */
    public function setEtat($etat)
    {
        $this->etat = $etat;

        return $this;
    }

    /**
     * @return string
     */
    public function getEtat()
    {
        return $this->etat;
    }

    /**
     * @param string $frM
     *
     * @return Nationalites
     */
    public function setFrM($frM)
    {
        $this->frM = $frM;

        return $this;
    }

    /**
     * @return string
     */
    public function getFrM()
    {
        return $this->frM;
    }

    /**
     * @param string $frF
     *
     * @return Nationalites
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
     * @return int
     */
    public function getIdNationalite()
    {
        return $this->idNationalite;
    }
}
