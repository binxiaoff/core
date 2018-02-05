<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LenderTaxExemption
 *
 * @ORM\Table(name="lender_tax_exemption", uniqueConstraints={@ORM\UniqueConstraint(name="id_lender_year", columns={"id_lender", "year"})}, indexes={@ORM\Index(name="id_lender", columns={"id_lender"}), @ORM\Index(name="iso_country", columns={"iso_country"}), @ORM\Index(name="year", columns={"year"})})
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\LenderTaxExemptionRepository")
 */
class LenderTaxExemption
{
    /**
     * @var string
     *
     * @ORM\Column(name="iso_country", type="string", length=2, nullable=false)
     */
    private $isoCountry;

    /**
     * @var integer
     *
     * @ORM\Column(name="year", type="integer", nullable=false)
     */
    private $year;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Users")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="id_user", referencedColumnName="id_user")
     * })
     */
    private $idUser;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_lender_tax_exemption", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLenderTaxExemption;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender", referencedColumnName="id")
     * })
     */
    private $idLender;

    /**
     * Set isoCountry
     *
     * @param string $isoCountry
     *
     * @return LenderTaxExemption
     */
    public function setIsoCountry($isoCountry)
    {
        $this->isoCountry = $isoCountry;

        return $this;
    }

    /**
     * Get isoCountry
     *
     * @return string
     */
    public function getIsoCountry()
    {
        return $this->isoCountry;
    }

    /**
     * Set year
     *
     * @param integer $year
     *
     * @return LenderTaxExemption
     */
    public function setYear($year)
    {
        $this->year = $year;

        return $this;
    }

    /**
     * Get year
     *
     * @return integer
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set idUser
     *
     * @param Users $idUser
     *
     * @return LenderTaxExemption
     */
    public function setIdUser(Users $idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return Users
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return LenderTaxExemption
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return LenderTaxExemption
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get idLenderTaxExemption
     *
     * @return integer
     */
    public function getIdLenderTaxExemption()
    {
        return $this->idLenderTaxExemption;
    }

    /**
     * Set idLender
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idLender
     *
     * @return LenderTaxExemption
     */
    public function setIdLender(Wallet $idLender)
    {
        $this->idLender = $idLender;

        return $this;
    }

    /**
     * Get idLender
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     */
    public function getIdLender()
    {
        return $this->idLender;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue() : void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue() : void
    {
        $this->updated = new \DateTime();
    }
}
