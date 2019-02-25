<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductAttributeType
 *
 * @ORM\Table(name="product_attribute_type")
 * @ORM\Entity
 */
class ProductAttributeType
{
    const ELIGIBLE_BORROWER_COMPANY_NAF_CODE            = 'product_eligible_borrower_company_naf_code';
    const ELIGIBLE_BORROWER_COMPANY_RCS                 = 'product_eligible_borrower_company_rcs';
    const ELIGIBLE_BORROWING_MOTIVE                     = 'product_eligible_borrowing_motive';
    const ELIGIBLE_EXCLUDED_BORROWING_MOTIVE            = 'product_eligible_excluded_borrowing_motive';
    const ELIGIBLE_CLIENT_ID                            = 'product_eligible_lender_id';
    const ELIGIBLE_CLIENT_TYPE                          = 'product_eligible_lender_type';
    const ELIGIBLE_EXCLUDED_HEADQUARTERS_LOCATION       = 'product_eligible_excluded_headquarters_location';
    const MAX_LOAN_DURATION_IN_MONTH                    = 'product_max_loan_duration_in_month';
    const MAX_XERFI_SCORE                               = 'product_max_xerfi_score';
    const MIN_CREATION_DAYS                             = 'product_min_creation_days';
    const MIN_LOAN_DURATION_IN_MONTH                    = 'product_min_loan_duration_in_month';
    const NO_IN_PROGRESS_BLEND_PROJECT_DAYS             = 'product_no_in_progress_blend_project_days';
    const NO_INCIDENT_UNILEND_PROJECT_DAYS              = 'product_no_incident_unilend_project_days';
    const NO_INCIDENT_BLEND_PROJECT_DAYS                = 'product_no_incident_blend_project_days';
    const MIN_PRE_SCORE                                 = 'product_min_pre_score';
    const MAX_PRE_SCORE                                 = 'product_max_pre_score';
    const ELIGIBLE_BORROWER_COMPANY_LEGAL_FORM_CODE     = 'product_eligible_borrower_legal_form_code';

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, unique=true)
     */
    private $label;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

    /**
     * @var int
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
