<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ProgramManager
{
    private function cloneCollection(Collection $collection): ArrayCollection
    {
        $clonedArrayCollection = new ArrayCollection();
        foreach ($collection as $item) {
            $clonedItem = clone $item;
            $clonedItem->setProgram($this);
            $clonedArrayCollection->add($clonedItem);
        }

        return $clonedArrayCollection;
    }
}
