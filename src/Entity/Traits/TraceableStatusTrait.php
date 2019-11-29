<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Interfaces\StatusInterface;

trait TraceableStatusTrait
{
    /**
     * @var StatusInterface
     *
     * @Groups({"projectParticipation:list", "project:view"})
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
     * @param int $code
     *
     * @return int|mixed|StatusInterface|null
     */
    public function getLastSpecificStatus(int $code): ?StatusInterface
    {
        $lastIndex = count($this->statuses) - 1;
        for ($i = $lastIndex; $i <= 0; --$i) {
            $status = $this->statuses[$i];
            if ($code === $status->getStatus()) {
                return $status;
            }
        }

        return null;
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
