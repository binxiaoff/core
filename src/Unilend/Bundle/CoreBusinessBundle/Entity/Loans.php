<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

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
     * @var float
     *
     * @ORM\Column(name="amount", type="float", precision=10, scale=0, nullable=false)
     */
    private $amount;

    /**
     * @var float
     *
     * @ORM\Column(name="rate", type="float", precision=10, scale=0, nullable=false)
     */
    private $rate;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
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
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
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
     *   @ORM\JoinColumn(name="id_lender", referencedColumnName="id")
     * })
     */
    private $idLender;

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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type_contract", referencedColumnName="id_contract")
     * })
     */
    private $idTypeContract;



    /**
     * Set idTransfer
     *
     * @param LoanTransfer $idTransfer
     *
     * @return Loans
     */
    public function setIdTransfer(LoanTransfer $idTransfer) : Loans
    {
        $this->idTransfer = $idTransfer;

        return $this;
    }

    /**
     * Get idTransfer
     *
     * @return LoanTransfer
     */
    public function getIdTransfer() : LoanTransfer
    {
        return $this->idTransfer;
    }

    /**
     * Set idProject
     *
     * @param Projects $idProject
     *
     * @return Loans
     */
    public function setProject(Projects $idProject) : Loans
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return Projects
     */
    public function getProject() : Projects
    {
        return $this->idProject;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return Loans
     */
    public function setAmount(float $amount) : Loans
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount() : float
    {
        return $this->amount;
    }

    /**
     * Set rate
     *
     * @param float $rate
     *
     * @return Loans
     */
    public function setRate(float $rate) : Loans
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Get rate
     *
     * @return float
     */
    public function getRate() : float
    {
        return $this->rate;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Loans
     */
    public function setStatus(int $status) : Loans
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus() : int
    {
        return $this->status;
    }

    /**
     * Set fichierDeclarationcontratpret
     *
     * @param string $fichierDeclarationcontratpret
     *
     * @return Loans
     */
    public function setFichierDeclarationcontratpret(string $fichierDeclarationcontratpret) : Loans
    {
        $this->fichierDeclarationcontratpret = $fichierDeclarationcontratpret;

        return $this;
    }

    /**
     * Get fichierDeclarationcontratpret
     *
     * @return string
     */
    public function getFichierDeclarationcontratpret() : string
    {
        return $this->fichierDeclarationcontratpret;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Loans
     */
    public function setAdded(\DateTime $added) : Loans
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded() : \DateTime
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return Loans
     */
    public function setUpdated(?\DateTime $updated) : Loans
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated() : \DateTime
    {
        return $this->updated;
    }

    /**
     * Get idLoan
     *
     * @return integer
     */
    public function getIdLoan() : int
    {
        return $this->idLoan;
    }

    /**
     * Set idLender
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idLender
     *
     * @return Loans
     */
    public function setIdLender(Wallet $idLender) : Loans
    {
        $this->idLender = $idLender;

        return $this;
    }

    /**
     * Get idLender
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     */
    public function getIdLender() : Wallet
    {
        return $this->idLender;
    }

    /**
     * Set idTypeContract
     *
     * @param UnderlyingContract $idTypeContract
     *
     * @return Loans
     */
    public function setIdTypeContract(UnderlyingContract $idTypeContract) : Loans
    {
        $this->idTypeContract = $idTypeContract;

        return $this;
    }

    /**
     * Get idTypeContract
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract
     */
    public function getIdTypeContract() : UnderlyingContract
    {
        return $this->idTypeContract;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue() : void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue() : void
    {
        $this->updated = new \DateTime();
    }
}
