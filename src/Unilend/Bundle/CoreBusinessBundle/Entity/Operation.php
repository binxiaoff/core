<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Operation
 *
 * @ORM\Table(name="operation", indexes={@ORM\Index(name="fk_operation_id_type_idx", columns={"id_type"}), @ORM\Index(name="fk_id_project_idx", columns={"id_project"}), @ORM\Index(name="fk_id_loan_idx", columns={"id_loan"}), @ORM\Index(name="fk_id_echeancier_idx", columns={"id_echeancier"}), @ORM\Index(name="fk_id_backpayline_idx", columns={"id_backpayline"}), @ORM\Index(name="fk_id_welcome_offer_idx", columns={"id_welcome_offer"}), @ORM\Index(name="fk_id_wire_transfer_out_idx", columns={"id_wire_transfer_out"}), @ORM\Index(name="fk_id_wire_transfer_in_idx", columns={"id_wire_transfer_in"}), @ORM\Index(name="fk_id_direct_debit_idx", columns={"id_direct_debit"}), @ORM\Index(name="fk_id_transfer_idx", columns={"id_transfer"}), @ORM\Index(name="idx_id_wallet_debitor_type", columns={"id_wallet_debtor", "id_type"}), @ORM\Index(name="idx_id_wallet_creditor_type", columns={"id_wallet_creditor", "id_type"}), @ORM\Index(name="IDX_1981A66D20799ED6", columns={"id_wallet_creditor"}), @ORM\Index(name="IDX_1981A66DE344C28F", columns={"id_wallet_debtor"})})
 * @ORM\Entity
 */
class Operation
{
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
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_direct_debit", referencedColumnName="id_prelevement")
     * })
     */
    private $idDirectDebit;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_echeancier", referencedColumnName="id_echeancier")
     * })
     */
    private $idEcheancier;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set idWelcomeOffer
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails $idWelcomeOffer
     *
     * @return Operation
     */
    public function setIdWelcomeOffer(\Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails $idWelcomeOffer = null)
    {
        $this->idWelcomeOffer = $idWelcomeOffer;

        return $this;
    }

    /**
     * Get idWelcomeOffer
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails
     */
    public function getIdWelcomeOffer()
    {
        return $this->idWelcomeOffer;
    }

    /**
     * Set idWalletDebtor
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWalletDebtor
     *
     * @return Operation
     */
    public function setIdWalletDebtor(\Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWalletDebtor = null)
    {
        $this->idWalletDebtor = $idWalletDebtor;

        return $this;
    }

    /**
     * Get idWalletDebtor
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     */
    public function getIdWalletDebtor()
    {
        return $this->idWalletDebtor;
    }

    /**
     * Set idWireTransferIn
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Receptions $idWireTransferIn
     *
     * @return Operation
     */
    public function setIdWireTransferIn(\Unilend\Bundle\CoreBusinessBundle\Entity\Receptions $idWireTransferIn = null)
    {
        $this->idWireTransferIn = $idWireTransferIn;

        return $this;
    }

    /**
     * Get idWireTransferIn
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Receptions
     */
    public function getIdWireTransferIn()
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
    public function setIdWireTransferOut(\Unilend\Bundle\CoreBusinessBundle\Entity\Virements $idWireTransferOut = null)
    {
        $this->idWireTransferOut = $idWireTransferOut;

        return $this;
    }

    /**
     * Get idWireTransferOut
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Virements
     */
    public function getIdWireTransferOut()
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
    public function setIdType(\Unilend\Bundle\CoreBusinessBundle\Entity\OperationType $idType = null)
    {
        $this->idType = $idType;

        return $this;
    }

    /**
     * Get idType
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\OperationType
     */
    public function getIdType()
    {
        return $this->idType;
    }

    /**
     * Set idWalletCreditor
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWalletCreditor
     *
     * @return Operation
     */
    public function setIdWalletCreditor(\Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWalletCreditor = null)
    {
        $this->idWalletCreditor = $idWalletCreditor;

        return $this;
    }

    /**
     * Get idWalletCreditor
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     */
    public function getIdWalletCreditor()
    {
        return $this->idWalletCreditor;
    }

    /**
     * Set idTransfer
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Transfer $idTransfer
     *
     * @return Operation
     */
    public function setIdTransfer(\Unilend\Bundle\CoreBusinessBundle\Entity\Transfer $idTransfer = null)
    {
        $this->idTransfer = $idTransfer;

        return $this;
    }

    /**
     * Get idTransfer
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Transfer
     */
    public function getIdTransfer()
    {
        return $this->idTransfer;
    }

    /**
     * Set idDirectDebit
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements $idDirectDebit
     *
     * @return Operation
     */
    public function setIdDirectDebit(\Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements $idDirectDebit = null)
    {
        $this->idDirectDebit = $idDirectDebit;

        return $this;
    }

    /**
     * Get idDirectDebit
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements
     */
    public function getIdDirectDebit()
    {
        return $this->idDirectDebit;
    }

    /**
     * Set idEcheancier
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers $idEcheancier
     *
     * @return Operation
     */
    public function setIdEcheancier(\Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers $idEcheancier = null)
    {
        $this->idEcheancier = $idEcheancier;

        return $this;
    }

    /**
     * Get idEcheancier
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers
     */
    public function getIdEcheancier()
    {
        return $this->idEcheancier;
    }

    /**
     * Set idLoan
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Loans $idLoan
     *
     * @return Operation
     */
    public function setIdLoan(\Unilend\Bundle\CoreBusinessBundle\Entity\Loans $idLoan = null)
    {
        $this->idLoan = $idLoan;

        return $this;
    }

    /**
     * Get idLoan
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Loans
     */
    public function getIdLoan()
    {
        return $this->idLoan;
    }

    /**
     * Set idProject
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Projects $idProject
     *
     * @return Operation
     */
    public function setIdProject(\Unilend\Bundle\CoreBusinessBundle\Entity\Projects $idProject = null)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * Set idBackpayline
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline $idBackpayline
     *
     * @return Operation
     */
    public function setIdBackpayline(\Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline $idBackpayline = null)
    {
        $this->idBackpayline = $idBackpayline;

        return $this;
    }

    /**
     * Get idBackpayline
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline
     */
    public function getIdBackpayline()
    {
        return $this->idBackpayline;
    }
}
