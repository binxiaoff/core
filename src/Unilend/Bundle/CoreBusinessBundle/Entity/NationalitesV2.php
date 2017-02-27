<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * NationalitesV2
 *
 * @ORM\Table(name="nationalites_v2")
 * @ORM\Entity
 */
class NationalitesV2
{
    /**
     * @var string
     *
     * @ORM\Column(name="fr_f", type="string", length=191, nullable=true)
     */
    private $frF;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordre", type="integer", nullable=false)
     */
    private $ordre;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_nationalite", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idNationalite;



    /**
     * Set frF
     *
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
     * Get frF
     *
     * @return string
     */
    public function getFrF()
    {
        return $this->frF;
    }

    /**
     * Set ordre
     *
     * @param integer $ordre
     *
     * @return NationalitesV2
     */
    public function setOrdre($ordre)
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * Get ordre
     *
     * @return integer
     */
    public function getOrdre()
    {
        return $this->ordre;
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
