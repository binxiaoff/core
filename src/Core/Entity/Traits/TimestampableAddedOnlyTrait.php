<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Traits;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait TimestampableAddedOnlyTrait
{
    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="added", type="datetime_immutable")
     *
     * @Groups({"timestampable:read"})
     */
    protected $added;

    public function getAdded(): DateTimeImmutable
    {
        return $this->added;
    }
}
