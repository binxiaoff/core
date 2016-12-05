<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WalletBalanceHistory
 *
 * @ORM\Table(name="wallet_balance_history", indexes={@ORM\Index(name="fk_id_wallet_idx", columns={"id_wallet"}), @ORM\Index(name="fk_id_operation_idx", columns={"id_operation"})})
 * @ORM\Entity
 */
class WalletBalanceHistory
{
    /**
     * @var string
     *
     * @ORM\Column(name="balance", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $balance;

    /**
     * @var string
     *
     * @ORM\Column(name="engaged_balance", type="decimal", precision=12, scale=2, nullable=true)
     */
    private $engagedBalance;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Operation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Operation")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_operation", referencedColumnName="id")
     * })
     */
    private $idOperation;



    /**
     * Set balance
     *
     * @param string $balance
     *
     * @return WalletBalanceHistory
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Get balance
     *
     * @return string
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * Set engagedBalance
     *
     * @param string $engagedBalance
     *
     * @return WalletBalanceHistory
     */
    public function setEngagedBalance($engagedBalance)
    {
        $this->engagedBalance = $engagedBalance;

        return $this;
    }

    /**
     * Get engagedBalance
     *
     * @return string
     */
    public function getEngagedBalance()
    {
        return $this->engagedBalance;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return WalletBalanceHistory
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set idWallet
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWallet
     *
     * @return WalletBalanceHistory
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
     * Set idOperation
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Operation $idOperation
     *
     * @return WalletBalanceHistory
     */
    public function setIdOperation(\Unilend\Bundle\CoreBusinessBundle\Entity\Operation $idOperation = null)
    {
        $this->idOperation = $idOperation;

        return $this;
    }

    /**
     * Get idOperation
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Operation
     */
    public function getIdOperation()
    {
        return $this->idOperation;
    }
}
