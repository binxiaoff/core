<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Operation
 *
 * @ORM\Table(name="operation", indexes={@ORM\Index(name="fk_operation_id_type_idx", columns={"id_type"}), @ORM\Index(name="fk_id_project_idx", columns={"id_project"}), @ORM\Index(name="fk_id_loan_idx", columns={"id_loan"}), @ORM\Index(name="fk_id_payment_schedule_idx", columns={"id_payment_schedule"}), @ORM\Index(name="fk_id_repayment_schedule_idx", columns={"id_repayment_schedule"}), @ORM\Index(name="fk_id_backpayline_idx", columns={"id_backpayline"}), @ORM\Index(name="fk_id_welcome_offer_idx", columns={"id_welcome_offer"}), @ORM\Index(name="fk_id_wire_transfer_out_idx", columns={"id_wire_transfer_out"}), @ORM\Index(name="fk_id_wire_transfer_in_idx", columns={"id_wire_transfer_in"}), @ORM\Index(name="fk_id_transfer_idx", columns={"id_transfer"}), @ORM\Index(name="idx_id_wallet_debitor_type", columns={"id_wallet_debtor", "id_type"}), @ORM\Index(name="idx_id_wallet_creditor_type", columns={"id_wallet_creditor", "id_type"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Operation
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="amount", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $amount;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_welcome_offer", referencedColumnName="id_offre_bienvenue_detail")
     * })
     */
    private $idWelcomeOffer;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wallet_debtor", referencedColumnName="id")
     * })
     */
    private $idWalletDebtor;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Receptions
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Receptions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wire_transfer_in", referencedColumnName="id_reception")
     * })
     */
    private $idWireTransferIn;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Virements
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Virements")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wire_transfer_out", referencedColumnName="id_virement")
     * })
     */
    private $idWireTransferOut;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\OperationType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\OperationType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type", referencedColumnName="id")
     * })
     */
    private $idType;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wallet_creditor", referencedColumnName="id")
     * })
     */
    private $idWalletCreditor;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Transfer
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Transfer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_transfer", referencedColumnName="id_transfer")
     * })
     */
    private $idTransfer;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_payment_schedule", referencedColumnName="id_echeancier_emprunteur")
     * })
     */
    private $idPaymentSchedule;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers
     *
     * @ORM\ManyToOne(targetEntity="\Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_repayment_schedule", referencedColumnName="id_echeancier")
     * })
     */
    private $idRepaymentSchedule;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Loans
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Loans")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_loan", referencedColumnName="id_loan")
     * })
     */
    private $idLoan;

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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_backpayline", referencedColumnName="id_backpayline")
     * })
     */
    private $idBackpayline;



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
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline $idBackpayline
     *
     * @return Operation
     */
    public function setBackpayline(\Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline $idBackpayline = null)
    {
        $this->idBackpayline = $idBackpayline;

        return $this;
    }

    /**
     * Get idBackpayline
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline
     */
    public function getBackpayline()
    {
        return $this->idBackpayline;
    }

    /**
     * Set idLoan
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Loans $idLoan
     *
     * @return Operation
     */
    public function setLoan(\Unilend\Bundle\CoreBusinessBundle\Entity\Loans $idLoan = null)
    {
        $this->idLoan = $idLoan;

        return $this;
    }

    /**
     * Get idLoan
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Loans
     */
    public function getLoan()
    {
        return $this->idLoan;
    }

    /**
     * Set idPaymentSchedule
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur $idPaymentSchedule
     *
     * @return Operation
     */
    public function setPaymentSchedule(\Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur $idPaymentSchedule = null)
    {
        $this->idPaymentSchedule = $idPaymentSchedule;

        return $this;
    }

    /**
     * Get idPaymentSchedule
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur
     */
    public function getPaymentSchedule()
    {
        return $this->idPaymentSchedule;
    }

    /**
     * Set idProject
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Projects $idProject
     *
     * @return Operation
     */
    public function setProject(\Unilend\Bundle\CoreBusinessBundle\Entity\Projects $idProject = null)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     */
    public function getProject()
    {
        return $this->idProject;
    }

    /**
     * Set idRepaymentSchedule
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers $idRepaymentSchedule
     *
     * @return Operation
     */
    public function setRepaymentSchedule(\Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers $idRepaymentSchedule = null)
    {
        $this->idRepaymentSchedule = $idRepaymentSchedule;

        return $this;
    }

    /**
     * Get idRepaymentSchedule
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers
     */
    public function getRepaymentSchedule()
    {
        return $this->idRepaymentSchedule;
    }

    /**
     * Set idTransfer
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Transfer $idTransfer
     *
     * @return Operation
     */
    public function setTransfer(\Unilend\Bundle\CoreBusinessBundle\Entity\Transfer $idTransfer = null)
    {
        $this->idTransfer = $idTransfer;

        return $this;
    }

    /**
     * Get idTransfer
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Transfer
     */
    public function getTransfer()
    {
        return $this->idTransfer;
    }

    /**
     * Set idWalletCreditor
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWalletCreditor
     *
     * @return Operation
     */
    public function setWalletCreditor(\Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWalletCreditor = null)
    {
        $this->idWalletCreditor = $idWalletCreditor;

        return $this;
    }

    /**
     * Get idWalletCreditor
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     */
    public function getWalletCreditor()
    {
        return $this->idWalletCreditor;
    }

    /**
     * Set idWalletDebtor
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWalletDebtor
     *
     * @return Operation
     */
    public function setWalletDebtor(\Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWalletDebtor = null)
    {
        $this->idWalletDebtor = $idWalletDebtor;

        return $this;
    }

    /**
     * Get idWalletDebtor
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     */
    public function getWalletDebtor()
    {
        return $this->idWalletDebtor;
    }

    /**
     * Set idWelcomeOffer
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails $idWelcomeOffer
     *
     * @return Operation
     */
    public function setWelcomeOffer(\Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails $idWelcomeOffer = null)
    {
        $this->idWelcomeOffer = $idWelcomeOffer;

        return $this;
    }

    /**
     * Get idWelcomeOffer
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails
     */
    public function getWelcomeOffer()
    {
        return $this->idWelcomeOffer;
    }

    /**
     * Set idWireTransferIn
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Receptions $idWireTransferIn
     *
     * @return Operation
     */
    public function setWireTransferIn(\Unilend\Bundle\CoreBusinessBundle\Entity\Receptions $idWireTransferIn = null)
    {
        $this->idWireTransferIn = $idWireTransferIn;

        return $this;
    }

    /**
     * Get idWireTransferIn
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Receptions
     */
    public function getWireTransferIn()
    {
        return $this->idWireTransferIn;
    }

    /**
     * Set idWireTransferOut
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Virements $idWireTransferOut
     *
     * @return Operation
     */
    public function setWireTransferOut(\Unilend\Bundle\CoreBusinessBundle\Entity\Virements $idWireTransferOut = null)
    {
        $this->idWireTransferOut = $idWireTransferOut;

        return $this;
    }

    /**
     * Get idWireTransferOut
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Virements
     */
    public function getWireTransferOut()
    {
        return $this->idWireTransferOut;
    }

    /**
     * Set idType
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\OperationType $idType
     *
     * @return Operation
     */
    public function setType(\Unilend\Bundle\CoreBusinessBundle\Entity\OperationType $idType = null)
    {
        $this->idType = $idType;

        return $this;
    }

    /**
     * Get idType
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\OperationType
     */
    public function getType()
    {
        return $this->idType;
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
