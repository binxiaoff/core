<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Interfaces;

use Doctrine\Common\Collections\Collection;

interface TraceableStatusAwareInterface
{
    /**
     * @return Collection|StatusInterface[]
     */
    public function getStatuses();

    /**
     * @return StatusInterface
     */
    public function getCurrentStatus();

    /**
     * @return static
     */
    public function setCurrentStatus(StatusInterface $status);
}
