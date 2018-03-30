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
    const STATUS_CREATION              = 5;
    const STATUS_TO_BE_CHECKED         = 10;
    const STATUS_COMPLETENESS          = 20;
    const STATUS_COMPLETENESS_REMINDER = 30;
    const STATUS_COMPLETENESS_REPLY    = 40;
    const STATUS_MODIFICATION          = 50;
    const STATUS_VALIDATED             = 60;
    const STATUS_SUSPENDED             = 65;
    const STATUS_DISABLED              = 70;
    const STATUS_CLOSED_LENDER_REQUEST = 80;
    const STATUS_CLOSED_BY_UNILEND     = 90;
    const STATUS_CLOSED_DEFINITELY     = 100;

    const GRANTED_LOGIN = [
        self::STATUS_CREATION,
        self::STATUS_TO_BE_CHECKED,
        self::STATUS_COMPLETENESS,
        self::STATUS_COMPLETENESS_REMINDER,
        self::STATUS_COMPLETENESS_REPLY,
        self::STATUS_MODIFICATION,
        self::STATUS_VALIDATED,
        self::STATUS_SUSPENDED
    ];

    const GRANTED_LENDER_ACCOUNT_READ = [
        self::STATUS_TO_BE_CHECKED,
        self::STATUS_COMPLETENESS,
        self::STATUS_COMPLETENESS_REMINDER,
        self::STATUS_COMPLETENESS_REPLY,
        self::STATUS_MODIFICATION,
        self::STATUS_VALIDATED,
        self::STATUS_SUSPENDED
    ];

    const GRANTED_LENDER_DEPOSIT = [
        self::STATUS_TO_BE_CHECKED,
        self::STATUS_COMPLETENESS,
        self::STATUS_COMPLETENESS_REMINDER,
        self::STATUS_COMPLETENESS_REPLY,
        self::STATUS_MODIFICATION,
        self::STATUS_VALIDATED
    ];

    const GRANTED_LENDER_WITHDRAW = [
        self::STATUS_VALIDATED,
        self::STATUS_SUSPENDED
    ];

    const GRANTED_LENDER_SPONSORSHIP = [
        self::STATUS_VALIDATED
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
