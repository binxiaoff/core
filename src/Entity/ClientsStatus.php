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
    public const STATUS_CREATED   = 10;
    public const STATUS_VALIDATED = 20;
    public const STATUS_BLOCKED   = 30;
    public const STATUS_CLOSED    = 100;

    public const GRANTED_LOGIN = [
        self::STATUS_CREATED,
        self::STATUS_VALIDATED,
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=191, unique=true, nullable=false)
     */
    private $code;

    /**
     * ClientsStatus constructor.
     *
     * @param int    $id
     * @param string $label
     */
    public function __construct(int $id, string $label)
    {
        $this->id   = $id;
        $this->code = $label;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
