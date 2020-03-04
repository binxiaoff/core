<?php

declare(strict_types=1);

namespace Unilend\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Staff;

trait BlamableUpdatedTrait
{
    /**
     * @var Staff|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumn(name="updated_by", referencedColumnName="id")
     */
    private $updatedBy;

    /**
     * @return Staff|null
     */
    public function getUpdatedBy(): ?Staff
    {
        return $this->updatedBy;
    }
}
