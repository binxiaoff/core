<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectRejectionReason
 *
 * @ORM\Table(name="project_rejection_reason")
 * @ORM\Entity
 */
class ProjectRejectionReason
{
    const STATUS_OFFLINE = 0;
    const STATUS_ONLINE  = 1;

    const CUSTOMERS                = 'customers';
    const SUPPLIERS                = 'suppliers';
    const STRONG_DOUBTS_DIRECTOR   = 'strong_doubts_director';
    const COMPANY_ENVIRONMENT      = 'company_environment';
    const OPERATIONAL_CASH_FLOW    = 'operational_cash_flow';
    const LOW_OPERATING_INCOME     = 'low_operating_income';
    const BANK_SUPPORT_BREAK       = 'bank_support_break';
    const PENDING_DISPUTE          = 'pending_dispute';
    const SCORING                  = 'scoring';
    const YOUNG_COMPANY            = 'young_company';
    const STRONG_DOUBTS_OVERALL    = 'strong_doubts_overall';
    const DISPROPORTIONATE_REQUEST = 'disproportionate_request';
    const INCONSISTENT_REQUEST     = 'inconsistent_request';
    const PROJECT_OUT_OF_SCOPE     = 'project_out_of_scope';
    const CASH_FLOW_STALEMATE      = 'cash_flow_stalemate';
    const TO_FOLLOW_UP             = 'to_follow_up';
    const SUSPENSIVE_CONDITIONS    = 'suspensive_conditions';
    const PROJECT_NOT_ELIGIBLE     = 'project_not_eligible';

    const PRODUCT_NOT_FOUND                                               = 'product_not_found';
    const PRODUCT_BLEND                                                   = 'product_blend';
    const NO_LEGAL_STATUS                                                 = 'no_legal_status';
    const ENTITY_INACTIVE                                                 = 'entity_inactive';
    const COMPANY_LOCATION                                                = 'company_location';
    const UNKNOWN_SIREN                                                   = 'unknown_siren';
    const IN_PROCEEDING                                                   = 'in_proceeding';
    const NEGATIVE_RAW_OPERATING_INCOMES                                  = 'negative_raw_operating_incomes';
    const NEGATIVE_CAPITAL_STOCK                                          = 'negative_capital_stock';
    const NEGATIVE_EQUITY_CAPITAL                                         = 'negative_equity_capital';
    const LOW_TURNOVER                                                    = 'low_turnover';
    const TOO_MUCH_PAYMENT_INCIDENT                                       = 'too_much_payment_incident';
    const NON_ALLOWED_PAYMENT_INCIDENT                                    = 'non_allowed_payment_incident';
    const UNILEND_XERFI_ELIMINATION_SCORE                                 = 'unilend_xerfi_elimination_score';
    const UNILEND_XERFI_VS_ALTARES_SCORE                                  = 'unilend_xerfi_vs_altares_score';
    const LOW_ALTARES_SCORE                                               = 'low_altares_score';
    const LOW_INFOLEGALE_SCORE                                            = 'low_infolegale_score';
    const EULER_TRAFFIC_LIGHT                                             = 'euler_traffic_light';
    const EULER_TRAFFIC_LIGHT_VS_ALTARES_SCORE                            = 'euler_traffic_light_vs_altares_score';
    const EULER_TRAFFIC_LIGHT_VS_UNILEND_XERFI                            = 'euler_traffic_light_vs_unilend_xerfi';
    const EULER_GRADE_VS_UNILEND_XERFI                                    = 'euler_grade_vs_unilend_xerfi';
    const EULER_GRADE_VS_ALTARES_SCORE                                    = 'euler_grade_vs_altares_score';
    const HAS_INFOGREFFE_PRIVILEGES                                       = 'has_infogreffe_privileges';
    const ELLISPHERE_DEFAULT                                              = 'ellisphere_default';
    const ELLISPHERE_SOCIAL_SECURITY_PRIVILEGES                           = 'ellisphere_social_security_privileges';
    const ELLISPHERE_TREASURY_TAX_PRIVILEGES                              = 'ellisphere_treasury_tax_privileges';
    const INFOLEGALE_COMPANY_INCIDENT                                     = 'infolegale_company_incident';
    const INFOLEGALE_CURRENT_MANAGER_INCIDENT                             = 'infolegale_current_manager_incident';
    const INFOLEGALE_PREVIOUS_MANAGER_INCIDENT                            = 'infolegale_previous_manager_incident';
    const INFOLEGALE_CURRENT_MANAGER_OTHER_COMPANIES_INCIDENT             = 'infolegale_current_manager_other_companies_incident';
    const INFOLEGALE_CURRENT_MANAGER_DEPOSITOR_NO_ROLE_12_MONTHS_INCIDENT = 'infolegale_current_manager_depositor_no_role_12months_incident';
    const INFOLEGALE_CURRENT_MANAGER_DEPOSITOR_CP_INCIDENT                = 'infolegale_current_manager_depositor_cp_incident';
    const INFOLEGALE_CURRENT_MANAGER_DEPOSITOR_ROLE_TARGET_INCIDENT       = 'infolegale_current_manager_depositor_role_target_incident';
    const INFOLEGALE_CURRENT_MANAGER_DEPOSITOR_ROLE_COMPLAINANT_INCIDENT  = 'infolegale_current_manager_depositor_role_complainant_incident';
    const INFOLEGALE_CURRENT_MANAGER_DEPOSITOR_ROLE_MISSING_INCIDENT      = 'infolegale_current_manager_depositor_role_missing_incident';
    const NO_BORROWER_CONTRIBUTION                                        = 'no_borrower_contribution';

