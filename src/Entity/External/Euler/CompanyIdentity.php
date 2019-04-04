<?php

namespace Unilend\Entity\External\Euler;

use JMS\Serializer\Annotation as JMS;

class CompanyIdentity
{
    /**
     * @JMS\SerializedName("Id")
     * @JMS\Type("string")
     */
    private $singleInvoiceId;

    /**
     * @JMS\SerializedName("Name")
     * @JMS\Type("string")
     */
    private $companyName;

    /**
     * @JMS\Type("integer")
     */
    private $code;

    /**
     * @JMS\SerializedName("ExternalIds")
     * @JMS\Type("array<Unilend\Entity\External\Euler\ExternalId>")
     */
    private $externalIds;

    /**
     * @return string
     */
    public function getSingleInvoiceId()
    {
        return $this->singleInvoiceId;
    }

    /**
     * @return string
     */
    public function getCompanyName()
    {
        return $this->companyName;
    }

    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return array
     */
    public function getExternalIds()
    {
        return $this->externalIds;
    }
}
