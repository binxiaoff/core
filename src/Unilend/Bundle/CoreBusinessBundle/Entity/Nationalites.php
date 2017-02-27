<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Nationalites
 *
 * @ORM\Table(name="nationalites", uniqueConstraints={@ORM\UniqueConstraint(name="code_pays", columns={"code_pays"})})
 * @ORM\Entity
 */
class Nationalites
{
    /**
     * @var string
     *
     * @ORM\Column(name="code_pays", type="string", length=9, nullable=false)
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
     * @var integer
     *
     * @ORM\Column(name="id_nationalite", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idNationalite;



    /**
     * Set codePays
     *
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
     * Get codePays
     *
     * @return string
     */
    public function getCodePays()
    {
        return $this->codePays;
    }

    /**
     * Set etat
     *
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
     * Get etat
     *
     * @return string
     */
    public function getEtat()
    {
        return $this->etat;
    }

    /**
     * Set frM
     *
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
     * Get frM
     *
     * @return string
     */
    public function getFrM()
    {
        return $this->frM;
    }

    /**
     * Set frF
     *
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
     * Get frF
     *
     * @return string
     */
    public function getFrF()
    {
        return $this->frF;
    }

    /**
     * Get idNationalite
     *
     * @return integer
     */
    public function getIdNationalite()
    {
        return $this->idNationalite;
    }
}
