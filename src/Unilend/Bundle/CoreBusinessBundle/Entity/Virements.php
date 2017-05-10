<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Virements
 *
 * @ORM\Table(name="virements", indexes={@ORM\Index(name="id_project", columns={"id_project"}), @ORM\Index(name="id_client", columns={"id_client"}), @ORM\Index(name="id_bank_account", columns={"id_bank_account"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\VirementsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Virements
{
    const STATUS_PENDING          = 0;
    const STATUS_CLIENT_VALIDATED = 10;
    const STATUS_VALIDATED        = 20;
    const STATUS_SENT             = 30;
    const STATUS_DENIED           = 40;

    const TYPE_LENDER   = 1;
    const TYPE_BORROWER = 2;
    const TYPE_UNILEND  = 4;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client")
     * })
     */
    private $idClient;

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
     * @var integer
     *
     * @ORM\Column(name="id_transaction", type="integer", nullable=false)
     */
    private $idTransaction = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="montant", type="integer", nullable=false)
     */
    private $montant;

    /**
     * @var string
     *
     * @ORM\Column(name="motif", type="string", length=150, nullable=false)
     */
    private $motif;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;

    /**
     * @var BankAccount
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_bank_account", referencedColumnName="id")
     * })
     */
    private $bankAccount;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user_request", referencedColumnName="id_user")
     * })
     */
    private $userRequest;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user_validation", referencedColumnName="id_user")
     * })
     */
    private $userValidation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="validated", type="datetime", nullable=true)
     */
    private $validated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="transfer_at", type="datetime", nullable=true)
     */
    private $transferAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added_xml", type="datetime", nullable=true)
     */
    private $addedXml;

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
     * @ORM\Column(name="id_virement", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idVirement;



    /**
     * Set idClient
     *
     * @param Clients $idClient
     *
     * @return Virements
     */
    public function setClient(Clients $idClient = null)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return Clients
     */
    public function getClient()
    {
        return $this->idClient;
    }

    /**
     * Set idProject
     *
     * @param Projects $idProject
     *
     * @return Virements
     */
    public function setProject(Projects $idProject = null)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return Projects
     */
    public function getProject()
    {
        return $this->idProject;
    }

    /**
     * Set idTransaction
     *
     * @param integer $idTransaction
     *
     * @return Virements
     */
    public function setIdTransaction($idTransaction)
    {
        $this->idTransaction = $idTransaction;

        return $this;
    }

    /**
     * Get idTransaction
     *
     * @return integer
     */
    public function getIdTransaction()
    {
        return $this->idTransaction;
    }

    /**
     * Set montant
     *
     * @param integer $montant
     *
     * @return Virements
     */
    public function setMontant($montant)
    {
        $this->montant = $montant;

        return $this;
    }

    /**
     * Get montant
     *
     * @return integer
     */
    public function getMontant()
    {
        return $this->montant;
    }

    /**
     * Set motif
     *
     * @param string $motif
     *
     * @return Virements
     */
    public function setMotif($motif)
    {
        $this->motif = $motif;

        return $this;
    }

    /**
     * Get motif
     *
     * @return string
     */
    public function getMotif()
    {
        return $this->motif;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return Virements
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set bankAccount
     *
     * @param BankAccount $bankAccount
     *
     * @return Virements
     */
    public function setBankAccount(BankAccount $bankAccount = null)
    {
        $this->bankAccount = $bankAccount;

        return $this;
    }

    /**
     * Get bankAccount
     *
     * @return BankAccount
     */
    public function getBankAccount()
    {
        return $this->bankAccount;
    }

    /**
     * Set userRequest
     *
     * @param Users $userRequest
     *
     * @return Virements
     */
    public function setUserRequest(Users $userRequest = null)
    {
        $this->userRequest = $userRequest;

        return $this;
    }

    /**
     * Get userRequest
     *
     * @return Users
     */
    public function getUserRequest()
    {
        return $this->userRequest;
    }

    /**
     * Set userValidation
     *
     * @param Users $userValidation
     *
     * @return Virements
     */
    public function setUserValidation(Users $userValidation = null)
    {
        $this->userValidation = $userValidation;

        return $this;
    }

    /**
     * Get userValidation
     *
     * @return Users
     */
    public function getUserValidation()
    {
        return $this->userValidation;
    }

    /**
     * Set validated
     *
     * @param \DateTime $validated
     *
     * @return Virements
     */
    public function setValidated(\DateTime $validated = null)
    {
        $this->validated = $validated;

        return $this;
    }

    /**
     * Get validated
     *
     * @return \DateTime
     */
    public function getValidated()
    {
        return $this->validated;
    }

    /**
     * Set transferAt
     *
     * @param \DateTime $transferAt
     *
     * @return Virements
     */
    public function setTransferAt(\DateTime $transferAt = null)
    {
        $this->transferAt = $transferAt;

        return $this;
    }

    /**
     * Get transferAt
     *
     * @return \DateTime
     */
    public function getTransferAt()
    {
        return $this->transferAt;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Virements
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
     * Set addedXml
     *
     * @param \DateTime $addedXml
     *
     * @return Virements
     */
    public function setAddedXml($addedXml)
    {
        $this->addedXml = $addedXml;

        return $this;
    }

    /**
     * Get addedXml
     *
     * @return \DateTime
     */
    public function getAddedXml()
    {
        return $this->addedXml;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Virements
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
     * @return Virements
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
     * Get idVirement
     *
     * @return integer
     */
    public function getIdVirement()
    {
        return $this->idVirement;
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
}
