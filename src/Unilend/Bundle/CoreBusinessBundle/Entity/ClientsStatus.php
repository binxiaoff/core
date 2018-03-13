<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsStatus
 *
 * @ORM\Table(name="clients_status", uniqueConstraints={@ORM\UniqueConstraint(name="unq_client_status_label", columns={"label"})})
 * @ORM\Entity
 */
class ClientsStatus
{
    const CREATION              = 5;
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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
