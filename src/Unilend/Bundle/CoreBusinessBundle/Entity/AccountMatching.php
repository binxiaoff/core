<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccountMatching
 *
 * @ORM\Table(name="account_matching", indexes={@ORM\Index(name="fk_id_wallet_matching_idx", columns={"id_wallet"}), @ORM\Index(name="fk_id_lender_account_matching_idx", columns={"id_lender_account"})})
 * @ORM\Entity
 */
class AccountMatching
{
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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_lender_account", referencedColumnName="id_lender_account")
     * })
     */
    private $idLenderAccount;



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
     * @return AccountMatching
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
     * Set idLenderAccount
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts $idLenderAccount
     *
     * @return AccountMatching
     */
    public function setIdLenderAccount(\Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts $idLenderAccount = null)
    {
        $this->idLenderAccount = $idLenderAccount;

        return $this;
    }

    /**
     * Get idLenderAccount
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts
     */
    public function getIdLenderAccount()
    {
        return $this->idLenderAccount;
    }
}
