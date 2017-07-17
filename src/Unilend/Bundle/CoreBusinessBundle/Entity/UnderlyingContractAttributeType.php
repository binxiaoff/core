<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UnderlyingContractAttributeType
 *
 * @ORM\Table(name="underlying_contract_attribute_type", uniqueConstraints={@ORM\UniqueConstraint(name="unq_underlying_contract_attribute_type_label", columns={"label"})})
 * @ORM\Entity
 */
class UnderlyingContractAttributeType
{
    const ELIGIBLE_CLIENT_TYPE                 = 'contract_eligible_lender_type';
    const TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO = 'contract_total_loan_amount_limitation_in_euro';
    const TOTAL_QUANTITY_LIMITATION            = 'contract_total_loan_quantity_limitation';
    const MAX_LOAN_DURATION_IN_MONTH           = 'contract_max_loan_duration_in_month';
    const ELIGIBLE_AUTOBID                     = 'contract_eligible_autobid';
    const MIN_CREATION_DAYS                    = 'contract_min_creation_days';
    const ELIGIBLE_BORROWER_COMPANY_RCS        = 'contract_eligible_borrower_company_rcs';

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_type", type="smallint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idType;



    /**
     * Set label
     *
     * @param string $label
     *
     * @return UnderlyingContractAttributeType
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return UnderlyingContractAttributeType
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
     * Get idType
     *
     * @return integer
     */
    public function getIdType()
    {
        return $this->idType;
    }
}
