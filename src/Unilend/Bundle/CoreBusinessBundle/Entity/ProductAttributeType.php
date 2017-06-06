<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductAttributeType
 *
 * @ORM\Table(name="product_attribute_type", uniqueConstraints={@ORM\UniqueConstraint(name="unq_attribute_type_label", columns={"label"})})
 * @ORM\Entity
 */
class ProductAttributeType
{
    const ELIGIBLE_BORROWER_COMPANY_NAF_CODE = 'product_eligible_borrower_company_naf_code';
    const ELIGIBLE_BORROWER_COMPANY_RCS      = 'product_eligible_borrower_company_rcs';
    const ELIGIBLE_BORROWING_MOTIVE          = 'product_eligible_borrowing_motive';
    const ELIGIBLE_LENDER_ID                 = 'product_eligible_lender_id';
    const ELIGIBLE_LENDER_TYPE               = 'product_eligible_lender_type';
    const MIN_CREATION_DAYS                  = 'product_min_creation_days';
    const MAX_LOAN_DURATION_IN_MONTH         = 'product_max_loan_duration_in_month';
    const MIN_LOAN_DURATION_IN_MONTH         = 'product_min_loan_duration_in_month';

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
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

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
     * @return ProductAttributeType
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
     * @return ProductAttributeType
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
     * @return ProductAttributeType
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
     * Get idType
     *
     * @return integer
     */
    public function getIdType()
    {
        return $this->idType;
    }
}
