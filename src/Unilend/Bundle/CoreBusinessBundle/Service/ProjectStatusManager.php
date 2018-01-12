<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Service\Repayment\ProjectRepaymentTaskManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\FrontBundle\Service\UniversignManager;

class ProjectStatusManager
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManager */
    private $entityManager;
    /** @var TranslatorInterface */
    private $translator;
    /** @var LoggerInterface */
    protected $logger;
    /** @var SlackManager */
    private $slackManager;
    /** @var UniversignManager */
    private $universignManager;
    /** @var RiskDataMonitoringManager */
    private $riskDataMonitoringManager;
    /** @var ProjectRepaymentTaskManager */
    private $projectRepaymentTaskManager;
    /** @var MailerManager */
    private $mailerManager;

    /**
     * @param EntityManagerSimulator          $entityManagerSimulator
     * @param EntityManager                   $entityManager
     * @param TranslatorInterface             $translator
     * @param LoggerInterface                 $logger
     * @param SlackManager                    $slackManager
     * @param UniversignManager               $universignManager
     * @param RiskDataMonitoringManager       $riskDataMonitoringManager
     * @param ProjectRepaymentTaskManager     $projectRepaymentTaskManager
     * @param MailerManager                   $mailerManager
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        SlackManager $slackManager,
        UniversignManager $universignManager,
        RiskDataMonitoringManager $riskDataMonitoringManager,
        ProjectRepaymentTaskManager $projectRepaymentTaskManager,
        MailerManager $mailerManager
    )
    {
        $this->entityManagerSimulator      = $entityManagerSimulator;
        $this->entityManager               = $entityManager;
        $this->translator                  = $translator;
        $this->logger                      = $logger;
        $this->slackManager                = $slackManager;
        $this->universignManager           = $universignManager;
        $this->riskDataMonitoringManager   = $riskDataMonitoringManager;
        $this->projectRepaymentTaskManager = $projectRepaymentTaskManager;
        $this->mailerManager               = $mailerManager;
    }

    /**
     * @param Projects $project
     *
     * @return array
     */
    public function getPossibleStatus(Projects $project)
    {
        $projectStatus             = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus');
        $paymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');

        switch ($project->getStatus()) {
            case ProjectsStatus::PROBLEME:
                if (0 < $paymentScheduleRepository->getOverdueScheduleCount($project)) {
                    $possibleStatus = [ProjectsStatus::PROBLEME, ProjectsStatus::LOSS];
                    break;
                }
                $possibleStatus = [ProjectsStatus::PROBLEME, ProjectsStatus::REMBOURSEMENT];
                break;
            case ProjectsStatus::REMBOURSEMENT_ANTICIPE:
            case ProjectsStatus::REMBOURSE:
            case ProjectsStatus::LOSS:
                return [];
            default:
                if ($project->getStatus() < ProjectsStatus::REMBOURSEMENT) {
                    return [];
                }
                $possibleStatus = ProjectsStatus::AFTER_REPAYMENT;

                $key = array_search(ProjectsStatus::LOSS, $possibleStatus);
                if (false !== $key) {
                    unset($possibleStatus[$key]);
                }

                $key = array_search(ProjectsStatus::REMBOURSEMENT, $possibleStatus);
                if (0 < $paymentScheduleRepository->getOverdueScheduleCount($project) && false !== $key) {
                    unset($possibleStatus[$key]);
                }
                break;
        }

        return $projectStatus->findBy(['status' => $possibleStatus], ['status' => 'ASC']);
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
            case ProjectsStatus::NON_ELIGIBLE_REASON_INFOLEGALE_COMPANY_INCIDENT:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-infolegale-company-incident');
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


    /**
     * @param Users|int          $user
     * @param int                $projectStatus
     * @param \projects|Projects $project
     * @param int                $reminderNumber
     * @param string             $content
     */
    public function addProjectStatus($user, int $projectStatus, $project, int $reminderNumber = 0, string $content = '')
    {
        if ($project instanceof \projects) {
            $projectEntity = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($project->id_project);
        } else {
            $projectEntity = $project;
        }

        if (is_numeric($user)) {
            $user = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($user);
        }

        if (
            $projectStatus === ProjectsStatus::REMBOURSEMENT
            && 0 < $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->getOverdueScheduleCount($project)
        ) {
            return;
        }

        try {
            $originStatus = $projectEntity->getStatus();
            /** @var \projects_status_history $projectsStatusHistory */
            $projectsStatusHistory = $this->entityManagerSimulator->getRepository('projects_status_history');
            /** @var \projects_status $projectStatusEntity */
            $projectStatusEntity = $this->entityManagerSimulator->getRepository('projects_status');
            $projectStatusEntity->get($projectStatus, 'status');

            $projectsStatusHistory->id_project        = $projectEntity->getIdProject();
            $projectsStatusHistory->id_project_status = $projectStatusEntity->id_project_status;
            $projectsStatusHistory->id_user           = $user->getIdUser();
            $projectsStatusHistory->numero_relance    = $reminderNumber;
            $projectsStatusHistory->content           = $content;
            $projectsStatusHistory->create();

            $projectEntity->setStatus($projectStatus);
            $this->entityManager->flush($projectEntity);

            if ($project instanceof \projects) {
                $project->status = $projectStatus;
            }
        } catch (\Exception $exception) {
            $this->logger->critical('An exception occured while updating project status for project: ' . $projectEntity->getIdProject() .
                ' - Exception: ' . $exception->getMessage(), ['file' => $exception->getFile(), 'line' => $exception->getLine()]);
        }

        if ($originStatus != $projectStatus) {
            $this->projectStatusUpdateTrigger($projectStatusEntity, $projectEntity, $user);
        }
    }

    /**
     * @param \projects_status $projectStatus
     * @param Projects         $project
     * @param Users            $user
     */
    private function projectStatusUpdateTrigger(\projects_status $projectStatus, Projects $project, Users $user)
    {
        if ($project->getStatus() >= ProjectsStatus::COMPLETE_REQUEST) {
            $message        = $this->slackManager->getProjectName($project) . ' passé en statut *' . $projectStatus->label . '*';

            if ($user) {
                $message .= ' par ' . $user->getFirstname() . ' ' . $user->getName();
            }

            if (
                $project->getIdCommercial()
                && $project->getIdCommercial()->getIdUser() > 0
                && $user !== $project->getIdCommercial()
                && false === empty($project->getIdCommercial()->getSlack())
            ) {
                $this->slackManager->sendMessage($message, '@' . $project->getIdCommercial()->getSlack());
            }

            if (
                $project->getIdAnalyste()
                && $project->getIdAnalyste()->getIdUser() > 0
                && $user !== $project->getIdAnalyste()
                && false === empty($project->getIdAnalyste()->getSlack())
            ) {
                $this->slackManager->sendMessage($message, '@' . $project->getIdAnalyste()->getSlack());
            }

            $this->slackManager->sendMessage($message, '#statuts-projets');
        }

        switch ($project->getStatus()) {
            case ProjectsStatus::COMMERCIAL_REJECTION:
            case ProjectsStatus::ANALYSIS_REJECTION:
            case ProjectsStatus::COMITY_REJECTION:
                $this->abandonOlderProjects($project, $user);
                break;
            case ProjectsStatus::A_FUNDER:
                $this->mailerManager->sendProjectOnlineToBorrower($project);
                break;
            case ProjectsStatus::PRET_REFUSE:
                $this->universignManager->cancelProxyAndMandate($project);
                break;
            case ProjectsStatus::REMBOURSE:
            case ProjectsStatus::REMBOURSEMENT_ANTICIPE:
                $this->riskDataMonitoringManager->stopMonitoringForSiren($project->getIdCompany()->getSiren());
                break;
            case ProjectsStatus::PROBLEME:
                $this->projectRepaymentTaskManager->disableAutomaticRepayment($project);
                break;
        }
    }

    /**
     * @param Projects $project
     * @param Users    $userId
     */
    private function abandonOlderProjects(Projects $project, Users $user)
    {
        /** @var \projects $projectData */
        $projectData       = $this->entityManagerSimulator->getRepository('projects');
        $previousProjects  = $projectData->getPreviousProjectsWithSameSiren($project->getIdCompany()->getSiren(), $project->getAdded()->format('Y-m-d H:i:s'));
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

        foreach ($previousProjects as $previousProject) {
            $previousProjectEntity = $projectRepository->find($previousProject['id_project']);
            if (in_array($previousProjectEntity->getStatus(), [ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION, ProjectsStatus::INCOMPLETE_REQUEST, ProjectsStatus::COMPLETE_REQUEST])) {
                $this->addProjectStatus($user, ProjectsStatus::ABANDONED, $previousProjectEntity, 0, 'same_company_project_rejected');
            }
        }
    }
}
