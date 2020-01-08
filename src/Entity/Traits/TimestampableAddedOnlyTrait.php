<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

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
     * @Groups({"timestampable:added:read", "timestampable:read"})
     */
    protected $added;

    /**
     * @return DateTimeImmutable
     */
    public function getAdded(): DateTimeImmutable
    {
        return $this->added;
    }
}
