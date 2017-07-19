<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OperationSubType
 *
 * @ORM\Table(name="operation_sub_type", uniqueConstraints={@ORM\UniqueConstraint(name="unq_operation_sub_type_label", columns={"label"})}, indexes={@ORM\Index(name="idx_operation_sub_type_id_parent", columns={"id_parent"})})
 * @ORM\Entity
 */
class OperationSubType
{
    const CAPITAL_REPAYMENT_EARLY                  = 'capital_repayment_early';
    const CAPITAL_REPAYMENT_DEBT_COLLECTION        = 'capital_repayment_debt_collection';
    const GROSS_INTEREST_REPAYMENT_DEBT_COLLECTION = 'gross_interest_repayment_debt_collection';
    const BORROWER_COMMISSION_FUNDS                = 'borrower_commission_funds';
    const BORROWER_COMMISSION_REPAYMENT            = 'borrower_commission_repayment';

    const CAPITAL_REPAYMENT_EARLY_REGULARIZATION                  = 'capital_repayment_early_regularization';
    const CAPITAL_REPAYMENT_DEBT_COLLECTION_REGULARIZATION        = 'capital_repayment_debt_collection_regularization';
    const GROSS_INTEREST_REPAYMENT_DEBT_COLLECTION_REGULARIZATION = 'gross_interest_repayment_debt_collection_regularization';
    const BORROWER_COMMISSION_FUNDS_REGULARIZATION                = 'borrower_commission_funds_regularization';
    const BORROWER_COMMISSION_REPAYMENT_REGULARIZATION            = 'borrower_commission_repayment_regularization';

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
     * @var OperationType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\OperationType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_parent", referencedColumnName="id")
     * })
     */
    private $idParent;

    /**
     * Set label
     *
     * @param string $label
     *
     * @return OperationSubType
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
     * Set idParent
     *
     * @param OperationType $idParent
     *
     * @return OperationSubType
     */
    public function setIdParent(OperationType $idParent = null)
    {
        $this->idParent = $idParent;

        return $this;
    }

    /**
     * Get idParent
     *
     * @return OperationType
     */
    public function getIdParent()
    {
        return $this->idParent;
    }
}
