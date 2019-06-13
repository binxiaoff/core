<?php

namespace Unilend\Entity\Traits;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

trait TimestampableAddedOnlyTrait
{
    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="added", type="datetime_immutable")
     */
    protected $added;

    /**
     * @param DateTimeImmutable $added
     *
     * @return self
     */
    public function setAdded(DateTimeImmutable $added): self
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getAdded(): DateTimeImmutable
    {
        return $this->added;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (null === $this->added) {
            $this->added = new DateTimeImmutable();
        }
    }
}
