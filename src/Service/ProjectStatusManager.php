<?php

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Entity\{Clients, EcheanciersEmprunteur, Project, ProjectAbandonReason, ProjectRejectionReason, ProjectStatusHistory, ProjectStatusHistoryReason, Projects,
    ProjectsStatus, ProjectsStatusHistory, Users};
use Unilend\Repository\ProjectRepository;
use Unilend\Service\Front\UniversignManager;
use Unilend\Service\Repayment\ProjectRepaymentTaskManager;
use Unilend\Service\Simulator\EntityManager as EntityManagerSimulator;

class ProjectStatusManager
{
    /** @var LoggerInterface */
    protected $logger;
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var TranslatorInterface */
    private $translator;
    /** @var SlackManager */
    private $slackManager;
    /** @var UniversignManager */
    private $universignManager;
    /** @var ProjectRepaymentTaskManager */
    private $projectRepaymentTaskManager;
    /** @var MailerManager */
    private $mailerManager;
    /** @var ProjectRepository */
    private $projectRepository;

    /**
     * @param EntityManagerSimulator      $entityManagerSimulator
     * @param EntityManagerInterface      $entityManager
     * @param TranslatorInterface         $translator
     * @param LoggerInterface             $logger
     * @param SlackManager                $slackManager
     * @param UniversignManager           $universignManager
     * @param ProjectRepaymentTaskManager $projectRepaymentTaskManager
     * @param MailerManager               $mailerManager
     * @param ProjectRepository           $projectRepository
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        SlackManager $slackManager,
        UniversignManager $universignManager,
        ProjectRepaymentTaskManager $projectRepaymentTaskManager,
        MailerManager $mailerManager,
        ProjectRepository $projectRepository
    ) {
        $this->entityManagerSimulator      = $entityManagerSimulator;
        $this->entityManager               = $entityManager;
        $this->translator                  = $translator;
        $this->logger                      = $logger;
        $this->slackManager                = $slackManager;
        $this->universignManager           = $universignManager;
        $this->projectRepaymentTaskManager = $projectRepaymentTaskManager;
        $this->mailerManager               = $mailerManager;
        $this->projectRepository           = $projectRepository;
    }

    /**
     * @param Projects $project
     *
     * @throws NonUniqueResultException
     *
     * @return array
     */
    public function getPossibleStatus(Projects $project)
    {
        $projectStatus             = $this->entityManager->getRepository(ProjectsStatus::class);
        $paymentScheduleRepository = $this->entityManager->getRepository(EcheanciersEmprunteur::class);

        switch ($project->getStatus()) {
            case ProjectsStatus::STATUS_LOST:
                if (0 < $paymentScheduleRepository->getOverdueScheduleCount($project)) {
                    $possibleStatus = [ProjectsStatus::STATUS_LOST];

                    break;
                }
                $possibleStatus = [ProjectsStatus::STATUS_LOST, ProjectsStatus::STATUS_CONTRACTS_SIGNED];

                break;
            case ProjectsStatus::STATUS_FINISHED:
                return [];
            default:
                if ($project->getStatus() < ProjectsStatus::STATUS_CONTRACTS_SIGNED) {
                    return [];
                }
                $possibleStatus = ProjectsStatus::AFTER_REPAYMENT;

                $key = array_search(ProjectsStatus::STATUS_LOST, $possibleStatus);
                if (false !== $key) {
                    unset($possibleStatus[$key]);
                }

                $key = array_search(ProjectsStatus::STATUS_CONTRACTS_SIGNED, $possibleStatus);
                if (0 < $paymentScheduleRepository->getOverdueScheduleCount($project) && false !== $key) {
                    unset($possibleStatus[$key]);
                }

                break;
        }

        return $projectStatus->findBy(['status' => $possibleStatus], ['status' => 'ASC']);
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

        $lastProjectStatusHistory = $this->entityManager->getRepository(ProjectsStatusHistory::class)
            ->findOneBy(['idProject' => $project], ['added' => 'DESC', 'idProjectStatusHistory' => 'DESC'])
        ;

        switch ($project->getStatus()) {
            case ProjectsStatus::STATUS_CANCELLED:
                $reasonText[] = $this->getWsCallFailureReasonTranslation($lastProjectStatusHistory->getContent());

                break;
            case ProjectsStatus::STATUS_CANCELLED:
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
            && ProjectsStatus::UNEXPECTED_RESPONSE === mb_substr($reasonLabel, 0, mb_strlen(ProjectsStatus::UNEXPECTED_RESPONSE))
        ) {
            $reasonText = $this->getWsCallFailureReasonTranslation($reasonLabel);
        } elseif (null !== $reasonLabel) {
            $reason = null;
            switch ($type) {
                case 'rejection':
                    $reason = $this->entityManager->getRepository(ProjectRejectionReason::class)->findOneBy(['label' => $reasonLabel]);

                    break;
                case 'abandon':
                    $reason = $this->entityManager->getRepository(ProjectAbandonReason::class)->findOneBy(['label' => $reasonLabel]);

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
     * @param Clients $user
     * @param int     $projectStatus
     * @param Project $project
     */
    public function addProjectStatus(Clients $user, int $projectStatus, $project)
    {
        $projectStatusHistory = (new ProjectStatusHistory())
            ->setStatus($projectStatus)
            ->setAddedBy($user)
        ;
        $project->setProjectStatusHistory($projectStatusHistory);

        try {
            $this->projectRepository->save($project);
        } catch (OptimisticLockException | ORMException $exception) {
            $this->logger->critical(sprintf('An exception occurred while updating project status for project %s. Message: %s', $project->getId(), $exception->getMessage()), [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }
    }

    /**
     * @param Projects|\projects       $project
     * @param int                      $rejectionStatus
     * @param ProjectRejectionReason[] $rejectionReasons
     * @param Users|int                $user
     *
     * @throws Exception
     *
     * @return bool
     */
    public function rejectProject($project, int $rejectionStatus, array $rejectionReasons, $user): bool
    {
        $rejectionStatusList = [ProjectsStatus::STATUS_CANCELLED];

        if (false === in_array($rejectionStatus, $rejectionStatusList)) {
            throw new Exception(sprintf('Incorrect project status, expected values: %s', implode(', ', $rejectionStatusList)));
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
        return $this->rejectOrAbandonProject($project, ProjectsStatus::STATUS_CANCELLED, $user, $abandonReasons, $reminder);
    }

    /**
     * @return array
     */
    public function getIndexedProjectStatus(): array
    {
        $indexedStatus = [];
        foreach ($this->entityManager->getRepository(ProjectsStatus::class)->findAll() as $status) {
            $indexedStatus[$status->getStatus()] = $status->getLabel();
        }

        return $indexedStatus;
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
     * @param ProjectsStatus $projectStatus
     * @param Projects       $project
     * @param Users          $user
     *
     * @throws OptimisticLockException
     */
    private function projectStatusUpdateTrigger(ProjectsStatus $projectStatus, Projects $project, Users $user)
    {
        return;
        if ($project->getStatus() >= ProjectsStatus::STATUS_REVIEW) {
            $message = $this->slackManager->getProjectName($project) . ' passé en statut *' . $projectStatus->getLabel() . '*';

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
            case ProjectsStatus::STATUS_CANCELLED:
                if (false === empty($project->getIdCompany()->getSiren())) {
                    $abandonReason = $this->entityManager->getRepository(ProjectAbandonReason::class)
                        ->findBy(['label' => ProjectAbandonReason::OTHER_PROJECT_OF_SAME_COMPANY_REJECTED])
                    ;
                    $previousProjects = $this->entityManager->getRepository(Projects::class)
                        ->findBySiren(
                            $project->getIdCompany()->getSiren(),
                            [ProjectsStatus::STATUS_CANCELLED, ProjectsStatus::STATUS_REQUESTED, ProjectsStatus::STATUS_REVIEW],
                            $project->getAdded()
                        )
                    ;

                    foreach ($previousProjects as $previousProject) {
                        $this->abandonProject($previousProject, $abandonReason, $user);
                    }
                }

                break;
            case ProjectsStatus::STATUS_REVIEW:
                $company = $project->getIdCompany();
                if (null !== $company && null !== $company->getIdClientOwner()) {
                    $this->mailerManager->sendBorrowerAccount($company->getIdClientOwner(), 'ouverture-espace-emprunteur-plein');
                } else {
                    $this->logger->error('Could not send "ouverture-espace-emprunteur-plein" email to the borrower. Either company or client is not found', [
                        'id_project' => $project->getIdProject(),
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                    ]);
                }
                $this->mailerManager->sendProjectOnlineToBorrower($project);

                break;
            case ProjectsStatus::STATUS_LOST:
                $this->projectRepaymentTaskManager->disableAutomaticRepayment($project, $user);

                break;
        }
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
            $project = $this->entityManager->getRepository(Projects::class)
                ->find($project->id_project)
            ;
        }

        $this->entityManager->beginTransaction();

        try {
            $this->addProjectStatus($user, $projectStatus, $project, $reminder);
        } catch (Exception $exception) {
            $this->entityManager->rollback();

            $this->logFailedProjectStatusChange($project, $reasons, 'An exception ocurred when calling self::addProjectStatus().', $exception);

            return false;
        }

        $lastProjectStatusHistory = $this->entityManager->getRepository(ProjectsStatusHistory::class)
            ->findOneBy(['idProject' => $project], ['added' => 'DESC', 'idProjectStatusHistory' => 'DESC'])
        ;

        if (null !== $lastProjectStatusHistory && $lastProjectStatusHistory->getIdProjectStatus()->getStatus() === $projectStatus) {
            try {
                $this->saveProjectStatusHistoryReasons($lastProjectStatusHistory, $reasons);

                $this->entityManager->commit();

                return true;
            } catch (Exception $exception) {
                $action  = ProjectsStatus::STATUS_CANCELLED === $projectStatus ? 'abandon' : 'rejection';
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
     * @throws Exception
     */
    private function saveProjectStatusHistoryReasons(ProjectsStatusHistory $lastProjectStatusHistory, array $reasons): void
    {
        if (empty($reasons)) {
            throw new Exception('Cannot reject or abandon project without specifying reasons.');
        }
        $historyReasons = [];

        foreach ($reasons as $reason) {
            $projectStatusHistoryReason = new ProjectStatusHistoryReason();
            $projectStatusHistoryReason
                ->setIdProjectStatusHistory($lastProjectStatusHistory)
            ;

            if ($reason instanceof ProjectAbandonReason) {
                $projectStatusHistoryReason
                    ->setIdAbandonReason($reason)
                ;
            } elseif ($reason instanceof ProjectRejectionReason) {
                $projectStatusHistoryReason
                    ->setIdRejectionReason($reason)
                ;
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
     * @param Exception|null $exception
     */
    private function logFailedProjectStatusChange(Projects $project, array $reasons, string $errorMessage, ?Exception $exception = null): void
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
            'exception'          => $exceptionInfo,
        ]);
    }
}
