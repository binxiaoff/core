<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InseePays
 *
 * @ORM\Table(name="insee_pays", indexes={@ORM\Index(name="COG", columns={"COG"}), @ORM\Index(name="LIBCOG", columns={"LIBCOG"}), @ORM\Index(name="codeiso2", columns={"CODEISO2"})})
 * @ORM\Entity
 */
class InseePays
{
    /**
     * @var string
     *
     * @ORM\Column(name="CODEISO2", type="string", length=2, nullable=false)
     */
    private $codeiso2;

    /**
     * @var string
     *
     * @ORM\Column(name="COG", type="string", length=5, nullable=true)
     */
    private $cog;

    /**
     * @var integer
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
     * @var integer
     *
     * @ORM\Column(name="CRPAY", type="integer", nullable=true)
     */
    private $crpay;

    /**
     * @var integer
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
     * @var integer
     *
     * @ORM\Column(name="id_insee_pays", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idInseePays;



    /**
     * Set codeiso2
     *
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
     * Get codeiso2
     *
     * @return string
     */
    public function getCodeiso2()
    {
        return $this->codeiso2;
    }

    /**
     * Set cog
     *
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
     * Get cog
     *
     * @return string
     */
    public function getCog()
    {
        return $this->cog;
    }

    /**
     * Set actual
     *
     * @param integer $actual
     *
     * @return InseePays
     */
    public function setActual($actual)
    {
        $this->actual = $actual;

        return $this;
    }

    /**
     * Get actual
     *
     * @return integer
     */
    public function getActual()
    {
        return $this->actual;
    }

    /**
     * Set capay
     *
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
     * Get capay
     *
     * @return string
     */
    public function getCapay()
    {
        return $this->capay;
    }

    /**
     * Set crpay
     *
     * @param integer $crpay
     *
     * @return InseePays
     */
    public function setCrpay($crpay)
    {
        $this->crpay = $crpay;

        return $this;
    }

    /**
     * Get crpay
     *
     * @return integer
     */
    public function getCrpay()
    {
        return $this->crpay;
    }

    /**
     * Set ani
     *
     * @param integer $ani
     *
     * @return InseePays
     */
    public function setAni($ani)
    {
        $this->ani = $ani;

        return $this;
    }

    /**
     * Get ani
     *
     * @return integer
     */
    public function getAni()
    {
        return $this->ani;
    }

    /**
     * Set libcog
     *
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
     * Get libcog
     *
     * @return string
     */
    public function getLibcog()
    {
        return $this->libcog;
    }

    /**
     * Set libenr
     *
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
     * Get libenr
     *
     * @return string
     */
    public function getLibenr()
    {
        return $this->libenr;
    }

    /**
     * Set ancnom
     *
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
     * Get ancnom
     *
     * @return string
     */
    public function getAncnom()
    {
        return $this->ancnom;
    }

    /**
     * Get idInseePays
     *
     * @return integer
     */
    public function getIdInseePays()
    {
        return $this->idInseePays;
    }
}
