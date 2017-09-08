<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;
use Unilend\Bundle\CoreBusinessBundle\Repository\RiskDataMonitoringRepository;

class surveillance_risqueController extends bootstrap
{

    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);

        $this->menu_admin = 'emprunteurs';
    }

    public function _default()
    {
        /** @var IntlDateFormatter dateFormatter */
        $this->dateFormatter = $this->get('date_formatter');
        $this->dateFormatter->setPattern('d MMM y');
        $this->currencyFormatter = $this->get('currency_formatter');

        /** @var RiskDataMonitoringRepository $riskDataMonitoringRepository */
        $riskDataMonitoringRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoring');
        $this->events = $this->formatEvents($riskDataMonitoringRepository->getMonitoringEvents());
    }

    /**
     * @param array $events
     *
     * @return array
     */
    private function formatEvents(array $events)
    {
        /** @var \Unilend\Bundle\CoreBusinessBundle\Repository\OperationRepository $operationRepository */
        $operationRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Operation');
        $activeStatus        = array_merge(ProjectsStatus::SALES_TEAM_UPCOMING_STATUS, ProjectsStatus::SALES_TEAM, ProjectsStatus::RISK_TEAM, ProjectsStatus::RUNNING_REPAYMENT);
        $formattedEvents     = [];

        foreach ($events as $event) {
            if (false === isset($formattedEvents[$event['siren']])) {
                $formattedEvents[$event['siren']] = [
                    'label'       => $event['name'],
                    'count'       => 0,
                    'activeSiren' => in_array($event['status'], $activeStatus)
                ];
            }

            if ($event['status'] >= ProjectsStatus::REMBOURSEMENT) {
                $event['remainingDueCapital'] = $operationRepository->getRemainingDueCapitalForProjects(new \DateTime('NOW'), [$event['id_project']]);
            }

            if ($formattedEvents[$event['siren']]['activeSiren'] || in_array($event['status'], $activeStatus)) {
                $formattedEvents[$event['siren']]['activeSiren'] = true;
            }

            $formattedEvents[$event['siren']]['count']++;
            $formattedEvents[$event['siren']]['events'][] = $event;
        }

        return $formattedEvents;
    }
}
