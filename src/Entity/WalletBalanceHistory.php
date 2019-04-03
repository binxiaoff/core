<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WalletBalanceHistory
 *
 * @ORM\Table(name="wallet_balance_history", indexes={@ORM\Index(name="fk_id_wallet_idx", columns={"id_wallet"}), @ORM\Index(name="fk_id_operation_idx", columns={"id_operation"}), @ORM\Index(name="fk_id_bid_idx", columns={"id_bid"}), @ORM\Index(name="id_project", columns={"id_project"}), @ORM\Index(name="id_autobid", columns={"id_autobid"}), @ORM\Index(name="id_loan", columns={"id_loan"}), @ORM\Index(name="idx_wallet_balance_history_added", columns={"added"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Unilend\Repository\WalletBalanceHistoryRepository")
 */
class WalletBalanceHistory
{
    /**
     * @var string
     *
     * @ORM\Column(name="available_balance", type="decimal", precision=12, scale=2)
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
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wallet", referencedColumnName="id", nullable=false)
     * })
     */
    private $idWallet;

    /**
     * @var \Unilend\Entity\Operation
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Operation")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_operation", referencedColumnName="id")
     * })
     */
    private $idOperation;

    /**
     * @var \Unilend\Entity\Bids
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Bids")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_bid", referencedColumnName="id_bid")
     * })
     */
    private $idBid;

    /**
     * @var \Unilend\Entity\Loans
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Loans")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_loan", referencedColumnName="id_loan")
     * })
     */
    private $idLoan;

    /**
     * @var \Unilend\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project")
     * })
     */
    private $idProject;

    /**
     * @var \Unilend\Entity\Autobid
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Autobid")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_autobid", referencedColumnName="id_autobid")
     * })
     */
    private $idAutobid;


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
     * @param \Unilend\Entity\Wallet $idWallet
     *
     * @return WalletBalanceHistory
     */
    public function setIdWallet(\Unilend\Entity\Wallet $idWallet = null)
    {
        $this->idWallet = $idWallet;

        return $this;
    }

    /**
     * Get idWallet
     *
     * @return \Unilend\Entity\Wallet
     */
    public function getIdWallet()
    {
        return $this->idWallet;
    }

    /**
     * Set idOperation
     *
     * @param \Unilend\Entity\Operation $idOperation
     *
     * @return WalletBalanceHistory
     */
    public function setIdOperation(\Unilend\Entity\Operation $idOperation = null)
    {
        $this->idOperation = $idOperation;

        return $this;
    }

    /**
     * Get idOperation
     *
     * @return \Unilend\Entity\Operation
     */
    public function getIdOperation()
    {
        return $this->idOperation;
    }


    /**
     * Set idBid
     *
     * @param \Unilend\Entity\Bids $idBid
     *
     * @return WalletBalanceHistory
     */
    public function setBid(\Unilend\Entity\Bids $idBid = null)
    {
        $this->idBid = $idBid;

        return $this;
    }

    /**
     * Get idBid
     *
     * @return \Unilend\Entity\Bids
     */
    public function getBid()
    {
        return $this->idBid;
    }

    /**
     * Set Loan
     *
     * @param \Unilend\Entity\Loans $idLoan
     *
     * @return WalletBalanceHistory
     */
    public function setLoan(Loans $idLoan = null)
    {
        $this->idLoan = $idLoan;

        return $this;
    }

    /**
     * Get Loan
     *
     * @return \Unilend\Entity\Loans
     */
    public function getLoan()
    {
        return $this->idLoan;
    }

    /**
     * Set Project
     *
     * @param Projects $project
     *
     * @return WalletBalanceHistory
     */
    public function setProject(Projects $project = null)
    {
        $this->idProject = $project;

        return $this;
    }

    /**
     * Get Project
     *
     * @return Projects
     */
    public function getProject()
    {
        return $this->idProject;
    }

    /**
     * Set Autobid
     *
     * @param Autobid $autobid
     *
     * @return WalletBalanceHistory
     */
    public function setAutobid(Autobid $autobid = null)
    {
        $this->idAutobid = $autobid;

        return $this;
    }

    /**
     * Get Autobid
     *
     * @return Autobid
     */
    public function getAutobid()
    {
        return $this->idAutobid;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }
}
