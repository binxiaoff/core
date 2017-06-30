<?php

use Unilend\Bundle\CoreBusinessBundle\Repository\RiskDataMonitoringRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;


class risk_monitoringController extends bootstrap
{

    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;

        $this->users->checkAccess('emprunteurs');

        $this->menu_admin = 'risk_monitoring';
    }

    public function _default()
    {
        /** @var RiskDataMonitoringRepository $riskDataMonitoringRepository */
        $riskDataMonitoringRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoring');

        $this->saleTeamEvents         = $riskDataMonitoringRepository->getMonitoringEventsByProjectStatus(ProjectsStatus::SALE_TEAM);
        $this->upcomingSaleTeamEvents = $riskDataMonitoringRepository->getMonitoringEventsByProjectStatus(ProjectsStatus::UPCOMING_SALE_TEAM);
        $this->riskTeamEvents         = $riskDataMonitoringRepository->getMonitoringEventsByProjectStatus(ProjectsStatus::RISK_TEAM);
        $this->runningRepayment       = $riskDataMonitoringRepository->getMonitoringEventsByProjectStatus(ProjectsStatus::RUNNING_REPAYMENT);
    }
}
