<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @deprecated
 *
 * @ORM\Table(name="notifications", indexes={@ORM\Index(name="id_lender", columns={"id_lender"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\NotificationsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Notifications
{
    const TYPE_BID_REJECTED                   = 1;
    const TYPE_REPAYMENT                      = 2;
    const TYPE_BID_PLACED                     = 3;
    const TYPE_LOAN_ACCEPTED                  = 4;
    const TYPE_BANK_TRANSFER_CREDIT           = 5;
    const TYPE_CREDIT_CARD_CREDIT             = 6;
    const TYPE_DEBIT                          = 7;
    const TYPE_NEW_PROJECT                    = 8;
    const TYPE_PROJECT_PROBLEM                = 9;
    const TYPE_PROJECT_PROBLEM_REMINDER       = 10;
    const TYPE_PROJECT_RECOVERY               = 11;
    const TYPE_PROJECT_PRECAUTIONARY_PROCESS  = 12;
    const TYPE_PROJECT_RECEIVERSHIP           = 13;
    const TYPE_PROJECT_COMPULSORY_LIQUIDATION = 14;
    const TYPE_PROJECT_FAILURE                = 15;
    const TYPE_AUTOBID_BALANCE_LOW            = 16;
    const TYPE_AUTOBID_BALANCE_INSUFFICIENT   = 17;
    const TYPE_AUTOBID_FIRST_ACTIVATION       = 18;
    const TYPE_REPAYMENT_REGULARIZATION       = 19;

    const STATUS_READ   = 1;
    const STATUS_UNREAD = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint")
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(name="id_project", type="integer", nullable=true)
     */
    private $idProject;

    /**
     * @var int
     *
     * @ORM\Column(name="id_bid", type="integer", nullable=true)
     */
    private $idBid;

    /**
     * @var int
     *
     * @ORM\Column(name="amount", type="integer", nullable=true)
     */
    private $amount;

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
     * @ORM\Column(name="id_notification", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idNotification;

    /**
     * @var Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender", referencedColumnName="id", nullable=false)
     * })
     */
    private $idLender;



    /**
     * Set type
     *
     * @param int $type
     *
     * @return Notifications
     */
    public function setType(int $type): Notifications
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Set idProject
     *
     * @param int|null $idProject
     *
     * @return Notifications
     */
    public function setIdProject(?int $idProject): Notifications
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return int|null
     */
    public function getIdProject(): ?int
    {
        return $this->idProject;
    }

    /**
     * Set idBid
     *
     * @param int|null $idBid
     *
     * @return Notifications
     */
    public function setIdBid(?int $idBid): Notifications
    {
        $this->idBid = $idBid;

        return $this;
    }

    /**
     * Get idBid
     *
     * @return int|null
     */
    public function getIdBid(): ?int
    {
        return $this->idBid;
    }

    /**
     * Set amount
     *
     * @param int|null $amount
     *
     * @return Notifications
     */
    public function setAmount(?int $amount): Notifications
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return int|null
     */
    public function getAmount(): ?int
    {
        return $this->amount;
    }

    /**
     * Set status
     *
     * @param int $status
     *
     * @return Notifications
     */
    public function setStatus(int $status): Notifications
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Notifications
     */
    public function setAdded(\DateTime $added): Notifications
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime|null $updated
     *
     * @return Notifications
     */
    public function setUpdated(?\DateTime $updated): Notifications
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime|null
     */
    public function getUpdated(): ?\DateTime
    {
        return $this->updated;
    }

    /**
     * Get idNotification
     *
     * @return int
     */
    public function getIdNotification(): int
    {
        return $this->idNotification;
    }

    /**
     * Set idLender
     *
     * @param Wallet $idLender
     *
     * @return Notifications
     */
    public function setIdLender(Wallet $idLender): Notifications
    {
        $this->idLender = $idLender;

        return $this;
    }

    /**
     * Get idLender
     *
     * @return Wallet
     */
    public function getIdLender(): Wallet
    {
        return $this->idLender;
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
     * @ORM\PrePersist
     */
    public function setStatusValue()
    {
        if (null === $this->status) {
            $this->status = self::STATUS_UNREAD;
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
