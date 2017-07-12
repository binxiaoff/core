<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProjectStatusManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator    = $translator;
    }

    /**
     * @param \projects $project
     *
     * @return array
     */
    public function getPossibleStatus(\projects $project)
    {
        /** @var \projects_status $projectStatus */
        $projectStatus = $this->entityManager->getRepository('projects_status');

        switch ($project->status) {
            case ProjectsStatus::LIQUIDATION_JUDICIAIRE:
                $possibleStatus = [ProjectsStatus::LIQUIDATION_JUDICIAIRE, ProjectsStatus::DEFAUT];
                break;
            case ProjectsStatus::REMBOURSEMENT_ANTICIPE:
            case ProjectsStatus::REMBOURSE:
            case ProjectsStatus::DEFAUT:
                return [];
            default:
                if ($project->status < ProjectsStatus::REMBOURSEMENT) {
                    return [];
                }
                $possibleStatus = \projects_status::$afterRepayment;
                if ($key = array_search(ProjectsStatus::DEFAUT, $possibleStatus)) {
                    unset($possibleStatus[$key]);
                }
                break;
        }

        return $projectStatus->select('status IN (' . implode(',' , $possibleStatus) . ')', 'status ASC');
    }

    /**
     * @param string $reason
     *
     * @return string
     */
    public function getRejectionReasonTranslation($reason)
    {
        switch ($this->getMainRejectionReason($reason)) {
            case '':
                return '';
            case ProjectsStatus::NON_ELIGIBLE_REASON_TOO_MUCH_PAYMENT_INCIDENT:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-too-much-payment-incidents');
            case ProjectsStatus::NON_ELIGIBLE_REASON_NON_ALLOWED_PAYMENT_INCIDENT:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-unauthorized-payment-incident');
            case ProjectsStatus::NON_ELIGIBLE_REASON_UNILEND_XERFI_ELIMINATION_SCORE:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-elimination-xerfi-score');
            case ProjectsStatus::NON_ELIGIBLE_REASON_UNILEND_XERFI_VS_ALTARES_SCORE:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-xerfi-vs-altares-score');
            case ProjectsStatus::NON_ELIGIBLE_REASON_LOW_ALTARES_SCORE:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-low-altares-score');
            case ProjectsStatus::NON_ELIGIBLE_REASON_LOW_INFOLEGALE_SCORE:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-low-infolegale-score');
            case ProjectsStatus::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-euler-traffic-light');
            case ProjectsStatus::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_ALTARES_SCORE:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-euler-traffic-light-vs-altares-score');
            case ProjectsStatus::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_UNILEND_XERFI:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-euler-traffic-light-vs-xerfi-score');
            case ProjectsStatus::NON_ELIGIBLE_REASON_EULER_GRADE_VS_UNILEND_XERFI:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-xerfi-vs-euler-grade');
            case ProjectsStatus::NON_ELIGIBLE_REASON_EULER_GRADE_VS_ALTARES_SCORE:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-euler-grade-vs-altares-score');
            case ProjectsStatus::NON_ELIGIBLE_REASON_INFOGREFFE_PRIVILEGES:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-infogreffe-privileges');
            case ProjectsStatus::NON_ELIGIBLE_REASON_ELLISPHERE_DEFAULTS:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-ellisphere-defaults');
            case ProjectsStatus::NON_ELIGIBLE_REASON_ELLISPHERE_SOCIAL_SECURITY_PRIVILEGES:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-ellisphere-social-security-privileges');
            case ProjectsStatus::NON_ELIGIBLE_REASON_ELLISPHERE_TREASURY_TAX_PRIVILEGES:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-ellisphere-treasury-tax-privileges');
            case ProjectsStatus::NON_ELIGIBLE_REASON_INFOLEGALE_CURRENT_MANAGER_INCIDENT:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-infolegale-current-manager-incident');
            case ProjectsStatus::NON_ELIGIBLE_REASON_INFOLEGALE_PREVIOUS_MANAGER_INCIDENT:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-infolegale-previous-manager-incident');
            case ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_identity':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-altares-identity-error');
            case ProjectsStatus::UNEXPECTED_RESPONSE . 'codinf_incident':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-codinf-incident-error');
            case ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_fpro':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-altares-fpro-error');
            case ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_ebe':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-altares-ebe-error');
            case ProjectsStatus::UNEXPECTED_RESPONSE . 'infogreffe_privileges':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-infogreffe-privileges-error');
            case ProjectsStatus::UNEXPECTED_RESPONSE . 'altares_score':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-altares-score-error');
            case ProjectsStatus::UNEXPECTED_RESPONSE . 'infolegale_score':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-infolegale-score-error');
            case ProjectsStatus::UNEXPECTED_RESPONSE . 'euler_traffic_light_score':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-euler-traffic-light-error');
            case ProjectsStatus::UNEXPECTED_RESPONSE . 'euler_grade':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-euler-grade-error');
            case ProjectsStatus::UNEXPECTED_RESPONSE . 'ellisphere_report':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-ellisphere-report-error');
            case ProjectsStatus::NON_ELIGIBLE_REASON_PROCEEDING:
                return $this->translator->trans('project-rejection-reason-bo_collective-proceeding');
            case ProjectsStatus::NON_ELIGIBLE_REASON_INACTIVE:
                return $this->translator->trans('project-rejection-reason-bo_inactive-siren');
            case ProjectsStatus::NON_ELIGIBLE_REASON_UNKNOWN_SIREN:
                return $this->translator->trans('project-rejection-reason-bo_no-siren');
            case ProjectsStatus::NON_ELIGIBLE_REASON_NEGATIVE_CAPITAL_STOCK:
            case ProjectsStatus::NON_ELIGIBLE_REASON_NEGATIVE_RAW_OPERATING_INCOMES:
            case ProjectsStatus::NON_ELIGIBLE_REASON_NEGATIVE_EQUITY_CAPITAL:
            case ProjectsStatus::NON_ELIGIBLE_REASON_LOW_TURNOVER:
                return $this->translator->trans('project-rejection-reason-bo_negative-operating-result');
            case ProjectsStatus::NON_ELIGIBLE_REASON_PRODUCT_NOT_FOUND:
                return $this->translator->trans('project-rejection-reason-bo_product-not-found');
            case ProjectsStatus::NON_ELIGIBLE_REASON_PRODUCT_BLEND:
                return $this->translator->trans('project-rejection-reason-bo_product-blend');
            case ProjectsStatus::NON_ELIGIBLE_REASON_COMPANY_LOCATION:
                return $this->translator->trans('project-rejection-reason-bo_company-location');
            default:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-default');
        }
    }

    /**
     * @param string $reason
     *
     * @return string
     */
    public function getMainRejectionReason($reason)
    {
        $reasons      = explode(',', $reason);
        $reasonsCount = count($reasons);

        if (1 === $reasonsCount) {
            return $reasons[0];
        }
        if (in_array(ProjectsStatus::NON_ELIGIBLE_REASON_PROCEEDING, $reasons)) {
            return ProjectsStatus::NON_ELIGIBLE_REASON_PROCEEDING;
        }
        if (in_array(ProjectsStatus::NON_ELIGIBLE_REASON_INACTIVE, $reasons)) {
            return ProjectsStatus::NON_ELIGIBLE_REASON_INACTIVE;
        }
        if (in_array(ProjectsStatus::NON_ELIGIBLE_REASON_UNKNOWN_SIREN, $reasons)) {
            return ProjectsStatus::NON_ELIGIBLE_REASON_UNKNOWN_SIREN;
        }
        if (in_array(ProjectsStatus::NON_ELIGIBLE_REASON_NEGATIVE_CAPITAL_STOCK, $reasons)) {
            return ProjectsStatus::NON_ELIGIBLE_REASON_NEGATIVE_CAPITAL_STOCK;
        }
        if (in_array(ProjectsStatus::NON_ELIGIBLE_REASON_NEGATIVE_RAW_OPERATING_INCOMES, $reasons)) {
            return ProjectsStatus::NON_ELIGIBLE_REASON_NEGATIVE_RAW_OPERATING_INCOMES;
        }
        if (in_array(ProjectsStatus::NON_ELIGIBLE_REASON_NEGATIVE_EQUITY_CAPITAL, $reasons)) {
            return ProjectsStatus::NON_ELIGIBLE_REASON_NEGATIVE_EQUITY_CAPITAL;
        }
        if (in_array(ProjectsStatus::NON_ELIGIBLE_REASON_LOW_TURNOVER, $reasons)) {
            return ProjectsStatus::NON_ELIGIBLE_REASON_LOW_TURNOVER;
        }
        return ProjectsStatus::NON_ELIGIBLE_REASON_PRODUCT_NOT_FOUND;
    }
}
