<?php

namespace Unilend\Bundle\WSClientBundle\Entity\GreenPoint;

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
     * @JMS\Type("DateTime")
     */
    private $lastModified;

    /**
     * @JMS\SerializedName("creation")
     * @JMS\Type("DateTime")
     */
    private $created;

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return \DateTime
     */
    public function getLastModified(): \DateTime
    {
        return $this->lastModified;
    }

    /**
     * @return \DateTime
     */
    public function getCreated(): \DateTime
    {
        return $this->created;
    }
}
