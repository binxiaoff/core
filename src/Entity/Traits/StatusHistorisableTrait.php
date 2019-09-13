<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Unilend\Entity\AbstractStatus;

/**
 * Class StatusHistorisableTrait.
 */
trait StatusHistorisableTrait
{
    /**
     * @var AbstractStatus
     */
    private $currentStatus;

    /**
     * @var AbstractStatus[]|ArrayCollection
     */
    private $statuses;

    /**
     * @return AbstractStatus
     */
    public function getCurrentStatus(): AbstractStatus
    {
        if (!$this->currentStatus) {
            $this->currentStatus = $this->statuses->last();
        }

        return $this->currentStatus;
    }

    /**
     * @param AbstractStatus|int $status
     *
     * @return self
     */
    private function setCurrentStatus(AbstractStatus $status): self
    {
        if (null === $this->currentStatus || $this->currentStatus->getStatus() !== $status->getStatus()) {
            $this->currentStatus = $status;
            $this->statuses[]    = $status;
        }

        return $this;
    }

    /**
     * @return ArrayCollection|AbstractStatus[]
     */
    private function getStatuses(): iterable
    {
        return $this->statuses;
    }
}