    const FINANCIAL_REASONS = [
        self::LOW_TURNOVER,
        self::NEGATIVE_CAPITAL_STOCK,
        self::NEGATIVE_EQUITY_CAPITAL,
        self::NEGATIVE_RAW_OPERATING_INCOMES
    ];

    const SCORING_REASONS = [
        self::TOO_MUCH_PAYMENT_INCIDENT,
        self::NON_ALLOWED_PAYMENT_INCIDENT,
        self::UNILEND_XERFI_ELIMINATION_SCORE,
        self::UNILEND_XERFI_VS_ALTARES_SCORE,
        self::LOW_ALTARES_SCORE,
        self::LOW_INFOLEGALE_SCORE,
        self::EULER_TRAFFIC_LIGHT,
        self::EULER_TRAFFIC_LIGHT_VS_ALTARES_SCORE,
        self::EULER_TRAFFIC_LIGHT_VS_UNILEND_XERFI,
        self::EULER_GRADE_VS_ALTARES_SCORE,
        self::EULER_GRADE_VS_UNILEND_XERFI,
        self::HAS_INFOGREFFE_PRIVILEGES,
        self::ELLISPHERE_DEFAULT,
        self::ELLISPHERE_SOCIAL_SECURITY_PRIVILEGES,
        self::ELLISPHERE_TREASURY_TAX_PRIVILEGES,
        self::INFOLEGALE_COMPANY_INCIDENT,
        self::INFOLEGALE_CURRENT_MANAGER_INCIDENT,
        self::INFOLEGALE_PREVIOUS_MANAGER_INCIDENT,
        self::INFOLEGALE_CURRENT_MANAGER_OTHER_COMPANIES_INCIDENT,
        self::INFOLEGALE_CURRENT_MANAGER_DEPOSITOR_NO_ROLE_12_MONTHS_INCIDENT,
        self::INFOLEGALE_CURRENT_MANAGER_DEPOSITOR_CP_INCIDENT,
        self::INFOLEGALE_CURRENT_MANAGER_DEPOSITOR_ROLE_TARGET_INCIDENT,
        self::INFOLEGALE_CURRENT_MANAGER_DEPOSITOR_ROLE_COMPLAINANT_INCIDENT,
        self::INFOLEGALE_CURRENT_MANAGER_DEPOSITOR_ROLE_MISSING_INCIDENT
    ];

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var string
     * @ORM\Column(name="reason", type="string", length=191, nullable=false)
     */
    private $reason;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=16777215, nullable=true)
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_rejection", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idRejection;

    /**
     * Set label
     *
     * @param string $label
     *
     * @return ProjectRejectionReason
     */
    public function setLabel(string $label): ProjectRejectionReason
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Get idRejection
     *
     * @return integer
     */
    public function getIdRejection(): int
    {
        return $this->idRejection;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     *
     * @return ProjectRejectionReason
     */
    public function setReason(string $reason): ProjectRejectionReason
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return ProjectRejectionReason
     */
    public function setDescription(?string $description): ProjectRejectionReason
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    /**
     * @param bool $status
     *
     * @return ProjectRejectionReason
     */
    public function setStatus(bool $status): ProjectRejectionReason
    {
        $this->status = $status;

        return $this;
    }
}
