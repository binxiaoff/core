<?php

namespace Unilend\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Unilend\Entity\Traits\{LendableTrait, TimestampableTrait};

/**
 * @Gedmo\Loggable(logEntryClass="Unilend\Entity\Versioned\VersionedLoan")
 *
 * @ORM\Table(name="loans", indexes={
 *     @ORM\Index(columns={"id_tranche", "status"}),
 *     @ORM\Index(columns={"added"})
 * })
 * @ORM\Entity(repositoryClass="Unilend\Repository\LoansRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\AssociationOverrides({@ORM\AssociationOverride(name="tranche", inversedBy="loans")})
 */
class Loans
{
    use LendableTrait;
    use TimestampableTrait;

    public const STATUS_PENDING  = 2;
    public const STATUS_ACCEPTED = 0;
    public const STATUS_REJECTED = 1;

    /**
     * @var LoanTransfer|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\LoanTransfer")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_transfer", referencedColumnName="id_loan_transfer")
     * })
     */
    private $transfer;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fichier_declarationContratPret", type="string", length=191, nullable=true)
     */
    private $fichierDeclarationcontratpret;

    /**
     * @var int
     *
     * @ORM\Column(name="id_loan", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLoan;

    /**
     * @var UnderlyingContract
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\UnderlyingContract")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_type_contract", referencedColumnName="id_contract", nullable=false)
     * })
     */
    private $underlyingContract;

    /**
     * @var AcceptationsLegalDocs|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\AcceptationsLegalDocs")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_acceptation_legal_doc", referencedColumnName="id_acceptation")
     * })
     */
    private $acceptationLegalDoc;

    /**
     * @var LoanFee[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="LoanFee", mappedBy="loan", cascade={"persist"}, orphanRemoval=true)
     */
    private $loanFees;

    /**
     * Loans constructor.
     */
    public function __construct()
    {
        $this->loanFees = new ArrayCollection();
        $this->traitInit();
    }

    /**
     * @param LoanTransfer|null $transfer
     *
     * @return Loans
     */
    public function setTransfer(?LoanTransfer $transfer): Loans
    {
        $this->transfer = $transfer;

        return $this;
    }

    /**
     * @return LoanTransfer|null
     */
    public function getTransfer(): ?LoanTransfer
    {
        // @todo to be removed when it is fully under doctrine
        if (null !== $this->transfer) {
            $this->transfer->getIdTransfer();
        }

        return $this->transfer;
    }

    /**
     * @param string|null $fichierDeclarationcontratpret
     *
     * @return Loans
     */
    public function setFichierDeclarationcontratpret(?string $fichierDeclarationcontratpret): Loans
    {
        $this->fichierDeclarationcontratpret = $fichierDeclarationcontratpret;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFichierDeclarationcontratpret(): ?string
    {
        return $this->fichierDeclarationcontratpret;
    }

    /**
     * @return int
     */
    public function getIdLoan(): int
    {
        return $this->idLoan;
    }

    /**
     * @param UnderlyingContract $underlyingContract
     *
     * @return Loans
     */
    public function setUnderlyingContract(UnderlyingContract $underlyingContract): Loans
    {
        $this->underlyingContract = $underlyingContract;

        return $this;
    }

    /**
     * @return UnderlyingContract
     */
    public function getUnderlyingContract(): UnderlyingContract
    {
        return $this->underlyingContract;
    }

    /**
     * @param AcceptationsLegalDocs|null $acceptationLegalDoc
     *
     * @return Loans
     */
    public function setAcceptationLegalDoc(?AcceptationsLegalDocs $acceptationLegalDoc): Loans
    {
        $this->acceptationLegalDoc = $acceptationLegalDoc;

        return $this;
    }

    /**
     * @return AcceptationsLegalDocs|null
     */
    public function getAcceptationLegalDoc(): ?AcceptationsLegalDocs
    {
        return $this->acceptationLegalDoc;
    }

    /**
     * @return iterable|LoanFee[]
     */
    public function getLoanFees(): iterable
    {
        return $this->loanFees;
    }

    /**
     * @param LoanFee $loanFee
     *
     * @return Loans
     */
    public function addLoanFee(LoanFee $loanFee): Loans
    {
        $loanFee->setLoan($this);

        if (false === $this->loanFees->contains($loanFee)) {
            $this->loanFees->add($loanFee);
        }

        return $this;
    }

    /**
     * @param LoanFee $loanFee
     *
     * @return Loans
     */
    public function removeLoanFee(LoanFee $loanFee): Loans
    {
        if ($this->loanFees->contains($loanFee)) {
            $this->loanFees->removeElement($loanFee);
        }

        return $this;
    }
}
