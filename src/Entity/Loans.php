<?php

namespace Unilend\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\{Lendable, Timestampable};

/**
 * Loans
 *
 * @ORM\Table(name="loans", indexes={
 *     @ORM\Index(name="status", columns={"status"}),
 *     @ORM\Index(name="idx_loans_added", columns={"added"})
 * })
 * @ORM\Entity(repositoryClass="Unilend\Repository\LoansRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\AssociationOverrides({@ORM\AssociationOverride(name="project", inversedBy="loans")})
 */
class Loans
{
    use Lendable;
    use Timestampable;

    const STATUS_PENDING  = 2;
    const STATUS_ACCEPTED = 0;
    const STATUS_REJECTED = 1;

    /**
     * @var \Unilend\Entity\LoanTransfer|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\LoanTransfer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_transfer", referencedColumnName="id_loan_transfer")
     * })
     */
    private $idTransfer;

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
     * @var \Unilend\Entity\UnderlyingContract
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\UnderlyingContract")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type_contract", referencedColumnName="id_contract", nullable=false)
     * })
     */
    private $idTypeContract;

    /**
     * @var \Unilend\Entity\AcceptationsLegalDocs|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\AcceptationsLegalDocs")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_acceptation_legal_doc", referencedColumnName="id_acceptation")
     * })
     */
    private $idAcceptationLegalDoc;

    /**
     * @var LoanPercentFee[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\LoanPercentFee", mappedBy="loan", cascade={"persist"}, orphanRemoval=true)
     */
    private $loanPercentFees;

    public function __construct()
    {
        $this->loanPercentFees = new ArrayCollection();
        $this->traitInit();
    }

    /**
     * @param LoanTransfer|null $idTransfer
     *
     * @return Loans
     */
    public function setIdTransfer(?LoanTransfer $idTransfer): Loans
    {
        $this->idTransfer = $idTransfer;

        return $this;
    }

    /**
     * @return LoanTransfer|null
     */
    public function getIdTransfer(): ?LoanTransfer
    {
        /** @todo to be removed when it is fully under doctrine */
        if (null !== $this->idTransfer) {
            try {
                $this->idTransfer->getIdTransfer();
            } catch (EntityNotFoundException $exception) {
                $this->idTransfer = null;
            }
        }

        return $this->idTransfer;
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
     * @return integer
     */
    public function getIdLoan(): int
    {
        return $this->idLoan;
    }

    /**
     * @param UnderlyingContract $idTypeContract
     *
     * @return Loans
     */
    public function setIdTypeContract(UnderlyingContract $idTypeContract): Loans
    {
        $this->idTypeContract = $idTypeContract;

        return $this;
    }

    /**
     * @return UnderlyingContract
     */
    public function getIdTypeContract(): UnderlyingContract
    {
        return $this->idTypeContract;
    }

    /**
     * @param AcceptationsLegalDocs|null $idAcceptationLegalDocs
     *
     * @return Loans
     */
    public function setIdAcceptationLegalDoc(?AcceptationsLegalDocs $idAcceptationLegalDocs): Loans
    {
        $this->idAcceptationLegalDoc = $idAcceptationLegalDocs;

        return $this;
    }

    /**
     * @return AcceptationsLegalDocs|null
     */
    public function getIdAcceptationLegalDoc(): ?AcceptationsLegalDocs
    {
        return $this->idAcceptationLegalDoc;
    }

    /**
     * @return iterable|LoanPercentFee[]
     */
    public function getLoanPercentFees(): iterable
    {
        return $this->loanPercentFees;
    }

    /**
     * @param PercentFee $percentFee
     *
     * @return Loans
     */
    public function addPercentFee(PercentFee $percentFee): Loans
    {
        $loanPercentFee = (new LoanPercentFee())
            ->setLoan($this)
            ->setPercentFee($percentFee);

        if (!$this->loanPercentFees->contains($loanPercentFee)) {
            $this->loanPercentFees->add($loanPercentFee);
        }

        return $this;
    }

    /**
     * @param LoanPercentFee $loanPercentFee
     *
     * @return Loans
     */
    public function removeLoanFee(LoanPercentFee $loanPercentFee): Loans
    {
        if ($this->loanPercentFees->contains($loanPercentFee)) {
            $this->loanPercentFees->removeElement($loanPercentFee);
        }

        return $this;
    }
}
