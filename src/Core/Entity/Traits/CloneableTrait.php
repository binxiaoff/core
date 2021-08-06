<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Traits;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;

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

        $this->onClone();
    }

    protected function onClone(): void
    {
    }
}
