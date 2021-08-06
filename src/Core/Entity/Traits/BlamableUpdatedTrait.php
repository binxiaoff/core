<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Core\Entity\Staff;

trait BlamableUpdatedTrait
{
    /**
     * @var Staff|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Staff")
     * @ORM\JoinColumn(name="updated_by", referencedColumnName="id")
     */
    private $updatedBy;

    public function getUpdatedBy(): ?Staff
    {
        return $this->updatedBy;
    }

    /**
     * @param Staff|null $updatedBy
     */
    public function setUpdatedBy(Staff $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }
}
