<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Wallet
 *
 * @ORM\Table(name="wallet", indexes={@ORM\Index(name="fk_id_type_idx", columns={"id_type"}), @ORM\Index(name="idx_id_client", columns={"id_client"})})
 * @ORM\Entity
 */
class Wallet
{
    /**
     * @var string
     *
     * @ORM\Column(name="available_balance", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $availableBalance;

    /**
     * @var string
     *
     * @ORM\Column(name="committed_balance", type="decimal", precision=12, scale=2, nullable=true)
     */
    private $committedBalance;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\WalletType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\WalletType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type", referencedColumnName="id")
     * })
     */
    private $idType;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client")
     * })
     */
    private $idClient;



    /**
     * Set availableBalance
     *
     * @param string $availableBalance
     *
     * @return Wallet
     */
    public function setBalance($availableBalance)
    {
        $this->availableBalance = $availableBalance;

        return $this;
    }

    /**
     * Get availableBalance
     *
     * @return string
     */
    public function getAvailableBalance()
    {
        return $this->availableBalance;
    }

    /**
     * Set committedBalance
     *
     * @param string $committedBalance
     *
     * @return Wallet
     */
    public function setCommittedBalance($committedBalance)
    {
        $this->committedBalance = $committedBalance;

        return $this;
    }

    /**
     * Get committedBalance
     *
     * @return string
     */
    public function getCommittedBalance()
    {
        return $this->committedBalance;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Wallet
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
     * @return Wallet
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set idType
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\WalletType $idType
     *
     * @return Wallet
     */
    public function setIdType(\Unilend\Bundle\CoreBusinessBundle\Entity\WalletType $idType = null)
    {
        $this->idType = $idType;

        return $this;
    }

    /**
     * Get idType
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\WalletType
     */
    public function getIdType()
    {
        return $this->idType;
    }

    /**
     * Set idClient
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Clients $idClient
     *
     * @return Wallet
     */
    public function setIdClient(\Unilend\Bundle\CoreBusinessBundle\Entity\Clients $idClient = null)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     */
    public function getIdClient()
    {
        return $this->idClient;
    }
}
