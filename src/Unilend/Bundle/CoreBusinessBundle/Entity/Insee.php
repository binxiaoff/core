<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Insee
 *
 * @ORM\Table(name="insee", indexes={@ORM\Index(name="NCCENR", columns={"NCCENR"}), @ORM\Index(name="NCC", columns={"NCC"})})
 * @ORM\Entity
 */
class Insee
{
    /**
     * @var integer
     *
     * @ORM\Column(name="CDC", type="integer", nullable=true)
     */
    private $cdc;

    /**
     * @var integer
     *
     * @ORM\Column(name="CHEFLIEU", type="integer", nullable=true)
     */
    private $cheflieu;

    /**
     * @var integer
     *
     * @ORM\Column(name="REG", type="integer", nullable=true)
     */
    private $reg;

    /**
     * @var integer
     *
     * @ORM\Column(name="DEP", type="integer", nullable=true)
     */
    private $dep;

    /**
     * @var integer
     *
     * @ORM\Column(name="COM", type="integer", nullable=true)
     */
    private $com;

    /**
     * @var integer
     *
     * @ORM\Column(name="AR", type="integer", nullable=true)
     */
    private $ar;

    /**
     * @var integer
     *
     * @ORM\Column(name="CT", type="integer", nullable=true)
     */
    private $ct;

    /**
     * @var integer
     *
     * @ORM\Column(name="TNCC", type="integer", nullable=true)
     */
    private $tncc;

    /**
     * @var string
     *
     * @ORM\Column(name="ARTMAJ", type="string", length=4, nullable=true)
     */
    private $artmaj;

    /**
     * @var string
     *
     * @ORM\Column(name="NCC", type="string", length=50, nullable=true)
     */
    private $ncc;

    /**
     * @var string
     *
     * @ORM\Column(name="ARTMIN", type="string", length=4, nullable=true)
     */
    private $artmin;

    /**
     * @var string
     *
     * @ORM\Column(name="NCCENR", type="string", length=50, nullable=true)
     */
    private $nccenr;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_insee", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idInsee;



    /**
     * Set cdc
     *
     * @param integer $cdc
     *
     * @return Insee
     */
    public function setCdc($cdc)
    {
        $this->cdc = $cdc;

        return $this;
    }

    /**
     * Get cdc
     *
     * @return integer
     */
    public function getCdc()
    {
        return $this->cdc;
    }

    /**
     * Set cheflieu
     *
     * @param integer $cheflieu
     *
     * @return Insee
     */
    public function setCheflieu($cheflieu)
    {
        $this->cheflieu = $cheflieu;

        return $this;
    }

    /**
     * Get cheflieu
     *
     * @return integer
     */
    public function getCheflieu()
    {
        return $this->cheflieu;
    }

    /**
     * Set reg
     *
     * @param integer $reg
     *
     * @return Insee
     */
    public function setReg($reg)
    {
        $this->reg = $reg;

        return $this;
    }

    /**
     * Get reg
     *
     * @return integer
     */
    public function getReg()
    {
        return $this->reg;
    }

    /**
     * Set dep
     *
     * @param integer $dep
     *
     * @return Insee
     */
    public function setDep($dep)
    {
        $this->dep = $dep;

        return $this;
    }

    /**
     * Get dep
     *
     * @return integer
     */
    public function getDep()
    {
        return $this->dep;
    }

    /**
     * Set com
     *
     * @param integer $com
     *
     * @return Insee
     */
    public function setCom($com)
    {
        $this->com = $com;

        return $this;
    }

    /**
     * Get com
     *
     * @return integer
     */
    public function getCom()
    {
        return $this->com;
    }

    /**
     * Set ar
     *
     * @param integer $ar
     *
     * @return Insee
     */
    public function setAr($ar)
    {
        $this->ar = $ar;

        return $this;
    }

    /**
     * Get ar
     *
     * @return integer
     */
    public function getAr()
    {
        return $this->ar;
    }

    /**
     * Set ct
     *
     * @param integer $ct
     *
     * @return Insee
     */
    public function setCt($ct)
    {
        $this->ct = $ct;

        return $this;
    }

    /**
     * Get ct
     *
     * @return integer
     */
    public function getCt()
    {
        return $this->ct;
    }

    /**
     * Set tncc
     *
     * @param integer $tncc
     *
     * @return Insee
     */
    public function setTncc($tncc)
    {
        $this->tncc = $tncc;

        return $this;
    }

    /**
     * Get tncc
     *
     * @return integer
     */
    public function getTncc()
    {
        return $this->tncc;
    }

    /**
     * Set artmaj
     *
     * @param string $artmaj
     *
     * @return Insee
     */
    public function setArtmaj($artmaj)
    {
        $this->artmaj = $artmaj;

        return $this;
    }

    /**
     * Get artmaj
     *
     * @return string
     */
    public function getArtmaj()
    {
        return $this->artmaj;
    }

    /**
     * Set ncc
     *
     * @param string $ncc
     *
     * @return Insee
     */
    public function setNcc($ncc)
    {
        $this->ncc = $ncc;

        return $this;
    }

    /**
     * Get ncc
     *
     * @return string
     */
    public function getNcc()
    {
        return $this->ncc;
    }

    /**
     * Set artmin
     *
     * @param string $artmin
     *
     * @return Insee
     */
    public function setArtmin($artmin)
    {
        $this->artmin = $artmin;

        return $this;
    }

    /**
     * Get artmin
     *
     * @return string
     */
    public function getArtmin()
    {
        return $this->artmin;
    }

    /**
     * Set nccenr
     *
     * @param string $nccenr
     *
     * @return Insee
     */
    public function setNccenr($nccenr)
    {
        $this->nccenr = $nccenr;

        return $this;
    }

    /**
     * Get nccenr
     *
     * @return string
     */
    public function getNccenr()
    {
        return $this->nccenr;
    }

    /**
     * Get idInsee
     *
     * @return integer
     */
    public function getIdInsee()
    {
        return $this->idInsee;
    }
}
