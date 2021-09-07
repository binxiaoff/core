<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Staff;

trait BlamableUpdatedTrait
{
    /**
     * @var Staff|null
     *
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Staff")
     * @ORM\JoinColumn(name="updated_by", referencedColumnName="id")
     */
    private $updatedBy;

    public function getUpdatedBy(): ?Staff
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(Staff $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }
}
