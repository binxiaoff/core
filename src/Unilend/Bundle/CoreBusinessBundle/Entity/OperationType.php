<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OperationType
 *
 * @ORM\Table(name="operation_type", uniqueConstraints={@ORM\UniqueConstraint(name="label_UNIQUE", columns={"label"})}, indexes={@ORM\Index(name="fk_id_wallet_type_debtor_idx", columns={"id_wallet_type_debtor"}), @ORM\Index(name="fk_id_wallet_type_creditor_idx", columns={"id_wallet_type_creditor"})})
 * @ORM\Entity
 */
class OperationType
{
    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\WalletType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\WalletType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wallet_type_debtor", referencedColumnName="id")
     * })
     */
    private $idWalletTypeDebtor;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\WalletType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\WalletType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_wallet_type_creditor", referencedColumnName="id")
     * })
     */
    private $idWalletTypeCreditor;



    /**
     * Set label
     *
     * @param string $label
     *
     * @return OperationType
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
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
     * Set idWalletTypeDebtor
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\WalletType $idWalletTypeDebtor
     *
     * @return OperationType
     */
    public function setIdWalletTypeDebtor(\Unilend\Bundle\CoreBusinessBundle\Entity\WalletType $idWalletTypeDebtor = null)
    {
        $this->idWalletTypeDebtor = $idWalletTypeDebtor;

        return $this;
    }

    /**
     * Get idWalletTypeDebtor
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\WalletType
     */
    public function getIdWalletTypeDebtor()
    {
        return $this->idWalletTypeDebtor;
    }

    /**
     * Set idWalletTypeCreditor
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\WalletType $idWalletTypeCreditor
     *
     * @return OperationType
     */
    public function setIdWalletTypeCreditor(\Unilend\Bundle\CoreBusinessBundle\Entity\WalletType $idWalletTypeCreditor = null)
    {
        $this->idWalletTypeCreditor = $idWalletTypeCreditor;

        return $this;
    }

    /**
     * Get idWalletTypeCreditor
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\WalletType
     */
    public function getIdWalletTypeCreditor()
    {
        return $this->idWalletTypeCreditor;
    }
}
