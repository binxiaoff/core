<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Insee.
 *
 * @ORM\Table(name="insee", indexes={@ORM\Index(name="NCCENR", columns={"NCCENR"}), @ORM\Index(name="NCC", columns={"NCC"})})
 * @ORM\Entity
 */
class Insee
{
    /**
     * @var int
     *
     * @ORM\Column(name="CDC", type="smallint", nullable=true)
     */
    private $cdc;

    /**
     * @var int
     *
     * @ORM\Column(name="CHEFLIEU", type="smallint", nullable=true)
     */
    private $cheflieu;

    /**
     * @var int
     *
     * @ORM\Column(name="REG", type="smallint", nullable=true)
     */
    private $reg;

    /**
     * @var int
     *
     * @ORM\Column(name="DEP", type="smallint", nullable=true)
     */
    private $dep;

    /**
     * @var int
     *
     * @ORM\Column(name="COM", type="smallint", nullable=true)
     */
    private $com;

    /**
     * @var int
     *
     * @ORM\Column(name="AR", type="smallint", nullable=true)
     */
    private $ar;

    /**
     * @var int
     *
     * @ORM\Column(name="CT", type="smallint", nullable=true)
     */
    private $ct;

    /**
     * @var int
     *
     * @ORM\Column(name="TNCC", type="smallint", nullable=true)
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
     * @var int
     *
     * @ORM\Column(name="id_insee", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idInsee;

    /**
     * Set cdc.
     *
     * @param int $cdc
     *
     * @return Insee
     */
    public function setCdc($cdc)
    {
        $this->cdc = $cdc;

        return $this;
    }

    /**
     * Get cdc.
     *
     * @return int
     */
    public function getCdc()
    {
        return $this->cdc;
    }

    /**
     * Set cheflieu.
     *
     * @param int $cheflieu
     *
     * @return Insee
     */
    public function setCheflieu($cheflieu)
    {
        $this->cheflieu = $cheflieu;

        return $this;
    }

    /**
     * Get cheflieu.
     *
     * @return int
     */
    public function getCheflieu()
    {
        return $this->cheflieu;
    }

    /**
     * Set reg.
     *
     * @param int $reg
     *
     * @return Insee
     */
    public function setReg($reg)
    {
        $this->reg = $reg;

        return $this;
    }

    /**
     * Get reg.
     *
     * @return int
     */
    public function getReg()
    {
        return $this->reg;
    }

    /**
     * Set dep.
     *
     * @param int $dep
     *
     * @return Insee
     */
    public function setDep($dep)
    {
        $this->dep = $dep;

        return $this;
    }

    /**
     * Get dep.
     *
     * @return int
     */
    public function getDep()
    {
        return $this->dep;
    }

    /**
     * Set com.
     *
     * @param int $com
     *
     * @return Insee
     */
    public function setCom($com)
    {
        $this->com = $com;

        return $this;
    }

    /**
     * Get com.
     *
     * @return int
     */
    public function getCom()
    {
        return $this->com;
    }

    /**
     * Set ar.
     *
     * @param int $ar
     *
     * @return Insee
     */
    public function setAr($ar)
    {
        $this->ar = $ar;

        return $this;
    }

    /**
     * Get ar.
     *
     * @return int
     */
    public function getAr()
    {
        return $this->ar;
    }

    /**
     * Set ct.
     *
     * @param int $ct
     *
     * @return Insee
     */
    public function setCt($ct)
    {
        $this->ct = $ct;

        return $this;
    }

    /**
     * Get ct.
     *
     * @return int
     */
    public function getCt()
    {
        return $this->ct;
    }

    /**
     * Set tncc.
     *
     * @param int $tncc
     *
     * @return Insee
     */
    public function setTncc($tncc)
    {
        $this->tncc = $tncc;

        return $this;
    }

    /**
     * Get tncc.
     *
     * @return int
     */
    public function getTncc()
    {
        return $this->tncc;
    }

    /**
     * Set artmaj.
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
     * Get artmaj.
     *
     * @return string
     */
    public function getArtmaj()
    {
        return $this->artmaj;
    }

    /**
     * Set ncc.
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
     * Get ncc.
     *
     * @return string
     */
    public function getNcc()
    {
        return $this->ncc;
    }

    /**
     * Set artmin.
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
     * Get artmin.
     *
     * @return string
     */
    public function getArtmin()
    {
        return $this->artmin;
    }

    /**
     * Set nccenr.
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
     * Get nccenr.
     *
     * @return string
     */
    public function getNccenr()
    {
        return $this->nccenr;
    }

    /**
     * Get idInsee.
     *
     * @return int
     */
    public function getIdInsee()
    {
        return $this->idInsee;
    }
}
