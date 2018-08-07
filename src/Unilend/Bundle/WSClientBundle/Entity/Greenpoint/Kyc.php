<?php

namespace Unilend\Bundle\WSClientBundle\Entity\Greenpoint;

use JMS\Serializer\Annotation as JMS;

class Kyc
{

    /**
     * @JMS\SerializedName("statut_dossier")
     * @JMS\Type("integer")
     */
    private $status;

    /**
     * @JMS\SerializedName("modification")
     * @JMS\Type("DateTime<'Y-m-d H:i:s.u'>")
     */
    private $lastModified;

    /**
     * @JMS\SerializedName("creation")
     * @JMS\Type("DateTime<'Y-m-d H:i:s.u'>")
     */
    private $created;

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }
}