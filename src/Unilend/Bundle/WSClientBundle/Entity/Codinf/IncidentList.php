<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Codinf;

use JMS\Serializer\Annotation as JMS;

class IncidentList
{
    /**
     * @JMS\Type("array<Unilend\Bundle\WSClientBundle\Entity\Codinf\PaymentIncident>")
     */
    private $incident;

    /**
     * @return mixed
     */
    public function getIncident()
    {
        return $this->incident;
    }
}
