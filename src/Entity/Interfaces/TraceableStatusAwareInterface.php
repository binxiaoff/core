<?php

declare(strict_types=1);

namespace Unilend\Entity\Interfaces;

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
     * @param StatusInterface $status
     *
     * @return self
     */
    public function setCurrentStatus(Statusinterface $status);
}
