<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DebtCollectionFeeDetail
 *
 * @ORM\Table(name="debt_collection_fee_detail", indexes={
 *     @ORM\Index(name="idx_debt_collection_fee_detail_id_wallet_creditor", columns={"id_wallet_creditor"}),
 *     @ORM\Index(name="idx_debt_collection_fee_detail_id_loan", columns={"id_loan"}),
 *     @ORM\Index(name="idx_debt_collection_fee_detail_wire_transfer_in_debtor_status", columns={"id_wire_transfer_in", "id_wallet_debtor", "status"}),
 *     @ORM\Index(name="idx_debt_collection_fee_detail_id_wallet_debtor", columns={"id_wallet_debtor"}),
 *     @ORM\Index(name="idx_debt_collection_fee_detail_id_debt_collection_mission", columns={"id_debt_collection_mission"})
 * })
 * @ORM\Entity(repositoryClass="Unilend\Repository\DebtCollectionFeeDetailRepository")
 * @ORM\HasLifecycleCallbacks
 */
class DebtCollectionFeeDetail
{
    const TYPE_LOAN                 = 1;
    const TYPE_PROJECT_CHARGE       = 2;
    const TYPE_REPAYMENT_COMMISSION = 3;

    const STATUS_PENDING = 0;
    const STATUS_TREATED = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="id_type", type="smallint")
     */
    private $idType;

    /**
     * @var string
     *
     * @ORM\Column(name="amount_tax_incl", type="decimal", precision=10, scale=2)
     */
    private $amountTaxIncl;

    /**
     * @var string
     *
     * @ORM\Column(name="vat", type="decimal", precision=10, scale=2)
     */
    private $vat;

    /**
     * @var string
     *
     * @ORM\Column(name="applied_fee_rate", type="decimal", precision=4, scale=4)
     */
    private $appliedFeeRate;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Entity\Receptions
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Receptions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wire_transfer_in", referencedColumnName="id_reception", nullable=false)
     * })
     */
    private $idWireTransferIn;

    /**
     * @var \Unilend\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wallet_debtor", referencedColumnName="id", nullable=false)
     * })
     */
    private $idWalletDebtor;

    /**
     * @var \Unilend\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wallet_creditor", referencedColumnName="id", nullable=false)
     * })
     */
    private $idWalletCreditor;

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
     * @var DebtCollectionMission
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\DebtCollectionMission")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_debt_collection_mission", referencedColumnName="id", nullable=false)
     * })
     */
    private $idDebtCollectionMission;

    /**
     * Set idType
     *
     * @param integer $idType
     *
     * @return DebtCollectionFeeDetail
     */
    public function setIdType($idType)
    {
        $this->idType = $idType;

        return $this;
    }

    /**
     * Get idType
     *
     * @return integer
     */
    public function getIdType()
    {
        return $this->idType;
    }

    /**
     * Set amountTaxIncl
     *
     * @param string $amountTaxIncl
     *
     * @return DebtCollectionFeeDetail
     */
    public function setAmountTaxIncl($amountTaxIncl)
    {
        $this->amountTaxIncl = $amountTaxIncl;

        return $this;
    }

    /**
     * Get amountTaxIncl
     *
     * @return string
     */
    public function getAmountTaxIncl()
    {
        return $this->amountTaxIncl;
    }

    /**
     * Set vat
     *
     * @param string $vat
     *
     * @return DebtCollectionFeeDetail
     */
    public function setVat($vat)
    {
        $this->vat = $vat;

        return $this;
    }

    /**
     * Get vat
     *
     * @return string
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return DebtCollectionFeeDetail
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return DebtCollectionFeeDetail
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
     * @return DebtCollectionFeeDetail
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
     * Set idWireTransferIn
     *
     * @param Receptions $idWireTransferIn
     *
     * @return DebtCollectionFeeDetail
     */
    public function setIdWireTransferIn(Receptions $idWireTransferIn)
    {
        $this->idWireTransferIn = $idWireTransferIn;

        return $this;
    }

    /**
     * Get idWireTransferIn
     *
     * @return Receptions
     */
    public function getIdWireTransferIn()
    {
        return $this->idWireTransferIn;
    }

    /**
     * Set idWalletDebtor
     *
     * @param Wallet $idWalletDebtor
     *
     * @return DebtCollectionFeeDetail
     */
    public function setIdWalletDebtor(Wallet $idWalletDebtor)
    {
        $this->idWalletDebtor = $idWalletDebtor;

        return $this;
    }

    /**
     * Get idWalletDebtor
     *
     * @return Wallet
     */
    public function getIdWalletDebtor()
    {
        return $this->idWalletDebtor;
    }

    /**
     * Set idWalletCreditor
     *
     * @param Wallet $idWalletCreditor
     *
     * @return DebtCollectionFeeDetail
     */
    public function setIdWalletCreditor(Wallet $idWalletCreditor)
    {
        $this->idWalletCreditor = $idWalletCreditor;

        return $this;
    }

    /**
     * Get idWalletCreditor
     *
     * @return Wallet
     */
    public function getIdWalletCreditor()
    {
        return $this->idWalletCreditor;
    }

    /**
     * Set idLoan
     *
     * @param Loans $idLoan
     *
     * @return DebtCollectionFeeDetail
     */
    public function setIdLoan(Loans $idLoan = null)
    {
        $this->idLoan = $idLoan;

        return $this;
    }

    /**
     * Get idLoan
     *
     * @return Loans
     */
    public function getIdLoan()
    {
        return $this->idLoan;
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

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }

    /**
     * Set idDebtCollectionMission
     *
     * @param DebtCollectionMission $idDebtCollectionMission
     *
     * @return DebtCollectionFeeDetail
     */
    public function setIdDebtCollectionMission(DebtCollectionMission $idDebtCollectionMission)
    {
        $this->idDebtCollectionMission = $idDebtCollectionMission;

        return $this;
    }

    /**
     * Get idDebtCollectionMission
     *
     * @return DebtCollectionMission
     */
    public function getIdDebtCollectionMission()
    {
        return $this->idDebtCollectionMission;
    }

    /**
     * @return string
     */
    public function getAppliedFeeRate()
    {
        return $this->appliedFeeRate;
    }

    /**
     * @param string $appliedFeeRate
     *
     * @return DebtCollectionFeeDetail
     */
    public function setAppliedFeeRate($appliedFeeRate)
    {
        $this->appliedFeeRate = $appliedFeeRate;

        return $this;
    }
}
