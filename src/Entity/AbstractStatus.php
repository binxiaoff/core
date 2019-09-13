<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractStatus
{
    use ConstantsAwareTrait;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="added", type="datetime_immutable")
     */
    protected $added;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $status;

    /**
     * @param int $status
     *
     * @throws \Exception
     */
    public function __construct(int $status)
    {
        if (!in_array($status, static::getPossibleStatuses(), true)) {
            throw new InvalidArgumentException(
                sprintf('%s is not a possible status for %s', $status, __CLASS__)
            );
        }
        $this->status = $status;
        $this->added  = new DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getAdded(): DateTimeImmutable
    {
        return $this->added;
    }

    /**
     * @return array
     */
    public static function getPossibleStatuses(): iterable
    {
        return self::getConstants('STATUS_');
    }
}
