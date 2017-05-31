<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Notifications
 *
 * @ORM\Table(name="notifications", indexes={@ORM\Index(name="id_lender", columns={"id_lender"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\NotificationsRepository")
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

    const STATUS_READ   = 1;
    const STATUS_UNREAD = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_lender", type="integer", nullable=false)
     */
    private $idLender;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project", type="integer", nullable=false)
     */
    private $idProject;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_bid", type="integer", nullable=false)
     */
    private $idBid;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount", type="integer", nullable=false)
     */
    private $amount;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

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
     * @ORM\Column(name="id_notification", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idNotification;



    /**
     * Set idLender
     *
     * @param integer $idLender
     *
     * @return Notifications
     */
    public function setIdLender($idLender)
    {
        $this->idLender = $idLender;

        return $this;
    }

    /**
     * Get idLender
     *
     * @return integer
     */
    public function getIdLender()
    {
        return $this->idLender;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return Notifications
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
     * Set idProject
     *
     * @param integer $idProject
     *
     * @return Notifications
     */
    public function setIdProject($idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * Get idProject
     *
     * @return integer
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * Set idBid
     *
     * @param integer $idBid
     *
     * @return Notifications
     */
    public function setIdBid($idBid)
    {
        $this->idBid = $idBid;

        return $this;
    }

    /**
     * Get idBid
     *
     * @return integer
     */
    public function getIdBid()
    {
        return $this->idBid;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     *
     * @return Notifications
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Notifications
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Notifications
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
     * @return Notifications
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
     * Get idNotification
     *
     * @return integer
     */
    public function getIdNotification()
    {
        return $this->idNotification;
    }
}
