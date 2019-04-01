<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Entity\{Projects, ProjectsStatus, Settings, WsCallHistory, WsExternalResource};
use Unilend\Bundle\WSClientBundle\Service\CallHistoryManager;

class WsMonitoringManager
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var CallHistoryManager */
    private $callHistoryManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param CallHistoryManager     $callHistoryManager
     */
    public function __construct(EntityManagerInterface $entityManager, CallHistoryManager $callHistoryManager)
    {
        $this->entityManager      = $entityManager;
        $this->callHistoryManager = $callHistoryManager;
    }

    /**
     * @param string $resourceLabel
     *
     * @return array
     */
    public function getRateByCallStatus($resourceLabel)
    {
        $rateByCallStatus    = [];
        $wsResource          = $this->entityManager->getRepository(WsExternalResource::class)->findOneBy(['label' => $resourceLabel]);
        $setting             = $this->entityManager->getRepository(Settings::class)->findOneBy(['type' => $wsResource->getProviderName() . ' monitoring time lapse']);
        $minutesLeftSetting  = (null !== $setting) ? $setting->getValue() : 30;
        $historyStartingDate = new \DateTime($minutesLeftSetting . ' minutes ago');
        $callHistory         = $this->entityManager->getRepository(WsCallHistory::class)->getCallStatusHistoryFromDate($wsResource, $historyStartingDate);

        if (false === empty($callHistory)) {
            foreach ($callHistory as $resourceCallHistory) {
                $rateByCallStatus[$resourceCallHistory['idResource']]['error'] = [
                    'rate'           => round(100 * $resourceCallHistory['nbErrorCalls'] / $resourceCallHistory['totalByResource'], 2),
                    'firstErrorTime' => $resourceCallHistory['firstErrorDate']
                ];
            }
        }

        return ['timeLapse' => $minutesLeftSetting, 'rateByCallStatus' => $rateByCallStatus];
    }

    /**
     * @param array $rateByCallStatus
     * @param int   $frequency
     *
     * @return string
     */
    public function sendNotifications(array $rateByCallStatus, $frequency)
    {
        $pingDom                      = 'ok';
        $wsExternalResourceRepository = $this->entityManager->getRepository(WsExternalResource::class);
        $projectsRepository           = $this->entityManager->getRepository(Projects::class);
        $wsCallHistoryRepository      = $this->entityManager->getRepository(WsCallHistory::class);

        $frequencyInterval = new \DateInterval('PT' . $frequency . 'M');

        foreach ($rateByCallStatus['rateByCallStatus'] as $resourceId => $rate) {
            $wsResource = $wsExternalResourceRepository->find($resourceId);

            if ($rate['error']['rate'] > 0) {
                $pingDom       = 'ko';
                $firstDownTime = \DateTime::createFromFormat('Y-m-d H:i:s', $rate['error']['firstErrorTime']);
                $this->callHistoryManager->sendMonitoringAlert(
                    $wsResource,
                    'down',
                    'Le service ' . $wsResource->getResourceName() . ' du fournisseur ' . $wsResource->getProviderName() . ' est indisponible depuis le ' . $this->formatDate($firstDownTime) .
                    ' (' . $rate['error']['rate'] . '% d\'appels en erreur sur les ' . $rateByCallStatus['timeLapse'] . ' dernières minutes)'
                );
            } else {
                $date           = $wsCallHistoryRepository->getFirstUpCallFromDate($wsResource, new \DateTime($rateByCallStatus['timeLapse'] . ' minutes ago'));
                $firstUpCall    = \DateTime::createFromFormat('Y-m-d H:i:s', $date['firstUpCallDate']);
                $lastStatusDate = $wsResource->getUpdated();
                $lastStatusDate->sub($frequencyInterval);

                if (false === ($firstUpCall instanceof \DateTime)) {
                    continue;
                }
                $callHistory = $wsCallHistoryRepository->getCallStatusHistoryFromDate($wsResource, $lastStatusDate, true);

                if (false === empty($callHistory)) {
                    $errorRate = round(100 * $callHistory[0]['nbErrorCalls'] / $callHistory[0]['totalByResource'], 2);
                } else {
                    $errorRate = 0;
                }
                $projectList = $this->getNonEvaluatedProjectsList($projectsRepository->getProjectsByStatusFromDate(ProjectsStatus::STATUS_CANCELLED, $lastStatusDate));
                $projectList = (empty($projectList)) ? '' : ', liste des projets non évalués : ' . $projectList;
                $this->callHistoryManager->sendMonitoringAlert(
                    $wsResource,
                    'up',
                    'Le service ' . $wsResource->getResourceName() . ' du fournisseur ' . $wsResource->getProviderName() . ' est à nouveau disponible depuis le ' . $this->formatDate($firstUpCall) .
                    ' (' . $errorRate . '% d\'appels en erreur depuis le ' . $this->formatDate($lastStatusDate) . $projectList . ')'
                );
            }
        }

        return $pingDom;
    }

    /**
     * @param Projects[] $projects
     *
     * @return string
     */
    private function getNonEvaluatedProjectsList(array $projects)
    {
        $projectIds = [];

        foreach ($projects as $project) {
            $projectIds[] = $project->getIdProject();
        }

        return implode(', ', $projectIds);
    }

    /**
     * @param \DateTime $dateTime
     *
     * @return string
     */
    private function formatDate(\DateTime $dateTime)
    {
        return strftime('%d-%b %R', $dateTime->getTimestamp());
    }

    /**
     * @return array
     */
    public function getDataForChart()
    {
        $dataStatus    = [
            'valid'   => [],
            'warning' => [],
            'error'   => []
        ];
        $data['day']   = $dataStatus;
        $data['week']  = $dataStatus;
        $data['month'] = $dataStatus;

        $wsCallHistoryRepository = $this->entityManager->getRepository(WsCallHistory::class);
        foreach ($wsCallHistoryRepository->getDailyStatistics() as $dailyStats) {
            $data['day'][$dailyStats['callStatus']][$dailyStats['added']->format('YmdH')] = [
                'date'         => $dailyStats['added']->format('Y-m-d H:00'),
                'totalVolume'  => (int) $dailyStats['totalVolume'],
                'clientVolume' => (int) $dailyStats['clientVolume']
            ];
        }

        foreach ($data['day'] as $status => $rows) {
            $data['day'][$status] += $this->getPaddingData(new \DateTime('1 day ago'), new \DateInterval('PT1H'), 'hours');
        }

        foreach ($wsCallHistoryRepository->getWeeklyStatistics() as $weeklyStats) {
            $data['week'][$weeklyStats['callStatus']][$weeklyStats['added']->format('Ymd')] = [
                'date'         => $weeklyStats['added']->format('Y-m-d'),
                'totalVolume'  => (int) $weeklyStats['totalVolume'],
                'clientVolume' => (int) $weeklyStats['clientVolume']
            ];
        }

        foreach ($data['week'] as $status => $rows) {
            $data['week'][$status] += $this->getPaddingData(new \DateTime('1 week ago'), new \DateInterval('P1D'), 'days');
        }

        foreach ($wsCallHistoryRepository->getMonthlyStatistics() as $monthlyStats) {
            $data['month'][$monthlyStats['callStatus']][$monthlyStats['added']->format('Ymd')] = [
                'date'         => $monthlyStats['added']->format('Y-m-d'),
                'totalVolume'  => (int) $monthlyStats['totalVolume'],
                'clientVolume' => (int) $monthlyStats['clientVolume']
            ];
        }

        foreach ($data['month'] as $status => $rows) {
            $data['month'][$status] += $this->getPaddingData(new \DateTime('1 month ago'), new \DateInterval('P1D'), 'days');
        }

        foreach ($data as $period => $rows) {
            foreach ($rows as $status => $cont) {
                ksort($cont);
                $rows[$status] = array_values($cont);
            }
            $data[$period] = $rows;
        }

        return $data;
    }

    /**
     * @param \DateTime     $startDate
     * @param \DateInterval $interval
     * @param string        $period
     * @return array
     */
    private function getPaddingData(\DateTime $startDate, \DateInterval $interval, $period)
    {
        $paddingData = [];
        $now         = new \DateTime();
        while ($startDate <= $now) {
            $paddingData[$startDate->format($period === 'hours' ? 'YmdH' : 'Ymd')] = [
                'date'         => $startDate->format($period === 'hours' ? 'Y-m-d H:00' : 'Y-m-d'),
                'totalVolume'  => 0,
                'clientVolume' => 0
            ];
            $startDate->add($interval);
        }

        return $paddingData;
    }
}
