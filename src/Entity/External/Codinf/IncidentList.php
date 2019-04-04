<?php

namespace Unilend\Entity\External\Codinf;

use JMS\Serializer\Annotation as JMS;

/**
 * Class IncidentList
 * @package Unilend\Entity\External\Codinf
 * @JMS\XmlRoot("incidentList")
 */
class IncidentList
{
    /**
     * @var PaymentIncident[]
     * @JMS\Type("array<Unilend\Entity\External\Codinf\PaymentIncident>")
     * @JMS\XmlList(inline = true, entry = "incident")
     */
    private $incidentList;

    /**
     * @return PaymentIncident[]
     */
    public function getIncidentList()
    {
        return $this->incidentList;
    }
}
