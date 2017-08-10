<?php

use Unilend\Bundle\CoreBusinessBundle\Repository\RiskDataMonitoringRepository;

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
        /** @var IntlDateFormatter dateFormatter */
        $this->dateFormatter = $this->get('date_formatter');
        $this->dateFormatter->setPattern('d MMM y');

        /** @var RiskDataMonitoringRepository $riskDataMonitoringRepository */
        $riskDataMonitoringRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:RiskDataMonitoring');
        $this->events                 = $riskDataMonitoringRepository->getMonitoringEvents();
    }
}
