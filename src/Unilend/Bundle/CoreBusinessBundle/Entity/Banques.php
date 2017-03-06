<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Banques
 *
 * @ORM\Table(name="banques", indexes={@ORM\Index(name="swift_code_banque", columns={"swift_code_banque"})})
 * @ORM\Entity
 */
class Banques
{
    /**
     * @var string
     *
     * @ORM\Column(name="nom_banque", type="string", length=191, nullable=true)
     */
    private $nomBanque;

    /**
     * @var string
     *
     * @ORM\Column(name="ville_banque", type="string", length=191, nullable=true)
     */
    private $villeBanque;

    /**
     * @var string
     *
     * @ORM\Column(name="swift_code_banque", type="string", length=50, nullable=true)
     */
    private $swiftCodeBanque;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_banque", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idBanque;



    /**
     * Set nomBanque
     *
     * @param string $nomBanque
     *
     * @return Banques
     */
    public function setNomBanque($nomBanque)
    {
        $this->nomBanque = $nomBanque;

        return $this;
    }

    /**
     * Get nomBanque
     *
     * @return string
     */
    public function getNomBanque()
    {
        return $this->nomBanque;
    }

    /**
     * Set villeBanque
     *
     * @param string $villeBanque
     *
     * @return Banques
     */
    public function setVilleBanque($villeBanque)
    {
        $this->villeBanque = $villeBanque;

        return $this;
    }

    /**
     * Get villeBanque
     *
     * @return string
     */
    public function getVilleBanque()
    {
        return $this->villeBanque;
    }

    /**
     * Set swiftCodeBanque
     *
     * @param string $swiftCodeBanque
     *
     * @return Banques
     */
    public function setSwiftCodeBanque($swiftCodeBanque)
    {
        $this->swiftCodeBanque = $swiftCodeBanque;

        return $this;
    }

    /**
     * Get swiftCodeBanque
     *
     * @return string
     */
    public function getSwiftCodeBanque()
    {
        return $this->swiftCodeBanque;
    }

    /**
     * Get idBanque
     *
     * @return integer
     */
    public function getIdBanque()
    {
        return $this->idBanque;
    }
}
