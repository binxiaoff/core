<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping as ORM;

/**
 * Loans
 *
 * @ORM\Table(name="loans", indexes={@ORM\Index(name="id_lender", columns={"id_lender"}), @ORM\Index(name="id_project", columns={"id_project"}), @ORM\Index(name="status", columns={"status"}), @ORM\Index(name="idx_loans_added", columns={"added"}), @ORM\Index(name="idx_loans_id_type_contract", columns={"id_type_contract"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\LoansRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Loans
{
    const STATUS_ACCEPTED = 0;
    const STATUS_REJECTED = 1;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\LoanTransfer
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\LoanTransfer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_transfer", referencedColumnName="id_loan_transfer")
     * })
     */
    private $idTransfer;

    /**
     * @var int
     *
     * @ORM\Column(name="amount", type="integer")
     */
    private $amount;

    /**
     * @var float
     *
     * @ORM\Column(name="rate", type="decimal", precision=3, scale=1)
     */
    private $rate;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="fichier_declarationContratPret", type="string", length=191, nullable=true)
     */
    private $fichierDeclarationcontratpret;

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
     * @ORM\Column(name="id_loan", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLoan;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender", referencedColumnName="id", nullable=false)
     * })
     */
    private $idLender;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Projects
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Projects")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_project", referencedColumnName="id_project", nullable=false)
     * })
     */
    private $idProject;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type_contract", referencedColumnName="id_contract", nullable=false)
     * })
     */
    private $idTypeContract;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\AcceptationsLegalDocs
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\AcceptationsLegalDocs")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_acceptation_legal_doc", referencedColumnName="id_acceptation")
     * })
     */
    private $idAcceptationLegalDoc;


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
        if (null !==  $this->idTransfer) {
            try {
                $this->idTransfer->getIdTransfer();
            } catch (EntityNotFoundException $exception) {
                $this->idTransfer = null;
            }
        }

        return $this->idTransfer;
    }

    /**
     * @param Projects $idProject
     *
     * @return Loans
     */
    public function setProject(Projects $idProject): Loans
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * @return Projects
     */
    public function getProject(): Projects
    {
        return $this->idProject;
    }

    /**
     * @param float $amount
     *
     * @return Loans
     */
    public function setAmount(float $amount): Loans
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param float $rate
     *
     * @return Loans
     */
    public function setRate(float $rate): Loans
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * @return float
     */
    public function getRate(): float
    {
        return $this->rate;
    }

    /**
     * @param integer $status
     *
     * @return Loans
     */
    public function setStatus(int $status): Loans
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return integer
     */
    public function getStatus(): int
    {
        return $this->status;
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
     * @param \DateTime $added
     *
     * @return Loans
     */
    public function setAdded(\DateTime $added): Loans
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * @param \DateTime|null $updated
     *
     * @return Loans
     */
    public function setUpdated(?\DateTime $updated): Loans
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * @return integer
     */
    public function getIdLoan(): int
    {
        return $this->idLoan;
    }

    /**
     * @param Wallet $idLender
     *
     * @return Loans
     */
    public function setIdLender(Wallet $idLender): Loans
    {
        $this->idLender = $idLender;

        return $this;
    }

    /**
     * @return Wallet
     */
    public function getIdLender(): Wallet
    {
        return $this->idLender;
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
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue(): void
    {
        $this->updated = new \DateTime();
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
}
