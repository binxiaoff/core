<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRatingHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsComments;
use Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoring;
use Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoringCallLog;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRating;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\WSClientBundle\Entity\Euler\CompanyRating as EulerCompanyRating;

class RiskDataMonitoringManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var EulerHermesManager */
    private $eulerHermesManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var  TranslatorInterface */
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
        $monitoredCompanies = $this->getMonitoredCompanies($siren, $ratingType);

        return count($monitoredCompanies) > 0;
    }

    /**
     * @param string $siren
     * @param string $ratingType
     *
     * @return null|array
     */
    public function getMonitoredCompanies($siren, $ratingType)
    {
        $monitoredCompanies = $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoring')
            ->getOngoingMonitoredCompaniesBySirenAndRatingType($siren, $ratingType);

        return $monitoredCompanies;
    }

    /**
     * @param string $siren
     * @param string $ratingType
     */
    public function stopMonitoringPeriod($siren, $ratingType)
    {
        /** @var RiskDataMonitoring $monitoring */
        foreach ($this->getMonitoredCompanies($siren, $ratingType) as $monitoring) {
            if ($monitoring->isOngoing()) {
                $monitoring->setEnd(new \DateTime('NOW'));

                $this->entityManager->flush($monitoring);
            }
        }
    }

    /**
     * @param Companies $company
     * @param string $ratingType
     *
     * @throws \Exception
     */
    public function startMonitoringPeriod(Companies $company, $ratingType)
    {
        if (null !== $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoring')->findOneBy(['idCompany' => $company, 'ratingType' => $ratingType])) {
            throw new \Exception('Company . ' . $company->getIdCompany() . ' is already monitored for ' . $ratingType);
        }

        $monitoring = new RiskDataMonitoring();
        $monitoring->setIdCompany($company)
            ->setRatingType($ratingType)
            ->setStart(new \DateTime('NOW'));

        $this->entityManager->persist($monitoring);
        $this->entityManager->flush($monitoring);
    }

    /**
     * @param string $siren
     */
    public function saveEulerHermesGradeMonitoringEvent($siren)
    {
        $monitoredCompanies = $this->getMonitoredCompanies($siren, CompanyRating::TYPE_EULER_HERMES_GRADE);

        /** @var RiskDataMonitoring $monitoring */
        foreach ($monitoredCompanies as $monitoring) {
            $monitoringCallLog = $this->createMonitoringEvent($monitoring);
            $this->entityManager->persist($monitoringCallLog);
            $this->entityManager->flush($monitoringCallLog);

            try {
                if (null !== ($eulerGrade = $this->eulerHermesManager->getGrade($siren, 'fr'))) {
                    $companyRatingHistory = $this->saveCompanyRating($monitoring, $eulerGrade);

                    $monitoringCallLog->setIdCompanyRatingHistory($companyRatingHistory);
                    $this->entityManager->flush($monitoringCallLog);
                }
            } catch (\Exception $exception) {
                $this->logger->error(
                    'Could not get Euler grade: EulerHermesManager::getGrade(' . $monitoring->getIdCompany()->getSiren() . '). Message: ' . $exception->getMessage(),
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren', $monitoring->getIdCompany()->getSiren()]
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
     * @param RiskDataMonitoring $monitoring
     * @param EulerCompanyRating $eulerGrade
     *
     * @return CompanyRatingHistory
     */
    private function saveCompanyRating(RiskDataMonitoring $monitoring, EulerCompanyRating $eulerGrade)
    {
        $companyRatingHistory = new CompanyRatingHistory();
        $companyRatingHistory->setIdCompany($monitoring->getIdCompany());
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
}
