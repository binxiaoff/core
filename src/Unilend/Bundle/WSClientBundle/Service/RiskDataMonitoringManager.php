<?php

namespace Unilend\Bundle\WSClientBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRatingHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoringCallLog;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyRating;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;

class RiskDataMonitoringManager
{
    /** @var EntityManager */
    private $entityManager;

    /** @var EulerHermesManager */
    private $eulerHermesManager;

    /** @var  LoggerInterface */
    private $logger;

    public function __construct(EntityManager $entityManager, EulerHermesManager $eulerHermesManager, LoggerInterface $logger)
    {
        $this->entityManager      = $entityManager;
        $this->eulerHermesManager = $eulerHermesManager;
        $this->logger             = $logger;
    }

    /**
     * @param $companyRating
     * @param $siren
     *
     * @return null|\Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoring
     * @throws \Exception
     */
    public function stopMonitoringPeriod($companyRating, $siren)
    {
        $monitoring = $this->getMonitoring($companyRating, $siren);

        if (false === $monitoring->isOngoing()) {
            throw new \Exception('Monitoring of siren ' . $siren . ' has already ended on ' . $monitoring->getEnd()->format('Y-m-d'));
        }

        $monitoring->setEnd(new \DateTime('NOW'));

        $this->entityManager->flush($monitoring);

        return $monitoring;
    }

    /**
     * @param string $companyRating
     * @param string $siren
     *
     * @return null|\Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoring
     */
    public function getMonitoring($companyRating, $siren)
    {
        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoring')->findOneBy(['companyRating' => $companyRating, 'siren' => $siren]);
    }


    public function saveEulerHermesGradeMonitoringEvent($siren)
    {
        $monitoringCallLog = $this->saveMonitoringEvent(CompanyRating::TYPE_EULER_HERMES_GRADE, $siren);

        /** @var Companies $company */
        foreach ($this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findBy(['siren' => $siren]) as $company) {
            try {
                if (null !== ($eulerGrade = $this->eulerHermesManager->getGrade($siren, 'fr'))) {
                    $companyRatingHistory = new CompanyRatingHistory();
                    $companyRatingHistory->setIdCompany($company->getIdCompany());
                    $companyRatingHistory->setAction(\company_rating_history::ACTION_WS);
                    $companyRatingHistory->setIdUser(Users::USER_ID_FRONT);

                    $this->entityManager->persist($companyRatingHistory);
                    $monitoringCallLog->setIdCompanyRatingHistory($companyRatingHistory);

                    $companyRating = new CompanyRating();
                    $companyRating->setIdCompanyRatingHistory($companyRatingHistory->getIdCompanyRatingHistory());
                    $companyRating->setType(CompanyRating::TYPE_EULER_HERMES_GRADE);
                    $companyRating->setValue($eulerGrade->getGrade());

                    $this->entityManager->persist($companyRating);
                }
            } catch (\Exception $exception) {
                $this->logger->error(
                    'Could not get Euler grade: EulerHermesManager::getGrade(' . $company->getSiren() . '). Message: ' . $exception->getMessage(),
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'siren', $company->getSiren()]
                );
            }
        }

        $this->entityManager->flush();

        return $monitoringCallLog;
    }

    /**
     * @param string $companyRating
     * @param string $siren
     *
     * @return RiskDataMonitoringCallLog
     * @throws \Exception
     */
    public function saveMonitoringEvent($companyRating, $siren)
    {
        $monitoring = $this->getMonitoring($companyRating, $siren);

        if (false === $monitoring->isOngoing()) {
            throw new \Exception('Monitoring of siren ' . $siren . ' has already ended on ' . $monitoring->getEnd()->format('Y-m-d'));
        }

        $monitoringCallLog = new RiskDataMonitoringCallLog();
        $monitoringCallLog->setIdRiskDataMonitoring($monitoring);
        $monitoringCallLog->setAdded(new \DateTime('NOW'));

        $this->entityManager->persist($monitoringCallLog);

        return $monitoringCallLog;
    }
}
