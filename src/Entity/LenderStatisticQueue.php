<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LenderStatisticQueue
 *
 * @ORM\Table(name="lender_statistic_queue")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class LenderStatisticQueue
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Entity\Wallet
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\Wallet")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wallet", referencedColumnName="id", nullable=false)
     * })
     */
    private $idWallet;



    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return LenderStatisticQueue
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
     * Set idWallet
     *
     * @param \Unilend\Entity\Wallet $idWallet
     *
     * @return LenderStatisticQueue
     */
    public function setIdWallet(\Unilend\Entity\Wallet $idWallet = null)
    {
        $this->idWallet = $idWallet;

        return $this;
    }

    /**
     * Get idWallet
     *
     * @return \Unilend\Entity\Wallet
     */
    public function getIdWallet()
    {
        return $this->idWallet;
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
}
