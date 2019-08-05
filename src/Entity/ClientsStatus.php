<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsStatus.
 *
 * @ORM\Table(name="clients_status")
 * @ORM\Entity
 */
class ClientsStatus
{
    public const STATUS_CREATION              = 5;
    public const STATUS_TO_BE_CHECKED         = 10;
    public const STATUS_COMPLETENESS          = 20;
    public const STATUS_COMPLETENESS_REMINDER = 30;
    public const STATUS_COMPLETENESS_REPLY    = 40;
    public const STATUS_MODIFICATION          = 50;
    public const STATUS_VALIDATED             = 60;
    public const STATUS_SUSPENDED             = 65;
    public const STATUS_DISABLED              = 70;
    public const STATUS_CLOSED_LENDER_REQUEST = 80;
    public const STATUS_CLOSED_BY_UNILEND     = 90;
    public const STATUS_CLOSED_DEFINITELY     = 100;

    public const GRANTED_LOGIN = [
        self::STATUS_CREATION,
        self::STATUS_TO_BE_CHECKED,
        self::STATUS_COMPLETENESS,
        self::STATUS_COMPLETENESS_REMINDER,
        self::STATUS_COMPLETENESS_REPLY,
        self::STATUS_MODIFICATION,
        self::STATUS_VALIDATED,
        self::STATUS_SUSPENDED,
    ];

    public const GRANTED_LENDER_ACCOUNT_READ = [
        self::STATUS_TO_BE_CHECKED,
        self::STATUS_COMPLETENESS,
        self::STATUS_COMPLETENESS_REMINDER,
        self::STATUS_COMPLETENESS_REPLY,
        self::STATUS_MODIFICATION,
        self::STATUS_VALIDATED,
        self::STATUS_SUSPENDED,
    ];

    public const GRANTED_LENDER_DEPOSIT = [
        self::STATUS_TO_BE_CHECKED,
        self::STATUS_COMPLETENESS,
        self::STATUS_COMPLETENESS_REMINDER,
        self::STATUS_COMPLETENESS_REPLY,
        self::STATUS_MODIFICATION,
        self::STATUS_VALIDATED,
    ];

    public const GRANTED_LENDER_WITHDRAW = [
        self::STATUS_VALIDATED,
        self::STATUS_SUSPENDED,
    ];

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, unique=true)
     */
    private $label;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Set label.
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
     * Get label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
