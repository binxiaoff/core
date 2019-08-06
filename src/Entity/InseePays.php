<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="insee_pays", indexes={@ORM\Index(name="COG", columns={"COG"}), @ORM\Index(name="LIBCOG", columns={"LIBCOG"}), @ORM\Index(name="codeiso2", columns={"CODEISO2"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\InseePaysRepository")
 */
class InseePays
{
    /**
     * @var string
     *
     * @ORM\Column(name="CODEISO2", type="string", length=2)
     */
    private $codeiso2;

    /**
     * @var string
     *
     * @ORM\Column(name="COG", type="string", length=5, nullable=true)
     */
    private $cog;

    /**
     * @var int
     *
     * @ORM\Column(name="ACTUAL", type="integer", nullable=true)
     */
    private $actual;

    /**
     * @var string
     *
     * @ORM\Column(name="CAPAY", type="string", length=5, nullable=true)
     */
    private $capay;

    /**
     * @var int
     *
     * @ORM\Column(name="CRPAY", type="integer", nullable=true)
     */
    private $crpay;

    /**
     * @var int
     *
     * @ORM\Column(name="ANI", type="integer", nullable=true)
     */
    private $ani;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBCOG", type="string", length=44, nullable=true)
     */
    private $libcog;

    /**
     * @var string
     *
     * @ORM\Column(name="LIBENR", type="string", length=54, nullable=true)
     */
    private $libenr;

    /**
     * @var string
     *
     * @ORM\Column(name="ANCNOM", type="string", length=20, nullable=true)
     */
    private $ancnom;

    /**
     * @var int
     *
     * @ORM\Column(name="id_insee_pays", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idInseePays;

    /**
     * @param string $codeiso2
     *
     * @return InseePays
     */
    public function setCodeiso2($codeiso2)
    {
        $this->codeiso2 = $codeiso2;

        return $this;
    }

    /**
     * @return string
     */
    public function getCodeiso2()
    {
        return $this->codeiso2;
    }

    /**
     * @param string $cog
     *
     * @return InseePays
     */
    public function setCog($cog)
    {
        $this->cog = $cog;

        return $this;
    }

    /**
     * @return string
     */
    public function getCog()
    {
        return $this->cog;
    }

    /**
     * @param int $actual
     *
     * @return InseePays
     */
    public function setActual($actual)
    {
        $this->actual = $actual;

        return $this;
    }

    /**
     * @return int
     */
    public function getActual()
    {
        return $this->actual;
    }

    /**
     * @param string $capay
     *
     * @return InseePays
     */
    public function setCapay($capay)
    {
        $this->capay = $capay;

        return $this;
    }

    /**
     * @return string
     */
    public function getCapay()
    {
        return $this->capay;
    }

    /**
     * @param int $crpay
     *
     * @return InseePays
     */
    public function setCrpay($crpay)
    {
        $this->crpay = $crpay;

        return $this;
    }

    /**
     * @return int
     */
    public function getCrpay()
    {
        return $this->crpay;
    }

    /**
     * @param int $ani
     *
     * @return InseePays
     */
    public function setAni($ani)
    {
        $this->ani = $ani;

        return $this;
    }

    /**
     * @return int
     */
    public function getAni()
    {
        return $this->ani;
    }

    /**
     * @param string $libcog
     *
     * @return InseePays
     */
    public function setLibcog($libcog)
    {
        $this->libcog = $libcog;

        return $this;
    }

    /**
     * @return string
     */
    public function getLibcog()
    {
        return $this->libcog;
    }

    /**
     * @param string $libenr
     *
     * @return InseePays
     */
    public function setLibenr($libenr)
    {
        $this->libenr = $libenr;

        return $this;
    }

    /**
     * @return string
     */
    public function getLibenr()
    {
        return $this->libenr;
    }

    /**
     * @param string $ancnom
     *
     * @return InseePays
     */
    public function setAncnom($ancnom)
    {
        $this->ancnom = $ancnom;

        return $this;
    }

    /**
     * @return string
     */
    public function getAncnom()
    {
        return $this->ancnom;
    }

    /**
     * @return int
     */
    public function getIdInseePays()
    {
        return $this->idInseePays;
    }
}
