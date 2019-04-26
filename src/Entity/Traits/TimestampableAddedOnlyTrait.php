<?php

namespace Unilend\Entity\Traits;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait TimestampableAddedOnlyTrait
{
    /**
     * @var DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    protected $added;

    /**
     * @param DateTime $added
     *
     * @return self
     */
    public function setAdded(DateTime $added): self
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getAdded(): DateTime
    {
        return $this->added;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (null === $this->added) {
            $this->added = new DateTime();
        }
    }
}
