<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BankAccount
 *
 * @ORM\Table(name="bank_account", uniqueConstraints={@ORM\UniqueConstraint(name="id_client_iban_UNIQUE", columns={"id_client", "iban"})}, indexes={@ORM\Index(name="fk_bank_account_id_client_idx", columns={"id_client"}), @ORM\Index(name="idx_id_attachment", columns={"id_attachment"})})
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\BankAccountRepository")
 * @ORM\HasLifecycleCallbacks
 */
class BankAccount
{
    const STATUS_PENDING   = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_ARCHIVED  = 2;

    /**
     * @var string
     * @ORM\Column(name="bic", type="string", length=100, nullable=false)
     */
    private $bic;

    /**
     * @var string
     * @ORM\Column(name="iban", type="string", length=100, nullable=false)
     */
    private $iban;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client")
     * })
     */
    private $idClient;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_pending", type="datetime", nullable=false)
     */
    private $datePending;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_validated", type="datetime", nullable=true)
     */
    private $dateValidated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_archived", type="datetime", nullable=true)
     */
    private $dateArchived;

    /**
     * @var Attachment
     *
     * @ORM\OneToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Attachment", inversedBy="bankAccount")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_attachment", referencedColumnName="id")
     * })
     */
    private $idAttachment;

    /**
     * Set bic
     *
     * @param string $bic
     *
     * @return BankAccount
     */
    public function setBic($bic)
    {
        $this->bic = $bic;

        return $this;
    }

    /**
     * Get bic
     *
     * @return string
     */
    public function getBic()
    {
        return $this->bic;
    }

    /**
     * Set iban
     *
     * @param string $iban
     *
     * @return BankAccount
     */
    public function setIban($iban)
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * Get iban
     *
     * @return string
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return BankAccount
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
     * @return BankAccount
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
     * Set idClient
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Clients $idClient
     *
     * @return BankAccount
     */
    public function setIdClient(\Unilend\Bundle\CoreBusinessBundle\Entity\Clients $idClient = null)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     */
    public function getIdClient()
    {
        return $this->idClient;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedAndPendingValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->added->getTimestamp()) {
            $this->added = new \DateTime();
        }

        if (! $this->datePending instanceof \DateTime || 1 > $this->datePending->getTimestamp()) {
            $this->datePending = new \DateTime();
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
     * @return \DateTime
     */
    public function getDatePending()
    {
        return $this->datePending;
    }

    /**
     * @param \DateTime $datePending
     *
     * @return BankAccount
     */
    public function setDatePending(\DateTime $datePending)
    {
        $this->datePending = $datePending;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateValidated()
    {
        return $this->dateValidated;
    }

    /**
     * @param \DateTime $dateValidated
     *
     * @return BankAccount
     */
    public function setDateValidated($dateValidated)
    {
        $this->dateValidated = $dateValidated;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateArchived()
    {
        return $this->dateArchived;
    }

    /**
     * @param \DateTime $dateArchived
     *
     * @return BankAccount
     */
    public function setDateArchived($dateArchived)
    {
        $this->dateArchived = $dateArchived;

        return $this;
    }

    public function getAttachment()
    {
        return $this->idAttachment;
    }

    /**
     * @param Attachment $attachment
     *
     * @return $this
     */
    public function setAttachment(Attachment $attachment = null)
    {
        $this->idAttachment = $attachment;

        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus()
    {
        if (null !== $this->dateArchived) {
            return self::STATUS_ARCHIVED;
        } elseif (null !== $this->dateValidated) {
            return self::STATUS_VALIDATED;
        } else {
            return self::STATUS_PENDING;
        }
    }
}
