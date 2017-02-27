<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PaysV2
 *
 * @ORM\Table(name="pays_v2")
 * @ORM\Entity
 */
class PaysV2
{
    /**
     * @var string
     *
     * @ORM\Column(name="fr", type="string", length=191, nullable=true)
     */
    private $fr;

    /**
     * @var string
     *
     * @ORM\Column(name="iso", type="string", length=2, nullable=false)
     */
    private $iso;

    /**
     * @var integer
     *
     * @ORM\Column(name="ordre", type="integer", nullable=false)
     */
    private $ordre;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_pays", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idPays;



    /**
     * Set fr
     *
     * @param string $fr
     *
     * @return PaysV2
     */
    public function setFr($fr)
    {
        $this->fr = $fr;

        return $this;
    }

    /**
     * Get fr
     *
     * @return string
     */
    public function getFr()
    {
        return $this->fr;
    }

    /**
     * Set iso
     *
     * @param string $iso
     *
     * @return PaysV2
     */
    public function setIso($iso)
    {
        $this->iso = $iso;

        return $this;
    }

    /**
     * Get iso
     *
     * @return string
     */
    public function getIso()
    {
        return $this->iso;
    }

    /**
     * Set ordre
     *
     * @param integer $ordre
     *
     * @return PaysV2
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
     * Get idPays
     *
     * @return integer
     */
    public function getIdPays()
    {
        return $this->idPays;
    }
}
