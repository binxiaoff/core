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
    const SUSPENDED             = 65;
    const DISABLED              = 70;
    const CLOSED_LENDER_REQUEST = 80;
    const CLOSED_BY_UNILEND     = 90;
    const CLOSED_DEFINITELY     = 100;

    const GRANTED_LOGIN = [
        self::CREATION,
        self::TO_BE_CHECKED,
        self::COMPLETENESS,
        self::COMPLETENESS_REMINDER,
        self::COMPLETENESS_REPLY,
        self::MODIFICATION,
        self::VALIDATED,
        self::SUSPENDED
    ];

    const GRANTED_LENDER_ACCOUNT_READ = [
        self::TO_BE_CHECKED,
        self::COMPLETENESS,
        self::COMPLETENESS_REMINDER,
        self::COMPLETENESS_REPLY,
        self::MODIFICATION,
        self::VALIDATED,
        self::SUSPENDED
    ];

    const GRANTED_LENDER_DEPOSIT = [
        self::TO_BE_CHECKED,
        self::COMPLETENESS,
        self::COMPLETENESS_REMINDER,
        self::COMPLETENESS_REPLY,
        self::MODIFICATION,
        self::VALIDATED
    ];

    const GRANTED_LENDER_WITHDRAW = [
        self::VALIDATED,
        self::SUSPENDED
    ];

    const GRANTED_LENDER_SPONSORSHIP = [
        self::VALIDATED
    ];

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
