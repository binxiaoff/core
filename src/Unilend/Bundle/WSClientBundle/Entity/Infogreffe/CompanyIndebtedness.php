<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Infogreffe;

use JMS\Serializer\Annotation as JMS;

class CompanyIndebtedness
{
    /**
     * @var Subscription[]
     * @JMS\SerializedName("inscription_3")
     * @JMS\Type("array<Unilend\Bundle\WSClientBundle\Entity\Infogreffe\Subscription>")
     */
    private $subscription3;

    /**
     * @var Subscription[]
     * @JMS\SerializedName("inscription_4")
     * @JMS\Type("array<Unilend\Bundle\WSClientBundle\Entity\Infogreffe\Subscription>")
     */
    private $subscription4;

    /**
     * @return Subscription[]
     */
    public function getSubscription3()
    {
        return $this->subscription3;
    }

    /**
     * @return Subscription[]
     */
    public function getSubscription4()
    {
        return $this->subscription4;
    }
}
