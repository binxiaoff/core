<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WalletBalanceHistory
 *
 * @ORM\Table(name="wallet_balance_history", indexes={@ORM\Index(name="fk_id_wallet_idx", columns={"id_wallet"}), @ORM\Index(name="fk_id_operation_idx", columns={"id_operation"}), @ORM\Index(name="fk_id_bid_idx", columns={"id_bid"}), @ORM\Index(name="id_project", columns={"id_project"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class WalletBalanceHistory
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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Bids
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Bids")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_bid", referencedColumnName="id_bid")
     * })
     */
    private $idBid;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $idProject;


    /**
     * Set availableBalance
     *
     * @param string $availableBalance
     *
     * @return WalletBalanceHistory
     */
    public function setAvailableBalance($availableBalance)
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
     * @return WalletBalanceHistory
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


    /**
     * Set idBid
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Bids $idBid
     *
     * @return WalletBalanceHistory
     */
    public function setBid(\Unilend\Bundle\CoreBusinessBundle\Entity\Bids $idBid = null)
    {
        $this->idBid = $idBid;

        return $this;
    }

    /**
     * Get idBid
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Bids
     */
    public function getBid()
    {
        return $this->idBid;
    }

    /**
     * Set Project
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Projects $idProject
     *
     * @return WalletBalanceHistory
     */
    public function setProject(Projects $idProject = null)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get Project
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     */
    public function getProject()
    {
        return $this->idProject;
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
}
