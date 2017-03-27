<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Symfony\Component\Translation\TranslatorInterface;
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
            case \projects_status::LIQUIDATION_JUDICIAIRE:
                $possibleStatus = [\projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::DEFAUT];
                break;
            case \projects_status::REMBOURSEMENT_ANTICIPE:
            case \projects_status::REMBOURSE:
            case \projects_status::DEFAUT:
                return [];
            default:
                if ($project->status < \projects_status::REMBOURSEMENT) {
                    return [];
                }
                $possibleStatus = \projects_status::$afterRepayment;
                if ($key = array_search(\projects_status::DEFAUT, $possibleStatus)) {
                    unset($possibleStatus[$key]);
                }
                break;
        }

        return $projectStatus->select('status IN (' . implode(',' , $possibleStatus) . ')', 'status ASC');
    }

    /**
     * @param string $motive
     * @return string
     */
    public function getRejectionMotiveTranslation($motive)
    {
        switch ($motive) {
            case '':
                return '';
            case \projects_status::NON_ELIGIBLE_REASON_TOO_MUCH_PAYMENT_INCIDENT:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-too-much-payment-incidents');
            case \projects_status::NON_ELIGIBLE_REASON_NON_ALLOWED_PAYMENT_INCIDENT:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-unauthorized-payment-incident');
            case \projects_status::NON_ELIGIBLE_REASON_UNILEND_XERFI_ELIMINATION_SCORE:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-elimination-xerfi-score');
            case \projects_status::NON_ELIGIBLE_REASON_UNILEND_XERFI_VS_ALTARES_SCORE:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-xerfi-vs-altares-score');
            case \projects_status::NON_ELIGIBLE_REASON_LOW_ALTARES_SCORE:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-low-altares-score');
            case \projects_status::NON_ELIGIBLE_REASON_LOW_INFOLEGALE_SCORE:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-low-infolegale-score');
            case \projects_status::NON_ELIGIBLE_REASON_EULER_WHITE_TRAFFIC_LIGHT:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-euler-white-traffic-light');
            case \projects_status::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-euler-traffic-light');
            case \projects_status::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_ALTARES_SCORE:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-euler-traffic-light-vs-altares-score');
            case \projects_status::NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_UNILEND_XERFI:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-euler-traffic-light-vs-xerfi-score');
            case \projects_status::NON_ELIGIBLE_REASON_EULER_GRADE_VS_UNILEND_XERFI:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-xerfi-vs-euler-grade');
            case \projects_status::NON_ELIGIBLE_REASON_EULER_GRADE_VS_ALTARES_SCORE:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-euler-grade-vs-altares-score');
            case \projects_status::NON_ELIGIBLE_REASON_INFOGREFFE_UNKNOWN_PRIVILEGES:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-infogreffe-unknown-privileges');
            case \projects_status::NON_ELIGIBLE_REASON_INFOGREFFE_PRIVILEGES:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-infogreffe-privileges');
            case \projects_status::UNEXPECTED_RESPONSE . 'altares_identity':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-altares-identity-error');
            case \projects_status::UNEXPECTED_RESPONSE . 'codinf_incident':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-codinf-incident-error');
            case \projects_status::UNEXPECTED_RESPONSE . 'altares_fpro':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-altares-fpro-error');
            case \projects_status::UNEXPECTED_RESPONSE . 'altares_ebe':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-altares-ebe-error');
            case \projects_status::UNEXPECTED_RESPONSE . 'infogreffe_privileges':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-infogreffe-privileges-error');
            case \projects_status::UNEXPECTED_RESPONSE . 'altares_score':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-altares-score-error');
            case \projects_status::UNEXPECTED_RESPONSE . 'infolegale_score':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-infolegale-score-error');
            case \projects_status::UNEXPECTED_RESPONSE . 'euler_grade':
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-euler-grade-error');
            default:
                $rejectReasons = explode(',', $motive);
                if (in_array(\projects_status::NON_ELIGIBLE_REASON_PROCEEDING, $rejectReasons)) {
                    return $this->translator->trans('project-rejection-reason-bo_collective-proceeding');
                } elseif (in_array(\projects_status::NON_ELIGIBLE_REASON_INACTIVE, $rejectReasons)) {
                    return $this->translator->trans('project-rejection-reason-bo_inactive-siren');
                } elseif (in_array(\projects_status::NON_ELIGIBLE_REASON_UNKNOWN_SIREN, $rejectReasons)) {
                    return $this->translator->trans('project-rejection-reason-bo_no-siren');
                } elseif (
                    in_array(\projects_status::NON_ELIGIBLE_REASON_NEGATIVE_CAPITAL_STOCK, $rejectReasons)
                    || in_array(\projects_status::NON_ELIGIBLE_REASON_NEGATIVE_RAW_OPERATING_INCOMES, $rejectReasons)
                    || in_array(\projects_status::NON_ELIGIBLE_REASON_NEGATIVE_EQUITY_CAPITAL, $rejectReasons)
                    || in_array(\projects_status::NON_ELIGIBLE_REASON_LOW_TURNOVER, $rejectReasons)
                ) {
                    return $this->translator->trans('project-rejection-reason-bo_negative-operating-result');
                } elseif (in_array(\projects_status::NON_ELIGIBLE_REASON_PRODUCT_NOT_FOUND, $rejectReasons)) {
                    return $this->translator->trans('project-rejection-reason-bo_product-not-found');
                }
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-default');
        }
    }
}
