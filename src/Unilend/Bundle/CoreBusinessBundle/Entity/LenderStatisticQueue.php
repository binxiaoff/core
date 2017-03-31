<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LenderStatisticQueue
 *
 * @ORM\Table(name="lender_statistic_queue", uniqueConstraints={@ORM\UniqueConstraint(name="id_wallet", columns={"id_wallet"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\LenderStatisticQueueRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class LenderStatisticQueue
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

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
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWallet
     *
     * @return LenderStatisticQueue
     */
    public function setIdWallet(\Unilend\Bundle\CoreBusinessBundle\Entity\Wallet $idWallet = null)
    {
        $this->idWallet = $idWallet;

        return $this;
    }

    /**
     * Get idWallet
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Wallet
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

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
