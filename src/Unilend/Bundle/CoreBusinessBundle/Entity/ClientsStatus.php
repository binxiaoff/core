<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsStatus
 *
 * @ORM\Table(name="clients_status")
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ClientStatusRepository")
 */
class ClientsStatus
{
    const TO_BE_CHECKED         = 10;
    const COMPLETENESS          = 20;
    const COMPLETENESS_REMINDER = 30;
    const COMPLETENESS_REPLY    = 40;
    const MODIFICATION          = 50;
    const VALIDATED             = 60;
    const CLOSED_LENDER_REQUEST = 70;
    const CLOSED_BY_UNILEND     = 80;
    const CLOSED_DEFINITELY     = 100;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_client_status", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idClientStatus;



    /**
     * Set label
     *
     * @param string $label
     *
     * @return ClientsStatus
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return ClientsStatus
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get idClientStatus
     *
     * @return integer
     */
    public function getIdClientStatus()
    {
        return $this->idClientStatus;
    }
}
