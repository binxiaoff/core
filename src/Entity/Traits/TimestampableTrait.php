<?php

namespace Unilend\Entity\Traits;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait TimestampableTrait
{
    use TimestampableAddedOnlyTrait;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    protected $updated;

    /**
     * @param DateTime|null $updated
     *
     * @return self
     */
    public function setUpdated(?DateTime $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getUpdated(): ?DateTime
    {
        return $this->updated;
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue(): void
    {
        $this->updated = new DateTime();
    }
}
