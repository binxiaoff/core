<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Operation
 *
 * @ORM\Table(name="operation", indexes={
 *     @ORM\Index(name="fk_operation_id_type_idx", columns={"id_type"}),
 *     @ORM\Index(name="fk_id_project_idx", columns={"id_project"}),
 *     @ORM\Index(name="fk_id_loan_idx", columns={"id_loan"}),
 *     @ORM\Index(name="fk_id_payment_schedule_idx", columns={"id_payment_schedule"}),
 *     @ORM\Index(name="fk_id_repayment_schedule_idx", columns={"id_repayment_schedule"}),
 *     @ORM\Index(name="fk_id_backpayline_idx", columns={"id_backpayline"}),
 *     @ORM\Index(name="fk_id_welcome_offer_idx", columns={"id_welcome_offer"}),
 *     @ORM\Index(name="fk_id_wire_transfer_out_idx", columns={"id_wire_transfer_out"}),
 *     @ORM\Index(name="fk_id_wire_transfer_in_idx", columns={"id_wire_transfer_in"}),
 *     @ORM\Index(name="fk_id_transfer_idx", columns={"id_transfer"}),
 *     @ORM\Index(name="idx_id_wallet_debitor_type", columns={"id_wallet_debtor", "id_type"}),
 *     @ORM\Index(name="idx_id_wallet_creditor_type", columns={"id_wallet_creditor", "id_type"}),
 *     @ORM\Index(name="idx_operation_id_sub_type", columns={"id_sub_type"}),
 *     @ORM\Index(name="idx_operation_id_sponsorship", columns={"id_sponsorship"}),
 *     @ORM\Index(name="idx_operation_added", columns={"added"}),
 * })
 * @ORM\Entity(repositoryClass="Unilend\Repository\OperationRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Operation
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="amount", type="decimal", precision=12, scale=2)
     */
    private $amount;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \Unilend\Entity\OffresBienvenuesDetails
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\OffresBienvenuesDetails")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_welcome_offer", referencedColumnName="id_offre_bienvenue_detail")
     * })
     */
    private $idWelcomeOffer;

    /**
     * @var \Unilend\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wallet_debtor", referencedColumnName="id")
     * })
     */
    private $idWalletDebtor;

    /**
     * @var \Unilend\Entity\Receptions
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Receptions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wire_transfer_in", referencedColumnName="id_reception")
     * })
     */
    private $idWireTransferIn;

    /**
     * @var \Unilend\Entity\Virements
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Virements")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wire_transfer_out", referencedColumnName="id_virement")
     * })
     */
    private $idWireTransferOut;

    /**
     * @var \Unilend\Entity\OperationType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\OperationType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type", referencedColumnName="id", nullable=false)
     * })
     */
    private $idType;

    /**
     * @var \Unilend\Entity\OperationSubType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\OperationSubType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_sub_type", referencedColumnName="id")
     * })
     */
    private $idSubType;

    /**
     * @var \Unilend\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wallet_creditor", referencedColumnName="id")
     * })
     */
    private $idWalletCreditor;

    /**
     * @var \Unilend\Entity\Transfer
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Transfer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_transfer", referencedColumnName="id_transfer")
     * })
     */
    private $idTransfer;

    /**
     * @var \Unilend\Entity\EcheanciersEmprunteur
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\EcheanciersEmprunteur")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_payment_schedule", referencedColumnName="id_echeancier_emprunteur")
     * })
     */
    private $idPaymentSchedule;

    /**
     * @var \Unilend\Entity\Echeanciers
     *
     * @ORM\ManyToOne(targetEntity="\Unilend\Entity\Echeanciers")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_repayment_schedule", referencedColumnName="id_echeancier")
     * })
     */
    private $idRepaymentSchedule;

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
     * @var \Unilend\Entity\Backpayline
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Backpayline")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_backpayline", referencedColumnName="id_backpayline")
     * })
     */
    private $idBackpayline;

    /**
     * @var ProjectRepaymentTaskLog
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectRepaymentTaskLog")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_repayment_task_log", referencedColumnName="id")
     * })
     */
    private $idRepaymentTaskLog;
    /**
     * @var \Unilend\Entity\Sponsorship
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Sponsorship")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_sponsorship", referencedColumnName="id")
     * })
     */
    private $idSponsorship;

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
     * Set amount
     *
     * @param string $amount
     *
     * @return Operation
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Operation
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
     * Set idBackpayline
     *
     * @param \Unilend\Entity\Backpayline $idBackpayline
     *
     * @return Operation
     */
    public function setBackpayline(\Unilend\Entity\Backpayline $idBackpayline = null)
    {
        $this->idBackpayline = $idBackpayline;

        return $this;
    }

    /**
     * Get idBackpayline
     *
     * @return \Unilend\Entity\Backpayline
     */
    public function getBackpayline()
    {
        return $this->idBackpayline;
    }

    /**
     * Set idLoan
     *
     * @param \Unilend\Entity\Loans $idLoan
     *
     * @return Operation
     */
    public function setLoan(\Unilend\Entity\Loans $idLoan = null)
    {
        $this->idLoan = $idLoan;

        return $this;
    }

    /**
     * Get idLoan
     *
     * @return \Unilend\Entity\Loans
     */
    public function getLoan()
    {
        return $this->idLoan;
    }

    /**
     * Set idPaymentSchedule
     *
     * @param \Unilend\Entity\EcheanciersEmprunteur $idPaymentSchedule
     *
     * @return Operation
     */
    public function setPaymentSchedule(\Unilend\Entity\EcheanciersEmprunteur $idPaymentSchedule = null)
    {
        $this->idPaymentSchedule = $idPaymentSchedule;

        return $this;
    }

    /**
     * Get idPaymentSchedule
     *
     * @return \Unilend\Entity\EcheanciersEmprunteur
     */
    public function getPaymentSchedule()
    {
        return $this->idPaymentSchedule;
    }

    /**
     * Set idProject
     *
     * @param \Unilend\Entity\Projects $idProject
     *
     * @return Operation
     */
    public function setProject(\Unilend\Entity\Projects $idProject = null)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return \Unilend\Entity\Projects
     */
    public function getProject()
    {
        return $this->idProject;
    }

    /**
     * Set idRepaymentSchedule
     *
     * @param \Unilend\Entity\Echeanciers $idRepaymentSchedule
     *
     * @return Operation
     */
    public function setRepaymentSchedule(\Unilend\Entity\Echeanciers $idRepaymentSchedule = null)
    {
        $this->idRepaymentSchedule = $idRepaymentSchedule;

        return $this;
    }

    /**
     * Get idRepaymentSchedule
     *
     * @return \Unilend\Entity\Echeanciers
     */
    public function getRepaymentSchedule()
    {
        return $this->idRepaymentSchedule;
    }

    /**
     * Set idTransfer
     *
     * @param \Unilend\Entity\Transfer $idTransfer
     *
     * @return Operation
     */
    public function setTransfer(\Unilend\Entity\Transfer $idTransfer = null)
    {
        $this->idTransfer = $idTransfer;

        return $this;
    }

    /**
     * Get idTransfer
     *
     * @return \Unilend\Entity\Transfer
     */
    public function getTransfer()
    {
        return $this->idTransfer;
    }

    /**
     * Set idWalletCreditor
     *
     * @param \Unilend\Entity\Wallet $idWalletCreditor
     *
     * @return Operation
     */
    public function setWalletCreditor(\Unilend\Entity\Wallet $idWalletCreditor = null)
    {
        $this->idWalletCreditor = $idWalletCreditor;

        return $this;
    }

    /**
     * Get idWalletCreditor
     *
     * @return \Unilend\Entity\Wallet
     */
    public function getWalletCreditor()
    {
        return $this->idWalletCreditor;
    }

    /**
     * Set idWalletDebtor
     *
     * @param \Unilend\Entity\Wallet $idWalletDebtor
     *
     * @return Operation
     */
    public function setWalletDebtor(\Unilend\Entity\Wallet $idWalletDebtor = null)
    {
        $this->idWalletDebtor = $idWalletDebtor;

        return $this;
    }

    /**
     * Get idWalletDebtor
     *
     * @return \Unilend\Entity\Wallet
     */
    public function getWalletDebtor()
    {
        return $this->idWalletDebtor;
    }

    /**
     * Set idWelcomeOffer
     *
     * @param \Unilend\Entity\OffresBienvenuesDetails $idWelcomeOffer
     *
     * @return Operation
     */
    public function setWelcomeOffer(\Unilend\Entity\OffresBienvenuesDetails $idWelcomeOffer = null)
    {
        $this->idWelcomeOffer = $idWelcomeOffer;

        return $this;
    }

    /**
     * Get idWelcomeOffer
     *
     * @return \Unilend\Entity\OffresBienvenuesDetails
     */
    public function getWelcomeOffer()
    {
        return $this->idWelcomeOffer;
    }

    /**
     * Set idWireTransferIn
     *
     * @param \Unilend\Entity\Receptions $idWireTransferIn
     *
     * @return Operation
     */
    public function setWireTransferIn(\Unilend\Entity\Receptions $idWireTransferIn = null)
    {
        $this->idWireTransferIn = $idWireTransferIn;

        return $this;
    }

    /**
     * Get idWireTransferIn
     *
     * @return \Unilend\Entity\Receptions
     */
    public function getWireTransferIn()
    {
        return $this->idWireTransferIn;
    }

    /**
     * Set idWireTransferOut
     *
     * @param \Unilend\Entity\Virements $idWireTransferOut
     *
     * @return Operation
     */
    public function setWireTransferOut(\Unilend\Entity\Virements $idWireTransferOut = null)
    {
        $this->idWireTransferOut = $idWireTransferOut;

        return $this;
    }

    /**
     * Get idWireTransferOut
     *
     * @return \Unilend\Entity\Virements
     */
    public function getWireTransferOut()
    {
        return $this->idWireTransferOut;
    }

    /**
     * Set idType
     *
     * @param OperationType $idType
     *
     * @return Operation
     */
    public function setType(OperationType $idType = null)
    {
        $this->idType = $idType;

        return $this;
    }

    /**
     * Get idType
     *
     * @return OperationType
     */
    public function getType()
    {
        return $this->idType;
    }

    /**
     * Set idSubType
     *
     * @param OperationSubType $idSubType
     *
     * @return Operation
     */
    public function setSubType(OperationSubType $idSubType = null)
    {
        $this->idSubType = $idSubType;

        return $this;
    }

    /**
     * Get idSubType
     *
     * @return OperationSubType
     */
    public function getSubType()
    {
        return $this->idSubType;
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
     * @return ProjectRepaymentTaskLog
     */
    public function getIdRepaymentTaskLog()
    {
        return $this->idRepaymentTaskLog;
    }

    /**
     * @param $idRepaymentTaskLog
     *
     * @return $this
     */
    public function setIdRepaymentTaskLog(ProjectRepaymentTaskLog $idRepaymentTaskLog)
    {
        $this->idRepaymentTaskLog = $idRepaymentTaskLog;

        return $this;
    }

    /**
     * Set idWelcomeOffer
     *
     * @param \Unilend\Entity\Sponsorship $idSponsorship
     *
     * @return Operation
     */
    public function setSponsorship(\Unilend\Entity\Sponsorship $idSponsorship = null)
    {
        $this->idSponsorship = $idSponsorship;

        return $this;
    }

    /**
     * Get idSponsorship
     *
     * @return \Unilend\Entity\Sponsorship
     */
    public function getSponsorship()
    {
        return $this->idSponsorship;
    }
}
