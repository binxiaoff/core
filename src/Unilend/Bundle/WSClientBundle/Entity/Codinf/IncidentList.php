<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Codinf;

use JMS\Serializer\Annotation as JMS;

/**
 * Class IncidentList
 * @package Unilend\Bundle\WSClientBundle\Entity\Codinf
 * @JMS\XmlRoot("incidentList")
 */
class IncidentList
{
    /**
     * @var PaymentIncident[]
     * @JMS\Type("array<Unilend\Bundle\WSClientBundle\Entity\Codinf\PaymentIncident>")
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
