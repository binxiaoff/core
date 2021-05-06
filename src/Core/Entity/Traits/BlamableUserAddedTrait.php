<?php

declare(strict_types=1);

namespace Unilend\Core\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\User;

trait BlamableUserAddedTrait
{
    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\User")
     * @ORM\JoinColumn(name="added_by", referencedColumnName="id", nullable=false)
     *
     * @Groups({"blameable:read"})
     */
    private User $addedBy;

    public function getAddedBy(): User
    {
        return $this->addedBy;
    }
}
