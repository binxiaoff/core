<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Repository\WsCallHistoryRepository;
use Unilend\Bundle\WSClientBundle\Service\CallHistoryManager;

class WsMonitoringManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var CallHistoryManager */
    private $callHistoryManager;

    public function __construct(EntityManager $entityManager, CallHistoryManager $callHistoryManager)
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
        $wsResource          = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WsExternalResource')->findOneBy(['label' => $resourceLabel]);
        $setting             = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => $wsResource->getProviderName() . ' monitoring time lapse']);
        $minutesLeftSetting  = (null !== $setting) ? $setting->getValue() : 30;
        $historyStartingDate = new \DateTime($minutesLeftSetting . ' minutes ago');
        $callHistory         = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WsCallHistory')->getCallStatusHistoryFromDate($wsResource, $historyStartingDate);

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
        $wsExternalResourceRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WsExternalResource');
        $projectsRepository           = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $wsCallHistoryRepository      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WsCallHistory');

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
                $projectList = $this->getNonEvaluatedProjectsList($projectsRepository->getProjectsByStatusFromDate(ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION, $lastStatusDate));
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
}