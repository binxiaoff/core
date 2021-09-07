<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Staff;

trait BlamableArchivedTrait
{
    /**
     * @var Staff|null
     *
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Staff")
     * @ORM\JoinColumn(name="archived_by", referencedColumnName="id")
     */
    private $archivedBy;

    public function getArchivedBy(): ?Staff
    {
        return $this->archivedBy;
    }

    public function setArchivedBy(Staff $archivedBy): self
    {
        $this->archivedBy = $archivedBy;

        return $this;
    }
}
