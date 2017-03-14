<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Backpayline
 *
 * @ORM\Table(name="backpayline", indexes={@ORM\Index(name="idx_backpayline_token", columns={"token"}), @ORM\Index(name="idx_id_wallet", columns={"id_wallet"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Backpayline
{
    const WS_DEFAULT_VERSION = 3;

    const CODE_TRANSACTION_APPROVED = '00000';
    const CODE_TRANSACTION_CANCELLED = '02319';

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wallet", referencedColumnName="id")
     * })
     */
    private $idWallet;

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=191, nullable=true)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="date", type="string", length=191, nullable=true)
     */
    private $date;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount", type="integer", nullable=false)
     */
    private $amount;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=191, nullable=true)
     */
    private $token;

    /**
     * @var string
     *
     * @ORM\Column(name="serialize", type="text", length=16777215, nullable=true)
     */
    private $serialize;

    /**
     * @var string
     *
     * @ORM\Column(name="serialize_do_payment", type="text", length=16777215, nullable=true)
     */
    private $serializeDoPayment;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=50, nullable=true)
     */
    private $code;

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
     * @ORM\Column(name="id_backpayline", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idBackpayline;



    /**
     * Set idWallet
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWallet
     *
     * @return Backpayline
     */
    public function setWallet(\Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWallet = null)
    {
        $this->idWallet = $idWallet;

        return $this;
    }

    /**
     * Get idWallet
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
     */
    public function getWallet()
    {
        return $this->idWallet;
    }


    /**
     * Set idWallet
     *
     * @param string $serializeDoPayment
     *
     * @return Backpayline
     */
    public function setSerializeDoPayment($serializeDoPayment = null)
    {
        $this->serializeDoPayment = $serializeDoPayment;

        return $this;
    }

    /**
     * Get idWalletserializeDoPaymen
     *
     * @return string
     */
    public function getSerializeDoPayment()
    {
        return $this->serializeDoPayment;
    }

    /**
     * Set id
     *
     * @param string $id
     *
     * @return Backpayline
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set date
     *
     * @param string $date
     *
     * @return Backpayline
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     *
     * @return Backpayline
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
     * Set token
     *
     * @param string $token
     *
     * @return Backpayline
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set serialize
     *
     * @param string $serialize
     *
     * @return Backpayline
     */
    public function setSerialize($serialize)
    {
        $this->serialize = $serialize;

        return $this;
    }

    /**
     * Get serialize
     *
     * @return string
     */
    public function getSerialize()
    {
        return $this->serialize;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Backpayline
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Backpayline
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
     * @return Backpayline
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
     * Get idBackpayline
     *
     * @return integer
     */
    public function getIdBackpayline()
    {
        return $this->idBackpayline;
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
