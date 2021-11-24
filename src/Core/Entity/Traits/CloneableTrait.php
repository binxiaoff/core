<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Traits;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * API platform clones the object and put it in "previous_data" of request attributes
 * EVERYTIME we do the "GET" (see ApiPlatform\Core\EventListener\ReadListener).
 *
 * Keep in mind that the modifications done here will modify the "previous_data".
 */
trait CloneableTrait
{
    public function __clone()
    {
        if (\property_exists($this, 'id') && $this->id) {
            $this->id = null;
        }
        if (\property_exists($this, 'publicId') && $this->publicId && \method_exists($this, 'setPublicId')) {
            $this->publicId = null;
            $this->setPublicId();
        }
        if (\property_exists($this, 'added')) {
            $this->added = new DateTimeImmutable();
        }
        if (\property_exists($this, 'updated')) {
            $this->updated = null;
        }
        if (\property_exists($this, 'statuses')) {
            $this->statuses = new ArrayCollection();
        }
    }
}
