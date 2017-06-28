<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRatingHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsComments;
use Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoring;
use Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoringCallLog;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRating;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\WSClientBundle\Entity\Euler\CompanyRating as EulerCompanyRating;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\WSClientBundle\Service\EulerHermesManager;

class RiskDataMonitoringManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var EulerHermesManager */
    private $eulerHermesManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(EntityManager $entityManager, EulerHermesManager $eulerHermesManager, LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->entityManager      = $entityManager;
        $this->eulerHermesManager = $eulerHermesManager;
        $this->logger             = $logger;
        $this->translator         = $translator;
    }

    /**
     * @param string $siren
     * @param string $ratingType
     *
     * @return bool
     */
    public function isSirenMonitored($siren, $ratingType)
    {
        $monitoring = $this->getMonitoringForSiren($siren, $ratingType);

        if (null !== $monitoring) {
            return $monitoring->isOngoing();
        }

        return false;
    }

    /**
     * @param string $siren
     * @param string $ratingType
     *
     * @return null|array
     */
    private function getMonitoredCompanies($siren, $ratingType)
    {
        $monitoredCompanies = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')
            ->getOngoingMonitoredCompaniesBySirenAndRatingType($siren, $ratingType);

        return $monitoredCompanies;
    }

    /**
     * @param string $siren
     * @param string $ratingType
     */
    public function saveEndOfMonitoringPeriodNotification($siren, $ratingType)
    {
        $this->stopMonitoringPeriod($this->getMonitoringForSiren($siren, $ratingType));

        /** @var RiskDataMonitoring $monitoring */
        foreach ($this->getMonitoredCompanies($siren, $ratingType) as $company) {
            if ($this->eligibleForLongTermMonitoring($company)) {
                $this->startMonitoringPeriod($siren, $monitoring->getRatingType());
                //TODO CALL LONG TERM MONITORING WS
            }
        }
    }

    /**
     * @param RiskDataMonitoring $monitoring
     */
    private function stopMonitoringPeriod(RiskDataMonitoring $monitoring)
    {
        if ($monitoring->isOngoing()) {
            $monitoring->setEnd(new \DateTime('NOW'));
            $this->entityManager->flush($monitoring);
        }
    }

    /**
     * @param Companies $company
     *
     * @return bool
     * @throws \Exception
     */
    private function eligibleForLongTermMonitoring(Companies $company)
    {
        $projects                   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findBy(['idCompany' => $company]);
        $longTermMonitoringExcluded = [
            ProjectsStatus::ABANDONED,
            ProjectsStatus::COMMERCIAL_REJECTION,
            ProjectsStatus::ANALYSIS_REJECTION,
            ProjectsStatus::COMITY_REJECTION,
            ProjectsStatus::PRET_REFUSE,
            ProjectsStatus::FUNDING_KO,
            ProjectsStatus::REMBOURSE,
            ProjectsStatus::REMBOURSEMENT_ANTICIPE,
            ProjectsStatus::LIQUIDATION_JUDICIAIRE,
            ProjectsStatus::REDRESSEMENT_JUDICIAIRE,
            ProjectsStatus::PROCEDURE_SAUVEGARDE
        ];

        foreach ($projects as $project) {
            if (false === in_array($project->getStatus(), $longTermMonitoringExcluded)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $siren
     * @param string $ratingType
     *
     * @return null|RiskDataMonitoring
     */
    private function startMonitoringPeriod($siren, $ratingType)
    {
        if ($this->isSirenMonitored($siren, $ratingType)) {
            return null;
        }

        $monitoring = new RiskDataMonitoring();
        $monitoring->setSiren($siren)
            ->setRatingType($ratingType)
            ->setStart(new \DateTime('NOW'));

        $this->entityManager->persist($monitoring);
        $this->entityManager->flush($monitoring);

        return $monitoring;
    }

    /**
     * @param string $siren
     */
    public function saveEulerHermesGradeMonitoringEvent($siren)
    {
        $monitoring = $this->getMonitoringForSiren($siren, CompanyRating::TYPE_EULER_HERMES_GRADE);
        $monitoredCompanies = $this->getMonitoredCompanies($siren, CompanyRating::TYPE_EULER_HERMES_GRADE);

        /** @var Companies $company */
        foreach ($monitoredCompanies as $company) {
            $monitoringCallLog = $this->createMonitoringEvent($monitoring);
            $this->entityManager->persist($monitoringCallLog);
            $this->entityManager->flush($monitoringCallLog);

            try {
                if (null !== ($eulerGrade = $this->eulerHermesManager->getGrade($siren, 'fr'))) {
                    $companyRatingHistory = $this->saveCompanyRating($company, $eulerGrade);

                    $monitoringCallLog->setIdCompanyRatingHistory($companyRatingHistory);
                    $this->entityManager->flush($monitoringCallLog);
                }
            } catch (\Exception $exception) {
                $this->logger->error(
                    'Could not get Euler grade: EulerHermesManager::getGrade(' . $monitoring->getSiren() . '). Message: ' . $exception->getMessage(),
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren', $monitoring->getSiren()]
                );
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @param RiskDataMonitoring $monitoring
     *
     * @return RiskDataMonitoringCallLog
     */
    private function createMonitoringEvent(RiskDataMonitoring $monitoring)
    {
        $monitoringCallLog = new RiskDataMonitoringCallLog();
        $monitoringCallLog->setIdRiskDataMonitoring($monitoring);
        $monitoringCallLog->setAdded(new \DateTime('NOW'));

        return $monitoringCallLog;
    }

    /**
     * @param CompanyRating $companyRating
     */
    private function saveMonitoringEventInProjectMemos(CompanyRating $companyRating)
    {
        $companyProjects = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')
            ->findBy(['idCompany' => $companyRating->getIdCompanyRatingHistory()->getIdCompany()]);

        $memoContent = '<p><b>Evènement monitoring</b> : ' . $this->translator->trans('company-rating_' . $companyRating->getType()) . ' : ' . $companyRating->getValue() . '</p>';

        foreach ($companyProjects as $project) {
            $projectCommentEntity = new ProjectsComments();
            $projectCommentEntity->setIdProject($project);
            $projectCommentEntity->setIdUser($this->entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_FRONT));
            $projectCommentEntity->setContent($memoContent);
            $projectCommentEntity->setPublic(false);

            $this->entityManager->persist($projectCommentEntity);
        }

        $this->entityManager->flush();
    }

    /**
     * @param Companies          $company
     * @param EulerCompanyRating $eulerGrade
     *
     * @return CompanyRatingHistory
     */
    private function saveCompanyRating(Companies $company, EulerCompanyRating $eulerGrade)
    {
        $companyRatingHistory = new CompanyRatingHistory();
        $companyRatingHistory->setIdCompany($company);
        $companyRatingHistory->setAction(\company_rating_history::ACTION_WS);
        $companyRatingHistory->setIdUser(Users::USER_ID_FRONT);

        $this->entityManager->persist($companyRatingHistory);
        $this->entityManager->flush($companyRatingHistory);

        $companyRating = new CompanyRating();
        $companyRating->setIdCompanyRatingHistory($companyRatingHistory);
        $companyRating->setType(CompanyRating::TYPE_EULER_HERMES_GRADE);
        $companyRating->setValue($eulerGrade->getGrade());

        $this->entityManager->persist($companyRating);
        $this->entityManager->flush($companyRating);

        $this->saveMonitoringEventInProjectMemos($companyRating);

        return $companyRatingHistory;
    }

    public function stopMonitoringForProject(Projects $project)
    {
        //get all monitorings for the same company
        //check if they are eligible for stop


    }

    private function getMonitoringForSiren($siren, $ratingType)
    {
        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoring')->findOneBy(['siren' => $siren, 'ratingType' => $ratingType]);
    }
}
