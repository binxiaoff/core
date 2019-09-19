<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Unilend\Entity\Interfaces\StatusInterface;

trait StatusTraceableTrait
{
    /**
     * @var StatusInterface
     */
    private $currentStatus;

    /**
     * @var StatusInterface[]|ArrayCollection
     */
    private $statuses;

    /**
     * @return StatusInterface
     */
    public function getCurrentStatus(): StatusInterface
    {
        if (!$this->currentStatus) {
            $this->currentStatus = $this->statuses->last();
        }

        return $this->currentStatus;
    }

    /**
     * @return iterable|StatusInterface[]
     */
    public function getStatuses(): iterable
    {
        return $this->statuses->toArray();
    }

    /**
     * @param StatusInterface|int $status
     *
     * @return self
     */
    private function setCurrentStatus(StatusInterface $status): self
    {
        if (null === $this->currentStatus || $this->currentStatus->getStatus() !== $status->getStatus()) {
            $this->currentStatus = $status;
            $this->statuses->add($status);
        }

        return $this;
    }
}
