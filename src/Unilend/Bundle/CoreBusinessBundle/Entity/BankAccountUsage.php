<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BankAccountUsage
 *
 * @ORM\Table(name="bank_account_usage", indexes={@ORM\Index(name="fk_bank_account_usage_id_wallet_idx", columns={"id_wallet"}), @ORM\Index(name="fk_id_bank_account_idx", columns={"id_bank_account"}), @ORM\Index(name="fk_bank_account_usage_id_type_idx", columns={"id_usage_type"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class BankAccountUsage
{
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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_bank_account", referencedColumnName="id")
     * })
     */
    private $idBankAccount;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wallet", referencedColumnName="id")
     * })
     */
    private $idWallet;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\BankAccountUsageType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\BankAccountUsageType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_usage_type", referencedColumnName="id")
     * })
     */
    private $idUsageType;



    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return BankAccountUsage
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
     * @return BankAccountUsage
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
     * Set idBankAccount
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount $idBankAccount
     *
     * @return BankAccountUsage
     */
    public function setIdBankAccount(\Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount $idBankAccount = null)
    {
        $this->idBankAccount = $idBankAccount;

        return $this;
    }

    /**
     * Get idBankAccount
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount
     */
    public function getIdBankAccount()
    {
        return $this->idBankAccount;
    }

    /**
     * Set idWallet
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWallet
     *
     * @return BankAccountUsage
     */
    public function setIdWallet(\Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWallet = null)
    {
        $this->idWallet = $idWallet;

        return $this;
    }

    /**
     * Get idWallet
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     */
    public function getIdWallet()
    {
        return $this->idWallet;
    }

    /**
     * Set idUsageType
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\BankAccountUsageType $idUsageType
     *
     * @return BankAccountUsage
     */
    public function setIdUsageType(\Unilend\Bundle\CoreBusinessBundle\Entity\BankAccountUsageType $idUsageType = null)
    {
        $this->idUsageType = $idUsageType;

        return $this;
    }

    /**
     * Get idUsageType
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\BankAccountUsageType
     */
    public function getIdUsageType()
    {
        return $this->idUsageType;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if(! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
