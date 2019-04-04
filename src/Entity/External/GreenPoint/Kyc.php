<?php

namespace Unilend\Entity\External\GreenPoint;

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
     * @JMS\Type("string")
     */
    private $lastModified;

    /**
     * @JMS\SerializedName("creation")
     * @JMS\Type("string")
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
        return new \DateTime($this->lastModified);
    }

    /**
     * @return \DateTime
     */
    public function getCreated(): \DateTime
    {
        return new \DateTime($this->created);
    }
}
