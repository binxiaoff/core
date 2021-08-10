<?php

declare(strict_types=1);

namespace KLS\Core\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\User;
use Symfony\Component\Serializer\Annotation\Groups;

trait BlamableUserAddedTrait
{
    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\User")
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
