<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProjectsStatus
 *
 * @ORM\Table(name="projects_status", uniqueConstraints={@ORM\UniqueConstraint(name="status", columns={"status"})})
 * @ORM\Entity
 */
class ProjectsStatus
{
    const IMPOSSIBLE_AUTO_EVALUATION = 1;
    const NOT_ELIGIBLE               = 2;
    const SIMULATION                 = 3;
    const INCOMPLETE_REQUEST         = 5;
    const COMPLETE_REQUEST           = 10;
    const ABANDONED                  = 15;
    const POSTPONED                  = 19;
    const COMMERCIAL_REVIEW          = 20;
    const COMMERCIAL_REJECTION       = 25;
    const PENDING_ANALYSIS           = 30;
    const ANALYSIS_REVIEW            = 31;
    const ANALYSIS_REJECTION         = 32;
    const COMITY_REVIEW              = 33;
    const COMITY_REJECTION           = 34;
    const SUSPENSIVE_CONDITIONS      = 35;
    const PREP_FUNDING               = 37;
    const A_FUNDER                   = 40;
    const AUTO_BID_PLACED            = 45;
    const EN_FUNDING                 = 50;
    const BID_TERMINATED             = 55;
    const FUNDE                      = 60;
    const FUNDING_KO                 = 70;
    const PRET_REFUSE                = 75;
    const REMBOURSEMENT              = 80;
    const REMBOURSE                  = 90;
    const REMBOURSEMENT_ANTICIPE     = 95;
    const PROBLEME                   = 100;
    const PROBLEME_J_X               = 110;
    const RECOUVREMENT               = 120;
    const PROCEDURE_SAUVEGARDE       = 130;
    const REDRESSEMENT_JUDICIAIRE    = 140;
    const LIQUIDATION_JUDICIAIRE     = 150;
    const DEFAUT                     = 160;

    const NON_ELIGIBLE_REASON_PRODUCT_NOT_FOUND                     = 'product_not_found';
    const NON_ELIGIBLE_REASON_PRODUCT_BLEND                         = 'product_blend';
    const NON_ELIGIBLE_REASON_INACTIVE                              = 'entity_inactive';
    const NON_ELIGIBLE_REASON_UNKNOWN_SIREN                         = 'unknown_siren';
    const NON_ELIGIBLE_REASON_PROCEEDING                            = 'in_proceeding';
    const NON_ELIGIBLE_REASON_NEGATIVE_RAW_OPERATING_INCOMES        = 'negative_raw_operating_incomes';
    const NON_ELIGIBLE_REASON_NEGATIVE_CAPITAL_STOCK                = 'negative_capital_stock';
    const NON_ELIGIBLE_REASON_NEGATIVE_EQUITY_CAPITAL               = 'negative_equity_capital';
    const NON_ELIGIBLE_REASON_LOW_TURNOVER                          = 'low_turnover';
    const NON_ELIGIBLE_REASON_TOO_MUCH_PAYMENT_INCIDENT             = 'too_much_payment_incident';
    const NON_ELIGIBLE_REASON_NON_ALLOWED_PAYMENT_INCIDENT          = 'non_allowed_payment_incident';
    const NON_ELIGIBLE_REASON_UNILEND_XERFI_ELIMINATION_SCORE       = 'unilend_xerfi_elimination_score';
    const NON_ELIGIBLE_REASON_UNILEND_XERFI_VS_ALTARES_SCORE        = 'unilend_xerfi_vs_altares_score';
    const NON_ELIGIBLE_REASON_LOW_ALTARES_SCORE                     = 'low_altares_score';
    const NON_ELIGIBLE_REASON_LOW_INFOLEGALE_SCORE                  = 'low_infolegale_score';
    const NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT                   = 'euler_traffic_light';
    const NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_ALTARES_SCORE  = 'euler_traffic_light_vs_altares_score';
    const NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_UNILEND_XERFI  = 'euler_traffic_light_vs_unilend_xerfi';
    const NON_ELIGIBLE_REASON_EULER_GRADE_VS_UNILEND_XERFI          = 'euler_grade_vs_unilend_xerfi';
    const NON_ELIGIBLE_REASON_EULER_GRADE_VS_ALTARES_SCORE          = 'euler_grade_vs_altares_score';
    const NON_ELIGIBLE_REASON_INFOGREFFE_PRIVILEGES                 = 'has_infogreffe_privileges';
    const NON_ELIGIBLE_REASON_ELLISPHERE_DEFAULTS                   = 'ellisphere_default';
    const NON_ELIGIBLE_REASON_ELLISPHERE_SOCIAL_SECURITY_PRIVILEGES = 'ellisphere_social_security_privileges';
    const NON_ELIGIBLE_REASON_ELLISPHERE_TREASURY_TAX_PRIVILEGES    = 'ellisphere_treasury_tax_privileges';
    const UNEXPECTED_RESPONSE                                       = 'unexpected_response_from_';

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_project_status", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idProjectStatus;



    /**
     * Set label
     *
     * @param string $label
     *
     * @return ProjectsStatus
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
     * Set status
     *
     * @param integer $status
     *
     * @return ProjectsStatus
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get idProjectStatus
     *
     * @return integer
     */
    public function getIdProjectStatus()
    {
        return $this->idProjectStatus;
    }
}
