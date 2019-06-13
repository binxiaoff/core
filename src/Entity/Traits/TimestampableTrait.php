<?php

namespace Unilend\Entity\Traits;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

trait TimestampableTrait
{
    use TimestampableAddedOnlyTrait;

    /**
     * @var DateTimeImmutable|null
     *
     * @ORM\Column(name="updated", type="datetime_immutable", nullable=true)
     */
    protected $updated;

    /**
     * @param DateTimeImmutable|null $updated
     *
     * @return self
     */
    public function setUpdated(?DateTimeImmutable $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue(): void
    {
        $this->updated = new DateTimeImmutable();
    }
}
