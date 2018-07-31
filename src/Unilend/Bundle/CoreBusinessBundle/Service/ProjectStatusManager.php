<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{ProjectAbandonReason, ProjectRejectionReason, Projects, ProjectsStatus, ProjectsStatusHistory, ProjectStatusHistoryReason, Users};
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
    /** @var ProjectRepaymentTaskManager */
    private $projectRepaymentTaskManager;
    /** @var MailerManager */
    private $mailerManager;

    /**
     * @param EntityManagerSimulator      $entityManagerSimulator
     * @param EntityManager               $entityManager
     * @param TranslatorInterface         $translator
     * @param LoggerInterface             $logger
     * @param SlackManager                $slackManager
     * @param UniversignManager           $universignManager
     * @param ProjectRepaymentTaskManager $projectRepaymentTaskManager
     * @param MailerManager               $mailerManager
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        SlackManager $slackManager,
        UniversignManager $universignManager,
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
    private function getWsCallFailureReasonTranslation(string $reason): string
    {
        switch ($reason) {
            case '':
                return '';
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
            default:
                return $this->translator->trans('project-rejection-reason-bo_external-rating-rejection-default');
        }
    }

    /**
     * @param Projects $project
     *
     * @return array
     */
    public function getStatusReasonByProject(Projects $project): array
    {
        $reasonText        = [];
        $reasonDescription = [];

        $lastProjectStatusHistory = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory')
            ->findOneBy(['idProject' => $project], ['added' => 'DESC', 'idProjectStatusHistory' => 'DESC']);

        switch ($project->getStatus()) {
            case ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION:
                $reasonText[] = $this->getWsCallFailureReasonTranslation($lastProjectStatusHistory->getContent());
                break;
            case ProjectsStatus::ABANDONED:
                /** @var ProjectStatusHistoryReason[] $abandonReasons */
                $abandonReasons = $lastProjectStatusHistory->getAbandonReasons();
                foreach ($abandonReasons as $reason) {
                    $reasonText[$reason->getIdAbandonReason()->getIdAbandon()]        = $reason->getIdAbandonReason()->getReason();
                    $reasonDescription[$reason->getIdAbandonReason()->getIdAbandon()] = $reason->getIdAbandonReason()->getDescription();
                }
                break;
            default:
                /** @var ProjectStatusHistoryReason[] $rejectionReasons */
                $rejectionReasons = $lastProjectStatusHistory->getRejectionReasons();

                foreach ($rejectionReasons as $reason) {
                    $reasonText[$reason->getIdRejectionReason()->getIdRejection()]        = $reason->getIdRejectionReason()->getReason();
                    $reasonDescription[$reason->getIdRejectionReason()->getIdRejection()] = $reason->getIdRejectionReason()->getDescription();
                }
                break;
        }

        return ['reason' => $reasonText, 'description' => $reasonDescription];
    }

    /**
     * @param string|null $reasonLabel
     * @param string|null $type
     *
     * @return array
     */
    public function getStatusReasonByLabel(?string $reasonLabel = null, ?string $type = null): array
    {
        $reasonText        = null;
        $reasonDescription = null;

        if (
            null !== $reasonLabel
            && ProjectsStatus::UNEXPECTED_RESPONSE === substr($reasonLabel, 0, strlen(ProjectsStatus::UNEXPECTED_RESPONSE))
        ) {
            $reasonText = $this->getWsCallFailureReasonTranslation($reasonLabel);
        } elseif (null !== $reasonLabel) {
            $reason = null;
            switch ($type) {
                case 'rejection':
                    $reason = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRejectionReason')->findOneBy(['label' => $reasonLabel]);
                    break;
                case 'abandon':
                    $reason = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAbandonReason')->findOneBy(['label' => $reasonLabel]);
                    break;
            }

            if (null !== $reason) {
                $reasonText        = $reason->getReason();
                $reasonDescription = $reason->getDescription();
            }
        }

        return ['reason' => $reasonText, 'description' => $reasonDescription];
    }

    /**
     * @param Users|int          $user
     * @param int                $projectStatus
     * @param \projects|Projects $project
     * @param int                $reminderNumber
     * @param string             $content
     *
     * @throws \Doctrine\ORM\OptimisticLockException
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
            && 0 < $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->getOverdueScheduleCount($projectEntity)
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
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function projectStatusUpdateTrigger(\projects_status $projectStatus, Projects $project, Users $user)
    {
        if ($project->getStatus() >= ProjectsStatus::COMPLETE_REQUEST) {
            $message = $this->slackManager->getProjectName($project) . ' passé en statut *' . $projectStatus->label . '*';

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
                $abandonReason    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAbandonReason')
                    ->findBy(['label' => ProjectAbandonReason::OTHER_PROJECT_OF_SAME_COMPANY_REJECTED]);
                $previousProjects = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')
                    ->findBySirenAndStatus($project->getIdCompany()->getSiren(), [ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION, ProjectsStatus::INCOMPLETE_REQUEST, ProjectsStatus::COMPLETE_REQUEST], $project->getAdded());

                foreach ($previousProjects as $previousProject) {
                    $this->abandonProject($previousProject, $abandonReason, $user);
                }
                break;
            case ProjectsStatus::A_FUNDER:
                $company = $project->getIdCompany();
                if (null !== $company && null !== $company->getIdClientOwner()) {
                    $this->mailerManager->sendBorrowerAccount($company->getIdClientOwner(), 'ouverture-espace-emprunteur-plein');
                } else {
                    $this->logger->error('Could not send "ouverture-espace-emprunteur-plein" email to the borrower. Either company or client is not found', [
                        'id_project' => $project->getIdProject(),
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__
                    ]);
                }
                $this->mailerManager->sendProjectOnlineToBorrower($project);
                break;
            case ProjectsStatus::PRET_REFUSE:
                $this->universignManager->cancelProxyAndMandate($project);
                break;
            case ProjectsStatus::PROBLEME:
                $this->projectRepaymentTaskManager->disableAutomaticRepayment($project, $user);
                break;
        }
    }

    /**
     * @param Projects|\projects       $project
     * @param int                      $rejectionStatus
     * @param ProjectRejectionReason[] $rejectionReasons
     * @param Users|int                $user
     *
     * @return bool
     * @throws \Exception
     */
    public function rejectProject($project, int $rejectionStatus, array $rejectionReasons, $user): bool
    {
        $rejectionStatusList = [
            ProjectsStatus::NOT_ELIGIBLE,
            ProjectsStatus::COMMERCIAL_REJECTION,
            ProjectsStatus::ANALYSIS_REJECTION,
            ProjectsStatus::COMITY_REJECTION
        ];

        if (false === in_array($rejectionStatus, $rejectionStatusList)) {
            throw new \Exception('Incorrect project status, expected values: ' . implode(', ', $rejectionStatusList));
        }
        return $this->rejectOrAbandonProject($project, $rejectionStatus, $user, $rejectionReasons);
    }

    /**
     * @param Projects|\projects     $project
     * @param ProjectAbandonReason[] $abandonReasons
     * @param Users|int              $user
     * @param int                    $reminder
     *
     * @return bool
     */
    public function abandonProject($project, array $abandonReasons, $user, ?int $reminder = 0): bool
    {
        return $this->rejectOrAbandonProject($project, ProjectsStatus::ABANDONED, $user, $abandonReasons, $reminder);
    }

    /**
     * @param Projects|\projects $project
     * @param int                $projectStatus
     * @param Users|int          $user
     * @param array              $reasons
     * @param int                $reminder
     *
     * @return bool
     */
    private function rejectOrAbandonProject($project, int $projectStatus, $user, array $reasons, ?int $reminder = 0): bool
    {
        if ($project instanceof \projects) {
            $project = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')
                ->find($project->id_project);
        }

        $this->entityManager->beginTransaction();

        try {
            $this->addProjectStatus($user, $projectStatus, $project, $reminder);
        } catch (\Exception $exception) {

            $this->entityManager->rollback();

            $this->logFailedProjectStatusChange($project, $reasons, 'An exception ocurred when calling self::addProjectStatus().', $exception);

            return false;
        }

        $lastProjectStatusHistory = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory')
            ->findOneBy(['idProject' => $project], ['added' => 'DESC', 'idProjectStatusHistory' => 'DESC']);

        if (null !== $lastProjectStatusHistory && $lastProjectStatusHistory->getIdProjectStatus()->getStatus() === $projectStatus) {
            try {
                $this->saveProjectStatusHistoryReasons($lastProjectStatusHistory, $reasons);

                $this->entityManager->commit();

                return true;
            } catch (\Exception $exception) {
                $action  = ProjectsStatus::ABANDONED === $projectStatus ? 'abandon' : 'rejection';
                $message = 'We could not save the project ' . $action . ' reasons.';
                $this->logFailedProjectStatusChange($project, $reasons, $message, $exception);
            }
        } else {
            $message = null === $lastProjectStatusHistory ? 'The last project status was not found' : 'The project status was not correctly updated to ' . $projectStatus;
            $this->logFailedProjectStatusChange($project, $reasons, $message);
        }
        $this->entityManager->rollback();

        return false;
    }

    /**
     * @param ProjectsStatusHistory $lastProjectStatusHistory
     * @param array                 $reasons
     *
     * @throws \Exception
     */
    private function saveProjectStatusHistoryReasons(ProjectsStatusHistory $lastProjectStatusHistory, array $reasons): void
    {
        if (empty($reasons)) {
            throw new \Exception('Cannot reject or abandon project without specifying reasons.');
        }
        $historyReasons = [];

        foreach ($reasons as $reason) {
            $projectStatusHistoryReason = new ProjectStatusHistoryReason();
            $projectStatusHistoryReason
                ->setIdProjectStatusHistory($lastProjectStatusHistory);

            if ($reason instanceof ProjectAbandonReason) {
                $projectStatusHistoryReason
                    ->setIdAbandonReason($reason);
            } elseif ($reason instanceof ProjectRejectionReason) {
                $projectStatusHistoryReason
                    ->setIdRejectionReason($reason);
            }
            $this->entityManager->persist($projectStatusHistoryReason);
            $historyReasons[] = $projectStatusHistoryReason;
        }
        $this->entityManager->flush($historyReasons);
    }

    /**
     * @param Projects        $project
     * @param array           $reasons
     * @param string          $errorMessage
     * @param \Exception|null $exception
     */
    private function logFailedProjectStatusChange(Projects $project, array $reasons, string $errorMessage, ?\Exception $exception = null): void
    {
        $reasonsId = [];
        foreach ($reasons as $reason) {
            $reasonsId[] = $reason instanceof ProjectRejectionReason ? $reason->getIdRejection() : $reason->getIdAbandon();
        }
        $action        = $reasons[0] instanceof ProjectRejectionReason ? 'rejection' : 'abandon';
        $exceptionInfo = [];
        if (null !== $exception) {
            $exceptionInfo = ['message' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine()];
        }
        $this->logger->error('The project status was not updated, because ' . $errorMessage, [
            'id_project'         => $project->getIdProject(),
            $action . '_reasons' => $reasonsId,
            'class'              => __CLASS__,
            'function'           => __FUNCTION__,
            'exception'          => $exceptionInfo
        ]);
    }

    /**
     * @return array
     */
    public function getIndexedProjectStatus(): array
    {
        $indexedStatus = [];
        foreach ($this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus')->findAll() as $status) {
            $indexedStatus[$status->getStatus()] = $status->getLabel();
        }

        return $indexedStatus;
    }
}
