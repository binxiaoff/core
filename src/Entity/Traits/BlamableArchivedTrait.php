<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Staff;

trait BlamableArchivedTrait
{
    /**
     * @var Staff|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Staff")
     * @ORM\JoinColumn(name="archived_by", referencedColumnName="id")
     */
    private $archivedBy;

    /**
     * @return Staff|null
     */
    public function getArchivedBy(): ?Staff
    {
        return $this->archivedBy;
    }

    /**
     * @param Staff|null $archivedBy
     *
     * @return self
     */
    private function setArchivedBy(?Staff $archivedBy): self
    {
        $this->archivedBy = $archivedBy;

        return $this;
    }
}
