<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;
use Unilend\Bundle\CoreBusinessBundle\Repository\RiskDataMonitoringRepository;

class risk_monitoringController extends bootstrap
{

    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);

        $this->catchAll   = true;
        $this->menu_admin = 'emprunteurs';
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
