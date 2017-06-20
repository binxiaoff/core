<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Ellisphere;

use JMS\Serializer\Annotation as JMS;

class DefaultsModule
{
    /**
     * @var SocialSecurityPrivilege
     *
     * @JMS\SerializedName("socialSecurityPrivileges")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Ellisphere\SocialSecurityPrivilege")
     */
    private $socialSecurityPrivilegesCount;
    /**
     * @var TreasuryTaxPrivilege
     *
     * @JMS\SerializedName("treasuryTaxPrivileges")
     * @JMS\Type("Unilend\Bundle\WSClientBundle\Entity\Ellisphere\TreasuryTaxPrivilege")
     */
    private $treasuryTaxPrivilegesCount;

    /**
     * @var string
     *
     * @JMS\SerializedName("defaultsNoted")
     * @JMS\Type("string")
     */
    private $defaultsNoted;

    /**
     * @var string
     *
     * @JMS\SerializedName("disputesNoted")
     * @JMS\Type("string")
     */
    private $disputesNoted;

    /**
     * @return SocialSecurityPrivilege
     */
    public function getSocialSecurityPrivilegesCount()
    {
        return $this->socialSecurityPrivilegesCount;
    }

    /**
     * @return TreasuryTaxPrivilege
     */
    public function getTreasuryTaxPrivilegesCount()
    {
        return $this->treasuryTaxPrivilegesCount;
    }

    /**
     * @return string
     */
    public function getDefaultsNoted()
    {
        return $this->defaultsNoted;
    }

    /**
     * @return string
     */
    public function getDisputesNoted()
    {
        return $this->disputesNoted;
    }

}
