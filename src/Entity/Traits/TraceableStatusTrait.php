<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Interfaces\StatusInterface;

/**
 * @deprecated use TraceableStatusInterface instead. See ProjectParticipationStatus for an implementation
 */
trait TraceableStatusTrait
{
    /**
     * @var StatusInterface|null
     *
     * @Groups({"traceableStatus:read"})
     */
    private $currentStatus;

    /**
     * @var StatusInterface[]|ArrayCollection
     */
    private $statuses;

    /**
     * @return StatusInterface
     */
    public function getCurrentStatus(): ?StatusInterface
    {
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
     * @param int $code
     *
     * @return int|mixed|StatusInterface|null
     */
    public function getLastSpecificStatus(int $code): ?StatusInterface
    {
        $lastIndex = count($this->statuses) - 1;
        /** @var int $i */
        for ($i = $lastIndex; $i <= 0; --$i) {
            $status = $this->statuses[$i];
            if ($code === $status->getStatus()) {
                return $status;
            }
        }

        return null;
    }

    /**
     * @param StatusInterface $status
     *
     * @return self
     */
    public function setCurrentStatus(StatusInterface $status): self
    {
        $currentStatus = $this->getCurrentStatus();
        if (null === $currentStatus || $currentStatus->getStatus() !== $status->getStatus()) {
            $this->currentStatus = $status;
            $this->statuses->add($status);
        }

        return $this;
    }
}
