<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LendersAccountsStatsQueue
 *
 * @ORM\Table(name="lenders_accounts_stats_queue")
 * @ORM\Entity
 */
class LendersAccountsStatsQueue
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_lender_account", type="integer", nullable=false)
     */
    private $idLenderAccount;

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
     * @ORM\Column(name="id_lenders_accounts_stats_queue", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLendersAccountsStatsQueue;



    /**
     * Set idLenderAccount
     *
     * @param integer $idLenderAccount
     *
     * @return LendersAccountsStatsQueue
     */
    public function setIdLenderAccount($idLenderAccount)
    {
        $this->idLenderAccount = $idLenderAccount;

        return $this;
    }

    /**
     * Get idLenderAccount
     *
     * @return integer
     */
    public function getIdLenderAccount()
    {
        return $this->idLenderAccount;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return LendersAccountsStatsQueue
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
     * @return LendersAccountsStatsQueue
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
     * Get idLendersAccountsStatsQueue
     *
     * @return integer
     */
    public function getIdLendersAccountsStatsQueue()
    {
        return $this->idLendersAccountsStatsQueue;
    }
}
